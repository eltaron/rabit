<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Department;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CenterUsersSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            'ديوان عام المحافظة',
            'مطوبس',
            'سيدي سالم',
            'مسير',
            'جهاز النظافة',
            'بيلا',
            'سيدي غازي',
            'مصيف بلطيم',
            'قلين',
            'بلطيم',
            'برج البرلس',
            'الحامول',
            'فوه',
            'دسوق',
            'كفر الشيخ',
            'الرياض',
        ];

        $sector = Sector::first();
        $governorate = \App\Models\Governorate::first();
        $role = 'employee';

        foreach ($centers as $idx => $centerName) {
            $email = 'center_' . ($idx + 1) . '@kfs.gov.eg';
            if (User::where('email', $email)->exists()) continue;

            $center = Center::where('name', 'LIKE', "%{$centerName}%")->first();

            $user = User::create([
                'name' => 'مسؤول ' . $centerName,
                'email' => $email,
                'password' => Hash::make('password'),
                'national_id' => str_pad(29000000000000 + $idx, 14, '0', STR_PAD_LEFT),
                'phone' => '010' . str_pad(10000000 + $idx, 8, '0', STR_PAD_LEFT),
                'job_title' => 'مسؤول المركز',
                'governorate_id' => $governorate?->id,
                'sector_id' => $sector?->id,
                'center_id' => $center?->id,
                'is_active' => true,
            ]);
            $user->assignRole($role);
        }

        // Create form templates for each Excel file type
        $admin = User::where('email', 'admin@kfs.gov.eg')->first();

        // 1. نموذج سجل خطة الصيانة الشهرية
        $this->createMaintenanceTemplate($admin);

        // 2. نموذج بيان المركبات
        $this->createVehicleTemplate($admin);

        // 3. نموذج البيان الاستراتيجي
        $this->createStrategicTemplate($admin);
    }

    private function createMaintenanceTemplate($admin): void
    {
        $template = FormTemplate::create([
            'name' => 'سجل خطة الصيانة الشهرية',
            'description' => 'نموذج تسجيل خطة الصيانة الشهرية للمركبات — تتبع الأعطال الأسبوعية والتنفيذ',
            'is_active' => true,
            'requires_approval' => true,
            'created_by' => $admin?->id,
        ]);

        $fields = [
            ['name' => 'center_name', 'label' => 'اسم المركز', 'type' => 'text', 'is_required' => true, 'sort_order' => 1],
            ['name' => 'vehicle_number', 'label' => 'رقم العربة', 'type' => 'text', 'is_required' => true, 'sort_order' => 2],
            ['name' => 'vehicle_type', 'label' => 'نوع العربة', 'type' => 'text', 'is_required' => true, 'sort_order' => 3],
            ['name' => 'usage_group', 'label' => 'مجموعة الاستخدام', 'type' => 'select', 'options' => ['ركوب', 'نظافة', 'قلاب', 'نقل'], 'is_required' => true, 'sort_order' => 4],
            ['name' => 'week1_planned', 'label' => 'الأسبوع الأول — مخطط', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 5],
            ['name' => 'week1_executed', 'label' => 'الأسبوع الأول — منفذ', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 6],
            ['name' => 'week2_planned', 'label' => 'الأسبوع الثاني — مخطط', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 7],
            ['name' => 'week2_executed', 'label' => 'الأسبوع الثاني — منفذ', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 8],
            ['name' => 'week3_planned', 'label' => 'الأسبوع الثالث — مخطط', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 9],
            ['name' => 'week3_executed', 'label' => 'الأسبوع الثالث — منفذ', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 10],
            ['name' => 'week4_planned', 'label' => 'الأسبوع الرابع — مخطط', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 11],
            ['name' => 'week4_executed', 'label' => 'الأسبوع الرابع — منفذ', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 12],
        ];

        foreach ($fields as $f) {
            FormField::create(array_merge($f, ['form_template_id' => $template->id]));
        }
    }

    private function createVehicleTemplate($admin): void
    {
        $template = FormTemplate::create([
            'name' => 'بيان المركبات',
            'description' => 'نموذج تسجيل بيانات المركبات — النوع وال اللوحة والشاسيه والحالة الفنية',
            'is_active' => true,
            'requires_approval' => false,
            'created_by' => $admin?->id,
        ]);

        $fields = [
            ['name' => 'license_plate', 'label' => 'رقم اللوحة المعدنية', 'type' => 'text', 'is_required' => true, 'sort_order' => 1],
            ['name' => 'vehicle_type', 'label' => 'نوع المركبة', 'type' => 'text', 'is_required' => true, 'sort_order' => 2],
            ['name' => 'model_year', 'label' => 'الموديل', 'type' => 'number', 'is_required' => true, 'sort_order' => 3],
            ['name' => 'color', 'label' => 'اللون', 'type' => 'text', 'is_required' => false, 'sort_order' => 4],
            ['name' => 'chassis_number', 'label' => 'رقم الشاسيه', 'type' => 'text', 'is_required' => false, 'sort_order' => 5],
            ['name' => 'engine_number', 'label' => 'رقم المحرك', 'type' => 'text', 'is_required' => false, 'sort_order' => 6],
            ['name' => 'technical_condition', 'label' => 'الحالة الفنية', 'type' => 'select', 'options' => ['صالح', 'عاطل', 'جسيم', 'مستغني'], 'is_required' => true, 'sort_order' => 7],
            ['name' => 'fuel_type', 'label' => 'نوع الوقود', 'type' => 'select', 'options' => ['بنزين', 'سولار', 'كهرباء', 'مزيج'], 'is_required' => false, 'sort_order' => 8],
            ['name' => 'location', 'label' => 'مكان التواجد', 'type' => 'text', 'is_required' => true, 'sort_order' => 9],
            ['name' => 'affiliated_entity', 'label' => 'الجهة التابعة', 'type' => 'text', 'is_required' => true, 'sort_order' => 10],
            ['name' => 'breakdown_type', 'label' => 'نوع العطل', 'type' => 'text', 'is_required' => false, 'sort_order' => 11],
            ['name' => 'breakdown_date', 'label' => 'تاريخ العطل', 'type' => 'date', 'is_required' => false, 'sort_order' => 12],
            ['name' => 'action_taken', 'label' => 'الإجراء المتخذ', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 13],
        ];

        foreach ($fields as $f) {
            FormField::create(array_merge($f, ['form_template_id' => $template->id]));
        }
    }

    private function createStrategicTemplate($admin): void
    {
        $template = FormTemplate::create([
            'name' => 'البيان الاستراتيجي',
            'description' => 'نموذج تسجيل البيانات الاستراتيجية — المرافق والخدمات والبنية التحتية بالمحافظة',
            'is_active' => true,
            'requires_approval' => true,
            'created_by' => $admin?->id,
        ]);

        $fields = [
            ['name' => 'category', 'label' => 'الفئة', 'type' => 'select', 'options' => ['اطفاء', 'اسعاف', 'مستشفيات', 'شرب وصرف', 'مراكز شباب', 'كنائس', 'مساجد', 'مدارس', 'فنادق', 'مطارات', 'كباري', 'أنفاق', 'دووين حكومية', 'مطاحن', 'محطات كهرباء'], 'is_required' => true, 'sort_order' => 1],
            ['name' => 'name', 'label' => 'الاسم', 'type' => 'text', 'is_required' => true, 'sort_order' => 2],
            ['name' => 'address', 'label' => 'العنوان', 'type' => 'text', 'is_required' => false, 'sort_order' => 3],
            ['name' => 'phone', 'label' => 'التليفون', 'type' => 'text', 'is_required' => false, 'sort_order' => 4],
            ['name' => 'center', 'label' => 'المركز', 'type' => 'text', 'is_required' => true, 'sort_order' => 5],
            ['name' => 'city', 'label' => 'المدينة', 'type' => 'text', 'is_required' => false, 'sort_order' => 6],
            ['name' => 'neighborhood', 'label' => 'الحي', 'type' => 'text', 'is_required' => false, 'sort_order' => 7],
            ['name' => 'type', 'label' => 'النوع', 'type' => 'text', 'is_required' => false, 'sort_order' => 8],
            ['name' => 'x_coord', 'label' => 'خط العرض (X)', 'type' => 'text', 'is_required' => false, 'sort_order' => 9],
            ['name' => 'y_coord', 'label' => 'خط الطول (Y)', 'type' => 'text', 'is_required' => false, 'sort_order' => 10],
        ];

        foreach ($fields as $f) {
            FormField::create(array_merge($f, ['form_template_id' => $template->id]));
        }
    }
}
