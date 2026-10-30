<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('centers')
            ->where('code', 'LGOV-05')
            ->where('name', 'مركز المحمودية')
            ->update([
                'name' => 'مركز قلين',
                'latitude' => 31.0500,
                'longitude' => 30.8500,
            ]);

        DB::table('centers')
            ->where('code', 'LGOV-06')
            ->where('name', 'مركز فارسكور')
            ->update([
                'name' => 'مركز سيدي غازي',
                'latitude' => 31.0964,
                'longitude' => 30.8567,
            ]);

        $qillin = DB::table('centers')->where('code', 'LGOV-05')->value('id');
        $sidiGhazi = DB::table('centers')->where('code', 'LGOV-06')->value('id');

        if ($qillin) {
            DB::table('users')->where('name', 'مسؤول قلين')->update(['center_id' => $qillin]);
        }
        if ($sidiGhazi) {
            DB::table('users')->where('name', 'مسؤول سيدي غازي')->update(['center_id' => $sidiGhazi]);
        }
    }

    public function down(): void
    {
        DB::table('centers')
            ->where('code', 'LGOV-05')
            ->update(['name' => 'مركز المحمودية', 'latitude' => 31.1838, 'longitude' => 30.5258]);

        DB::table('centers')
            ->where('code', 'LGOV-06')
            ->update(['name' => 'مركز فارسكور', 'latitude' => 31.3274, 'longitude' => 31.7137]);

        $qillin = DB::table('centers')->where('code', 'LGOV-05')->value('id');
        $sidiGhazi = DB::table('centers')->where('code', 'LGOV-06')->value('id');
        $sidiSalem = DB::table('centers')->where('code', 'LGOV-03')->value('id');

        if ($qillin) {
            DB::table('users')->where('name', 'مسؤول قلين')->update(['center_id' => $sidiSalem]);
        }
        if ($sidiGhazi) {
            DB::table('users')->where('name', 'مسؤول سيدي غازي')->update(['center_id' => $sidiSalem]);
        }
    }
};
