<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Division;
use Illuminate\Database\Seeder;

class KfsDivisionsSeeder extends Seeder
{
    public function run(): void
    {
        $divisionsData = [
            'مركز كفر الشيخ العام' => [
                ['name' => 'قسم الباطنية', 'code' => 'KFS-01-INT', 'is_active' => true],
                ['name' => 'قسم الجراحة', 'code' => 'KFS-01-SUR', 'is_active' => true],
                ['name' => 'قسم الأطفال', 'code' => 'KFS-01-PED', 'is_active' => true],
                ['name' => 'قسم الطوارئ', 'code' => 'KFS-01-EMR', 'is_active' => true],
            ],
            'مركز سيدي سالم' => [
                ['name' => 'قسم الباطنية', 'code' => 'KFS-02-INT', 'is_active' => true],
                ['name' => 'قسم الأسنان', 'code' => 'KFS-02-DEN', 'is_active' => true],
            ],
            'مركز دسوق' => [
                ['name' => 'قسم الطوارئ', 'code' => 'KFS-03-EMR', 'is_active' => true],
                ['name' => 'قسم النسائية', 'code' => 'KFS-03-OBG', 'is_active' => true],
            ],
            'إدارة كفر الشيخ التعليمية' => [
                ['name' => 'قسم الإشراف التربوي', 'code' => 'EDU-01-SUP', 'is_active' => true],
                ['name' => 'قسم الامتحانات', 'code' => 'EDU-01-EXM', 'is_active' => true],
            ],
            'مركز كفر الشيخ' => [
                ['name' => 'وحدة الشئون المالية', 'code' => 'LGOV-01-FIN', 'is_active' => true],
                ['name' => 'وحدة شئون الأحوال الشخصية', 'code' => 'LGOV-01-CIV', 'is_active' => true],
            ],
            'مركز دسوق' => [
                ['name' => 'وحدة الشئون المالية', 'code' => 'LGOV-02-FIN', 'is_active' => true],
            ],
            'إدارة التضامن كفر الشيخ' => [
                ['name' => 'قسم الرعاية الاجتماعية', 'code' => 'TSN-01-SOC', 'is_active' => true],
                ['name' => 'قسم الأيتام', 'code' => 'TSN-01-ORP', 'is_active' => true],
            ],
            'مديرية الزراعة كفر الشيخ' => [
                ['name' => 'قسم الإرشاد الزراعي', 'code' => 'AGR-01-EXT', 'is_active' => true],
                ['name' => 'قسم حماية النبات', 'code' => 'AGR-01-PLT', 'is_active' => true],
            ],
        ];

        foreach ($divisionsData as $centerName => $divisions) {
            $center = Center::where('name', $centerName)->first();
            if ($center) {
                foreach ($divisions as $div) {
                    Division::create(array_merge($div, ['center_id' => $center->id]));
                }
            }
        }
    }
}
