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

        $user->syncRoles(['octagon']);
    }
}
