<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * المصادقة الثنائية الإجبارية: سر مشفّر، أكواد استرداد، تاريخ التفعيل، ومنع إعادة استخدام الرمز.
 */
return new class extends Migration
{
    public function up(): void
    {
        // أي أسرار غير مكتملة التفعيل تُمسح (ستُشفَّر الأسرار من الآن فصاعداً)
        DB::table('users')->update(['two_factor_secret' => null, 'two_factor_enabled' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->change();
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedBigInteger('two_factor_last_timestamp')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_timestamp']);
        });
    }
};
