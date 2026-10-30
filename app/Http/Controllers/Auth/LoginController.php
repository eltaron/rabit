<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        $demoAccounts = collect();

        if (config('app.show_demo_accounts')) {
            $demoAccounts = \Spatie\Permission\Models\Role::orderBy('id')->get()->map(function ($role) {
                $email = config('app.demo_accounts')[$role->name] ?? ($role->name . '@kfs.gov.eg');
                $user = $role->users()->where('email', $email)->where('is_active', true)->first(['users.id', 'email']);
                return $user ? ['role' => \App\Support\RoleLabels::label($role->name), 'email' => $user->email] : null;
            })->filter()->values();
        }

        return view('auth.login', compact('demoAccounts'));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'البريد الإلكتروني أو الرقم القومي مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $login = $credentials['login'];
        $password = $credentials['password'];

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'national_id';

        $user = User::where($fieldType, $login)->first();

        if (! $user || ! Auth::attempt([$fieldType => $login, 'password' => $password], $request->boolean('remember'))) {
            return back()->withErrors([
                'login' => 'بيانات تسجيل الدخول غير صحيحة.',
            ])->onlyInput('login');
        }

        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors([
                'login' => 'حسابك غير نشط. يرجى التواصل مع المسؤول.',
            ])->onlyInput('login');
        }

        $request->session()->regenerate();

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'login',
            'description' => 'تسجيل دخول ناجح',
            'new_values' => ['login_method' => $fieldType],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (Auth::user()->hasTwoFactor()) {
            return redirect()->route('2fa.challenge');
        }
        if (\App\Http\Controllers\Auth\TwoFactorController::required()) {
            return redirect()->route('2fa.setup')->with('warning', 'المصادقة الثنائية إجبارية. فعّلها الآن لإكمال الدخول.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'logout',
            'description' => 'تسجيل خروج',
            'old_values' => ['email' => Auth::user()->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
