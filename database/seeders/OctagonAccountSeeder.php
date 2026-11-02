<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * حساب لوحة Octagon (إحصائيات وجداول - عرض فقط).
 *   php artisan db:seed --class=OctagonAccountSeeder
 * كلمة المرور من OCTAGON_PASSWORD في .env، وإلا القيمة الافتراضية أدناه (غيّرها بعد أول دخول).
 */
class OctagonAccountSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'octagon', 'guard_name' => 'web']);

        $user = User::firstOrNew(['email' => 'octagon@kfs.gov.eg']);

        if (! $user->exists) {
            $user->fill([
                'name' => 'Octagon',
                'job_title' => 'متابعة الإحصائيات',
                'data_scope' => 'all',
                'is_active' => true,
                'locale' => 'ar',
                'password' => env('OCTAGON_PASSWORD', 'Octagon@2026'),
            ]);
            $user->email_verified_at = now();
            $user->save();
        }

        // الحساب مستثنى من المصادقة الثنائية: امسح أي بيانات 2FA قديمة
        // (قد تكون مشفّرة بـ APP_KEY مختلف فتسبب "The MAC is invalid")
        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $user->syncRoles(['octagon']);
    }
}
