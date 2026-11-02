<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\TwoFactorController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يفرض المصادقة الثنائية:
 *  - حساب بلا مصادقة ثنائية (والإجبار مفعّل) ← يُحوَّل لصفحة الإعداد ولا يصل لأي صفحة أخرى.
 *  - حساب مفعّل لم يتحقق في هذه الجلسة ← يُحوَّل لصفحة إدخال الرمز.
 */
class Check2FA
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        // طلبات API بتوكن (بلا جلسة) لا تمر بهذا الفحص؛ التوكن يُصدر بعد دخول مكتمل
        if (! $user || ! $request->hasSession()) {
            return $next($request);
        }

        // حسابات Octagon فقط مستثناة من المصادقة الثنائية
        if (RedirectOctagonUsers::isOctagonOnly($user)) {
            return $next($request);
        }

        if ($user->hasTwoFactor()) {
            if (! $request->session()->get('2fa_verified')) {
                return $this->deny($request, 'challenge', 'أدخل رمز المصادقة الثنائية للمتابعة.');
            }
        } elseif (TwoFactorController::required()) {
            return $this->deny($request, 'setup', 'المصادقة الثنائية إجبارية على كل حسابات المنصة. فعّلها للمتابعة.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $target, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'two_factor' => $target], 423);
        }
        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }
        return redirect()->route('2fa.' . $target)->with('warning', $message);
    }
}
