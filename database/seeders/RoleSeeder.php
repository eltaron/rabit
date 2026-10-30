<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |----------------------------------------------------------
        | Permissions
        |----------------------------------------------------------
        */
        $permissions = [
            // Dashboard
            'view_dashboard',
            'view_governor_dashboard',

            // Forms
            'manage_form_templates',
            'create_submissions',
            'submit_data',
            'review_submissions',
            'approve_submissions',

            // Reports
            'manage_reports',
            'approve_reports',
            'view_reports',
            'send_reports',

            // Admin
            'manage_governorates',
            'manage_sectors',
            'manage_departments',
            'manage_centers',
            'manage_divisions',
            'manage_users',

            // Audit
            'view_audit_logs',

            // Chatbot
            'use_chatbot',

            // Notifications
            'view_notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        /*
        |----------------------------------------------------------
        | Roles
        |----------------------------------------------------------
        */
        $superAdmin = Role::create(['name' => 'super_admin']);
        $manager = Role::create(['name' => 'manager']);
        $employee = Role::create(['name' => 'employee']);
        $authority = Role::create(['name' => 'authority']);
        $governor = Role::create(['name' => 'governor']);
        $leader = Role::create(['name' => 'leader']);

        /*
        |----------------------------------------------------------
        | Assign Permissions to Roles
        |----------------------------------------------------------
        */
        // super_admin: all permissions
        $superAdmin->givePermissionTo(Permission::all());

        // manager: all except manage_users and view_audit_logs
        $managerPermissions = collect($permissions)->reject(function ($p) {
            return in_array($p, ['manage_users', 'view_audit_logs']);
        })->values()->all();
        $manager->givePermissionTo($managerPermissions);

        // employee
        $employee->givePermissionTo([
            'create_submissions',
            'submit_data',
            'view_reports',
            'view_dashboard',
            'use_chatbot',
            'view_notifications',
        ]);

        // authority
        $authority->givePermissionTo([
            'view_reports',
            'send_reports',
            'view_dashboard',
            'use_chatbot',
            'view_notifications',
        ]);

        // governor
        $governorPermissions = collect($permissions)->filter(function ($p) {
            return str_starts_with($p, 'view_') || $p === 'approve_reports';
        })->values()->all();
        $governor->givePermissionTo($governorPermissions);

        // leader
        $leader->givePermissionTo([
            'view_dashboard',
            'view_reports',
            'view_notifications',
        ]);
    }
}
