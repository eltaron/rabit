<?php

namespace Database\Seeders;

use App\Support\RoleLabels;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * يزامن كتالوج الصلاحيات والأدوار الافتراضية (آمن للتشغيل أكثر من مرة).
 * الأدوار الموجودة لا تُسحب منها صلاحيات أضافها المدير؛ تُضاف الافتراضية فقط.
 */
class PermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleLabels::allPermissions() as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        foreach (RoleLabels::defaults() as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
