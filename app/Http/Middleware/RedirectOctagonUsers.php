<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * حسابات لوحة Octagon فقط (دور octagon بدون أي دور آخر) لا تستخدم الموقع الرئيسي:
 * أي صفحة في الموقع تُحوَّل إلى لوحة /octagon مباشرة.
 * يُستثنى: المصادقة الثنائية، تسجيل الخروج، الملف الشخصي (تغيير كلمة المرور وأكواد الاسترداد).
 */
class RedirectOctagonUsers
{
    /** مسارات مسموحة: المصادقة الثنائية، الخروج، الملف الشخصي، وطلبات Livewire/Filament الخاصة باللوحة نفسها */
    private const ALLOWED_ROUTES = ['2fa.*', 'logout', 'profile.*', 'livewire.*', 'filament.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && self::isOctagonOnly($user)
            && ! $request->routeIs(...self::ALLOWED_ROUTES)
            && ! $request->is('livewire*', 'octagon', 'octagon/*')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'هذا الحساب مخصص للوحة Octagon فقط.'], 403);
            }

            return redirect('/octagon');
        }

        return $next($request);
    }

    public static function isOctagonOnly($user): bool
    {
        $roles = $user->getRoleNames();

        return $roles->count() === 1 && $roles->first() === 'octagon';
    }
}
