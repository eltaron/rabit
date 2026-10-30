<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        $coordinates = [
            'مركز كفر الشيخ' => [31.1086, 30.9381],
            'مركز سيدي سالم' => [31.2671, 30.7781],
            'مركز دسوق' => [31.1277, 30.6475],
            'مركز بيلا' => [31.1616, 31.2019],
            'مركز المحمودية' => [31.1838, 30.5258],
            'مركز الحامول' => [31.3128, 31.1460],
            'مركز بلطيم' => [31.5600, 31.0815],
            'مركز مطوبس' => [31.2860, 30.5270],
            'مركز الرياض' => [31.2392, 30.9461],
            'مركز فارسكور' => [31.3274, 31.7137],
        ];

        foreach ($coordinates as $name => $coord) {
            DB::table('centers')
                ->where('name', $name)
                ->update(['latitude' => $coord[0], 'longitude' => $coord[1]]);
        }

        $kfsCenter = DB::table('centers')->where('name', 'مركز كفر الشيخ')->first();
        $fuwwahExists = DB::table('centers')->where('name', 'مركز فوه')->exists();
        if (!$fuwwahExists && $kfsCenter) {
            DB::table('centers')->insert([
                'department_id' => $kfsCenter->department_id,
                'name' => 'مركز فوه',
                'code' => 'LGOV-11',
                'description' => null,
                'is_active' => 1,
                'latitude' => 31.2015,
                'longitude' => 30.5493,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
