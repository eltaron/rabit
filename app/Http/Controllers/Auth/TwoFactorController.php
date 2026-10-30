<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * المصادقة الثنائية (TOTP) — إجبارية لكل الحسابات حسب config('app.two_factor.required').
 *
 * - السر يُولَّد ويُحفظ في الجلسة حتى يؤكد المستخدم أول رمز، ثم يُخزَّن مشفّراً.
 * - رمز QR يُرسم داخل المتصفح (لا يُرسل السر لأي خدمة خارجية).
 * - أكواد استرداد (مخزنة كـ hash) للدخول عند فقد الهاتف، كل كود يُستخدم مرة واحدة.
 * - منع إعادة استخدام نفس الرمز (verifyKeyNewer) وتحديد المحاولات.
 */
class TwoFactorController extends Controller
{
    private const PENDING = '2fa.pending_secret';

    public static function required(): bool
    {
        return (bool) config('app.two_factor.required', true);
    }

    /* ------------------------------------------------------------------ setup */

    public function showSetupForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // إعادة ضبط جهاز مفعّل مسبقاً تتطلب أن تكون الجلسة قد تحققت
        if ($user->hasTwoFactor() && ! $request->session()->get('2fa_verified')) {
            return redirect()->route('2fa.challenge');
        }

        $g = new Google2FA();
        $secret = $this->pendingSecret($request, true);

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'secretGrouped' => trim(chunk_split($secret, 4, ' ')),
            'otpauthUrl' => $g->getQRCodeUrl(config('app.two_factor.issuer'), $user->email, $secret),
            'mandatory' => self::required() && ! $user->hasTwoFactor(),
            'reconfigure' => $user->hasTwoFactor(),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'أدخل رمز التحقق المكوّن من 6 أرقام.',
            'code.digits' => 'رمز التحقق يجب أن يكون 6 أرقام.',
        ]);

        $secret = $this->pendingSecret($request, false);
        if (! $secret) {
            return redirect()->route('2fa.setup')->with('warning', 'انتهت صلاحية جلسة الإعداد، امسح الرمز الجديد.');
        }

        if ($resp = $this->tooManyAttempts($request)) return $resp;

        $g = new Google2FA();
        $ts = $g->verifyKeyNewer($secret, $this->digits($request->code), 0, $this->window());
        if ($ts === false) {
            RateLimiter::hit($this->throttleKey($request), 300);
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح. إن كنت مسحت رمزاً من قبل، احذف الحساب القديم من تطبيق المصادقة وامسح الرمز الظاهر الآن، ثم أدخل الرمز الحالي.']);
        }
        RateLimiter::clear($this->throttleKey($request));

        $user = $request->user();
        $wasEnabled = $user->hasTwoFactor();
        $codes = $this->newRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_timestamp' => $ts,
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($this->hashableCode($c)), $codes),
        ])->save();

        $request->session()->forget(self::PENDING);
        $request->session()->put('2fa_verified', true);
        $request->session()->flash('2fa_recovery_codes', $codes);

        $this->audit($request, $wasEnabled ? '2fa_reconfigured' : '2fa_enabled', $wasEnabled ? 'تغيير جهاز المصادقة الثنائية' : 'تفعيل المصادقة الثنائية');

        return redirect()->route('2fa.recovery');
    }

    /* ------------------------------------------------------------------ recovery codes */

    public function showRecoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('2fa_recovery_codes');
        if (! $codes) {
            return redirect()->route('profile.edit')->with('info', 'أكواد الاسترداد تُعرض مرة واحدة فقط. يمكنك توليد أكواد جديدة من الملف الشخصي.');
        }
        return view('auth.two-factor-recovery', ['codes' => $codes, 'user' => $request->user()]);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'أدخل كلمة المرور للتأكيد.',
            'password.current_password' => 'كلمة المرور غير صحيحة.',
        ]);

        $user = $request->user();
        abort_unless($user->hasTwoFactor(), 400);

        $codes = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($this->hashableCode($c)), $codes)])->save();
        $request->session()->flash('2fa_recovery_codes', $codes);
        $this->audit($request, '2fa_recovery_regenerated', 'توليد أكواد استرداد جديدة للمصادقة الثنائية');

        return redirect()->route('2fa.recovery');
    }

    /* ------------------------------------------------------------------ challenge */

    public function showVerifyForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasTwoFactor()) {
            return self::required() ? redirect()->route('2fa.setup') : redirect()->route('dashboard');
        }
        if ($request->session()->get('2fa_verified')) {
            return redirect()->intended(route('dashboard'));
        }
        return view('auth.two-factor-challenge', [
            'remaining' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasTwoFactor()) {
            return redirect()->route('2fa.setup');
        }

        $useRecovery = $request->filled('recovery_code');
        $request->validate(
            $useRecovery ? ['recovery_code' => ['required', 'string', 'max:30']] : ['code' => ['required', 'digits:6']],
            [
                'code.required' => 'أدخل رمز التحقق المكوّن من 6 أرقام.',
                'code.digits' => 'رمز التحقق يجب أن يكون 6 أرقام.',
            ]
        );

        if ($resp = $this->tooManyAttempts($request)) return $resp;

        $ok = $useRecovery ? $this->consumeRecoveryCode($user, $request->recovery_code) : $this->checkOtp($user, $request->code);

        if (! $ok) {
            RateLimiter::hit($this->throttleKey($request), 300);
            $this->audit($request, '2fa_failed', $useRecovery ? 'فشل الدخول بكود استرداد' : 'فشل التحقق بخطوتين');
            $field = $useRecovery ? 'recovery_code' : 'code';
            return back()->withErrors([$field => $useRecovery ? 'كود الاسترداد غير صحيح أو مستخدم من قبل.' : 'رمز التحقق غير صحيح أو مستخدم من قبل.'])
                ->with('use_recovery', $useRecovery);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        $request->session()->put('2fa_verified', true);

        $this->audit($request, '2fa_verified', $useRecovery ? 'دخول بكود استرداد' : 'تم التحقق بخطوتين');

        $left = count($user->fresh()->two_factor_recovery_codes ?? []);
        $redirect = redirect()->intended(route('dashboard'));
        if ($useRecovery) {
            $redirect->with('warning', "دخلت بكود استرداد. تبقّى {$left} كود. إن فقدت هاتفك أعد ضبط المصادقة من الملف الشخصي الآن.");
        }
        return $redirect;
    }

    /* ------------------------------------------------------------------ disable / admin reset */

    public function disable(Request $request): RedirectResponse
    {
        if (self::required()) {
            return back()->with('error', 'المصادقة الثنائية إجبارية على كل حسابات المنصة ولا يمكن تعطيلها. يمكنك تغيير جهاز المصادقة بدلاً من ذلك.');
        }

        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'كلمة المرور مطلوبة للتعطيل.',
            'password.current_password' => 'كلمة المرور غير صحيحة.',
        ]);

        $this->wipe($request->user());
        $request->session()->forget('2fa_verified');
        $this->audit($request, '2fa_disabled', 'تعطيل المصادقة الثنائية');

        return redirect()->route('profile.edit')->with('success', 'تم تعطيل المصادقة الثنائية.');
    }

    /** مدير النظام: إعادة ضبط المصادقة لمستخدم فقد هاتفه (سيُطلب منه الإعداد عند الدخول التالي) */
    public function adminReset(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('manage_users'), 403);

        $this->wipe($user);
        // إنهاء جلسات المستخدم المفتوحة
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => '2fa_reset',
            'description' => "إعادة ضبط المصادقة الثنائية للمستخدم: {$user->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "تمت إعادة ضبط المصادقة الثنائية لـ {$user->name}. سيُطلب منه إعدادها عند الدخول التالي.");
    }

    /* ------------------------------------------------------------------ helpers */

    /**
     * السر قيد الإعداد:
     *  - حساب غير مفعّل: يُحفظ مشفّراً في قاعدة البيانات (two_factor_enabled = false) فيبقى نفس رمز QR
     *    مهما سجّل المستخدم الخروج والدخول، حتى يؤكده أول مرة.
     *  - تغيير جهاز لحساب مفعّل: يُحفظ في الجلسة حتى لا يتعطل الجهاز الحالي قبل التأكيد.
     */
    private function pendingSecret(Request $request, bool $create): ?string
    {
        $user = $request->user();
        $g = new Google2FA();

        if ($user->hasTwoFactor()) {
            $secret = $request->session()->get(self::PENDING);
            if (! $secret && $create) {
                $secret = $g->generateSecretKey(32);
                $request->session()->put(self::PENDING, $secret);
            }
            return $secret;
        }

        if (! $user->two_factor_secret && $create) {
            $user->forceFill(['two_factor_secret' => $g->generateSecretKey(32), 'two_factor_enabled' => false])->save();
        }
        return $user->two_factor_secret ?: null;
    }

    /** نافذة السماح بفرق الساعة: عدد فترات الـ30 ثانية قبل/بعد الوقت الحالي */
    private function window(): int
    {
        return (int) config('app.two_factor.window', 2);
    }

    private function checkOtp(User $user, string $code): bool
    {
        $ts = (new Google2FA())->verifyKeyNewer($user->two_factor_secret, $this->digits($code), (int) $user->two_factor_last_timestamp, $this->window());
        if ($ts === false) return false;
        $user->forceFill(['two_factor_last_timestamp' => $ts])->save();
        return true;
    }

    private function consumeRecoveryCode(User $user, string $input): bool
    {
        $input = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
        $codes = $user->two_factor_recovery_codes ?? [];
        foreach ($codes as $i => $hash) {
            if (Hash::check($input, $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                return true;
            }
        }
        return false;
    }

    /** أكواد بصيغة XXXXX-XXXXX؛ تُخزَّن بدون الشرطة */
    private function newRecoveryCodes(): array
    {
        return collect(range(1, (int) config('app.two_factor.recovery_codes', 8)))
            ->map(fn () => strtoupper(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }

    public function hashableCode(string $c): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $c));
    }

    private function wipe(User $user): void
    {
        $user->refresh();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestamp' => null,
        ])->save();
    }

    private function digits(string $s): string
    {
        // يقبل الأرقام العربية والمسافات
        $s = strtr($s, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        return preg_replace('/\D/', '', $s);
    }

    private function throttleKey(Request $request): string
    {
        return '2fa:' . $request->user()->id;
    }

    private function tooManyAttempts(Request $request): ?RedirectResponse
    {
        $key = $this->throttleKey($request);
        if (RateLimiter::tooManyAttempts($key, (int) config('app.two_factor.max_attempts', 5))) {
            $min = (int) ceil(RateLimiter::availableIn($key) / 60);
            return back()->withErrors(['code' => "محاولات كثيرة غير صحيحة. حاول مرة أخرى بعد {$min} دقيقة."]);
        }
        return null;
    }

    private function audit(Request $request, string $event, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'auditable_type' => User::class,
            'auditable_id' => $request->user()?->id,
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
