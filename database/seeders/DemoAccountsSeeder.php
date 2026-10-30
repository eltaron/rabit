<?php

namespace Database\Seeders;

use App\Models\OrgUnit;
use App\Models\User;
use App\Support\RoleLabels;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * حساب تجريبي لكل دور (كلمة المرور: password) — لبيئات التطوير والتدريب فقط.
 * آمن للتكرار: لا يغيّر كلمة مرور حساب موجود.
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $root = OrgUnit::root();
        $dessouk = OrgUnit::where('type', 'center')->where('name', 'like', '%دسوق%')->first() ?? $root;

        foreach (Role::orderBy('id')->get() as $role) {
            $email = config('app.demo_accounts')[$role->name] ?? ($role->name . '@kfs.gov.eg');

            $user = User::where('email', $email)->first();
            if (! $user) {
                $unit = in_array($role->name, ['leader', 'reviewer', 'manager'], true) ? $dessouk : $root;
                $user = User::create([
                    'name' => 'حساب تجريبي — ' . RoleLabels::label($role->name),
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'org_unit_id' => $unit?->id,
                    'data_scope' => RoleLabels::DEFAULT_SCOPE[$role->name] ?? 'unit',
                    'is_active' => true,
                ]);
            }
            if (! $user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }
        }
    }
}
