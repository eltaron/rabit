<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Department;
use App\Models\Division;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class KfsStructureSeeder extends Seeder
{
    public function run(): void
    {
        $governorateId = 1;

        // ===== Sector 1: الخدمات =====
        $sectorServices = Sector::where('code', 'SVC')->first();

        $deptHealth = Department::create(['sector_id' => $sectorServices->id, 'name' => 'الصحة', 'code' => 'HLT', 'is_active' => true]);
        Center::create(['department_id' => $deptHealth->id, 'name' => 'مركز كفر الشيخ العام', 'code' => 'KFS-01', 'is_active' => true]);
        Center::create(['department_id' => $deptHealth->id, 'name' => 'مركز سيدي سالم', 'code' => 'KFS-02', 'is_active' => true]);
        Center::create(['department_id' => $deptHealth->id, 'name' => 'مركز دسوق', 'code' => 'KFS-03', 'is_active' => true]);
        Center::create(['department_id' => $deptHealth->id, 'name' => 'مركز بيلا', 'code' => 'KFS-04', 'is_active' => true]);
        Center::create(['department_id' => $deptHealth->id, 'name' => 'مركز المحمودية', 'code' => 'KFS-05', 'is_active' => true]);

        $deptEducation = Department::create(['sector_id' => $sectorServices->id, 'name' => 'التعليم', 'code' => 'EDU', 'is_active' => true]);
        Center::create(['department_id' => $deptEducation->id, 'name' => 'إدارة كفر الشيخ التعليمية', 'code' => 'EDU-01', 'is_active' => true]);
        Center::create(['department_id' => $deptEducation->id, 'name' => 'إدارة دسوق التعليمية', 'code' => 'EDU-02', 'is_active' => true]);
        Center::create(['department_id' => $deptEducation->id, 'name' => 'إدارة سيدي سالم التعليمية', 'code' => 'EDU-03', 'is_active' => true]);

        $deptReligious = Department::create(['sector_id' => $sectorServices->id, 'name' => 'الأوقاف والشؤون الدينية', 'code' => 'REL', 'is_active' => true]);
        Center::create(['department_id' => $deptReligious->id, 'name' => 'Directorate of Awqaf - KFS', 'code' => 'REL-01', 'is_active' => true]);

        // ===== Sector 2: الإدارات المحلية =====
        $sectorLocal = Sector::where('code', 'LOC')->first();

        $deptLocalGov = Department::create(['sector_id' => $sectorLocal->id, 'name' => 'الlocal administration', 'code' => 'LGOV', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز كفر الشيخ', 'code' => 'LGOV-01', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز دسوق', 'code' => 'LGOV-02', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز سيدي سالم', 'code' => 'LGOV-03', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز بيلا', 'code' => 'LGOV-04', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز المحمودية', 'code' => 'LGOV-05', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز فارسكور', 'code' => 'LGOV-06', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز الحامول', 'code' => 'LGOV-07', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز بلطيم', 'code' => 'LGOV-08', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز مطوبس', 'code' => 'LGOV-09', 'is_active' => true]);
        Center::create(['department_id' => $deptLocalGov->id, 'name' => 'مركز الرياض', 'code' => 'LGOV-10', 'is_active' => true]);

        $deptCivic = Department::create(['sector_id' => $sectorLocal->id, 'name' => 'الشئون Civic', 'code' => 'CIV', 'is_active' => true]);
        Center::create(['department_id' => $deptCivic->id, 'name' => 'سجل مدني كفر الشيخ', 'code' => 'CIV-01', 'is_active' => true]);

        // ===== Sector 3: الشئون الاجتماعية =====
        $sectorSocial = Sector::where('code', 'SOC')->first();

        $deptSocial = Department::create(['sector_id' => $sectorSocial->id, 'name' => 'التضامن الاجتماعي', 'code' => 'TSN', 'is_active' => true]);
        Center::create(['department_id' => $deptSocial->id, 'name' => 'إدارة التضامن كفر الشيخ', 'code' => 'TSN-01', 'is_active' => true]);
        Center::create(['department_id' => $deptSocial->id, 'name' => 'إدارة التضامن دسوق', 'code' => 'TSN-02', 'is_active' => true]);
        Center::create(['department_id' => $deptSocial->id, 'name' => 'إدارة التضامن سيدي سالم', 'code' => 'TSN-03', 'is_active' => true]);

        $deptLabor = Department::create(['sector_id' => $sectorSocial->id, 'name' => 'القوى العاملة', 'code' => 'LAB', 'is_active' => true]);
        Center::create(['department_id' => $deptLabor->id, 'name' => 'مكتب القوى العاملة كفر الشيخ', 'code' => 'LAB-01', 'is_active' => true]);

        // ===== Sector 4: التنمية =====
        $sectorDev = Sector::where('code', 'DEV')->first();

        $deptPlanning = Department::create(['sector_id' => $sectorDev->id, 'name' => 'التخطيط والمتابعة', 'code' => 'PLN', 'is_active' => true]);
        Center::create(['department_id' => $deptPlanning->id, 'name' => 'جهاز تنمية كفر الشيخ', 'code' => 'PLN-01', 'is_active' => true]);

        $deptAgriculture = Department::create(['sector_id' => $sectorDev->id, 'name' => 'الزراعة', 'code' => 'AGR', 'is_active' => true]);
        Center::create(['department_id' => $deptAgriculture->id, 'name' => 'مديرية الزراعة كفر الشيخ', 'code' => 'AGR-01', 'is_active' => true]);
        Center::create(['department_id' => $deptAgriculture->id, 'name' => 'مركز البحوث الزراعية', 'code' => 'AGR-02', 'is_active' => true]);

        $deptIrrigation = Department::create(['sector_id' => $sectorDev->id, 'name' => 'الري', 'code' => 'IRR', 'is_active' => true]);
        Center::create(['department_id' => $deptIrrigation->id, 'name' => 'مديرية الري كفر الشيخ', 'code' => 'IRR-01', 'is_active' => true]);
    }
}
