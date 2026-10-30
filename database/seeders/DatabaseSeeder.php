<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Governorate;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles & permissions first
        $this->call([
            RoleSeeder::class,
            PermissionCatalogSeeder::class,
        ]);

        // Governorate
        $governorate = Governorate::create([
            'name' => 'كفر الشيخ',
            'code' => 'KFS',
        ]);

        // Sectors
        $sectorServices = Sector::create([
            'name' => 'الخدمات',
            'code' => 'SVC',
            'governorate_id' => $governorate->id,
        ]);

        $sectorLocal = Sector::create([
            'name' => 'الإدارات المحلية',
            'code' => 'LOC',
            'governorate_id' => $governorate->id,
        ]);

        $sectorSocial = Sector::create([
            'name' => 'الشئون الاجتماعية',
            'code' => 'SOC',
            'governorate_id' => $governorate->id,
        ]);

        $sectorDevelopment = Sector::create([
            'name' => 'التنمية',
            'code' => 'DEV',
            'governorate_id' => $governorate->id,
        ]);

        // Default Admin
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@kfs.gov.eg',
            'password' => Hash::make('password'),
            'national_id' => '28501011234567',
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        // Default Manager
        $manager = User::create([
            'name' => 'مدير عام',
            'email' => 'manager@kfs.gov.eg',
            'password' => Hash::make('password'),
            'national_id' => '28601011234567',
            'is_active' => true,
            'sector_id' => $sectorServices->id,
            'governorate_id' => $governorate->id,
        ]);
        $manager->assignRole('manager');

        // Default Employee
        $employee = User::create([
            'name' => 'موظف',
            'email' => 'employee@kfs.gov.eg',
            'password' => Hash::make('password'),
            'national_id' => '28701011234567',
            'is_active' => true,
            'sector_id' => $sectorSocial->id,
            'governorate_id' => $governorate->id,
        ]);
        $employee->assignRole('employee');

        // Default Authority
        $authority = User::create([
            'name' => 'السلطة المختصة',
            'email' => 'authority@kfs.gov.eg',
            'password' => Hash::make('password'),
            'national_id' => '28801011234567',
            'is_active' => true,
            'governorate_id' => $governorate->id,
        ]);
        $authority->assignRole('authority');

        // Seed departments, centers, divisions
        $this->call([
            KfsStructureSeeder::class,
            KfsDivisionsSeeder::class,
            CenterUsersSeeder::class,
        ]);

        // Assign governorate/sector/department to existing users
        $dept = \App\Models\Department::where('name', 'الصحة')->first();
        $center = \App\Models\Center::where('name', 'مركز كفر الشيخ العام')->first();
        $division = \App\Models\Division::where('name', 'قسم الباطنية')->first();

        $manager->update(['department_id' => $dept?->id, 'center_id' => $center?->id]);
        $employee->update(['department_id' => $dept?->id, 'center_id' => $center?->id, 'division_id' => $division?->id]);

        // Create sample form templates
        $template1 = \App\Models\FormTemplate::create([
            'name' => 'استمارة التظلم',
            'description' => 'نموذج تقديم التظلمات الإدارية',
            'is_active' => true,
            'requires_approval' => true,
            'sector_id' => $sectorServices->id,
            'department_id' => $dept?->id,
            'center_id' => $center?->id,
            'created_by' => $admin->id,
        ]);

        $template2 = \App\Models\FormTemplate::create([
            'name' => 'استمارة الشكوى',
            'description' => 'نموذج تسجيل الشكاوى من المواطنين',
            'is_active' => true,
            'requires_approval' => false,
            'sector_id' => $sectorSocial->id,
            'created_by' => $manager->id,
        ]);

        $template3 = \App\Models\FormTemplate::create([
            'name' => 'استمارة طلب تصريح',
            'description' => 'نموذج طلب التصاريح الإدارية',
            'is_active' => true,
            'requires_approval' => true,
            'sector_id' => $sectorLocal->id,
            'created_by' => $admin->id,
        ]);
    }
}
