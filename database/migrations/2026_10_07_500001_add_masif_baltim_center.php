<?php

use App\Models\OrgUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * إضافة مركز ومدينة مصيف بلطيم كمركز إداري مستقل،
 * ونقل «مدينة مصيف بلطيم» وتوابعها من مركز بلطيم إليه.
 */
return new class extends Migration
{
    private const LAT = 31.5930;
    private const LNG = 31.1350;

    public function up(): void
    {
        $legacy = DB::table('centers')->where('code', 'LGOV-12')->first();
        $legacyId = $legacy?->id ?? DB::table('centers')->insertGetId([
            'department_id' => DB::table('centers')->where('code', 'LGOV-08')->value('department_id'),
            'name' => 'مركز مصيف بلطيم',
            'code' => 'LGOV-12',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $root = OrgUnit::root();
        $center = OrgUnit::where('type', 'center')->where('legacy_center_id', $legacyId)->first()
            ?? OrgUnit::create([
                'parent_id' => $root?->id,
                'type' => 'center',
                'name' => 'مركز مصيف بلطيم',
                'name_en' => 'Masif Baltim',
                'code' => 'C-LGOV-12',
                'latitude' => self::LAT,
                'longitude' => self::LNG,
                'sort_order' => 11,
                'legacy_center_id' => $legacyId,
            ]);

        // نقل المدينة (وتوابعها) من مركز بلطيم إلى المركز الجديد
        $city = OrgUnit::where('type', 'city')->where('name', 'like', '%مصيف بلطيم%')->first();
        if ($city && $city->parent_id !== $center->id) {
            $city->parent_id = $center->id;
            $city->latitude ??= self::LAT;
            $city->longitude ??= self::LNG;
            $city->save();
        } elseif (! $city) {
            OrgUnit::create([
                'parent_id' => $center->id,
                'type' => 'city',
                'name' => 'مدينة مصيف بلطيم',
                'latitude' => self::LAT,
                'longitude' => self::LNG,
            ]);
        }
    }

    public function down(): void
    {
        $center = OrgUnit::where('code', 'C-LGOV-12')->first();
        $baltim = OrgUnit::where('code', 'C-LGOV-08')->first();
        if ($center) {
            foreach ($center->children()->get() as $child) {
                $child->parent_id = $baltim?->id;
                $child->save();
            }
            $center->forceDelete();
        }
        DB::table('centers')->where('code', 'LGOV-12')->delete();
    }
};
