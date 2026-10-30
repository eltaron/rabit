<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapTemplateSectorsSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'خطة الصيانة الشهرية' => 4,
            'بيان المركبات' => 4,
            'الاطفاء' => 4,
            'الاسعاف' => 1,
            'الشرب والصرف' => 10,
            'مراكز الشباب' => 6,
            'الكنائس' => 3,
            'العشوائيات' => 4,
            'بنزينات' => 4,
            'الايواء' => 4,
            'محطات الكهرباء' => 4,
            'المطاحن' => 4,
            'مساجد' => 3,
            'المطارات' => 4,
            'سفارات' => 4,
            'دواوين حكومية' => 4,
            'منشأت شرطية' => 4,
            'وزارات' => 4,
            'فنادق' => 4,
            'المزارات الاثرية' => 4,
            'المحاكم' => 4,
            'الكباري' => 4,
            'انفاق' => 4,
            'المزارات الترفيهيه' => 4,
            'المدارس' => 2,
        ];

        $sectorByDept = [
            1 => 1, 2 => 1, 3 => 1,
            4 => 2, 5 => 2,
            6 => 3, 7 => 3,
            8 => 4, 9 => 4, 10 => 4,
        ];

        $updated = 0;
        foreach ($mapping as $needle => $departmentId) {
            $templates = FormTemplate::where('name', 'LIKE', "%{$needle}%")->get();
            foreach ($templates as $template) {
                if ($template->department_id !== $departmentId) {
                    $template->department_id = $departmentId;
                    $template->sector_id = $sectorByDept[$departmentId] ?? null;
                    $template->save();
                    $updated++;
                }
            }
        }

        $this->command?->info("Mapped {$updated} templates to departments/sectors.");
    }
}
