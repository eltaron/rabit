<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id')->unique()->nullable()->after('email');
            $table->string('phone')->nullable()->after('national_id');
            $table->string('job_title')->nullable()->after('phone');
            $table->foreignId('governorate_id')->nullable()->after('job_title')->constrained()->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->after('governorate_id')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('sector_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->after('center_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('division_id');
            $table->boolean('two_factor_enabled')->default(false)->after('is_active');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->string('avatar')->nullable()->after('two_factor_secret');
            $table->timestamp('last_login_at')->nullable()->after('avatar');
            $table->string('locale')->default('ar')->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'national_id', 'phone', 'job_title',
                'governorate_id', 'sector_id', 'department_id', 'center_id', 'division_id',
                'is_active', 'two_factor_enabled', 'two_factor_secret',
                'avatar', 'last_login_at', 'locale',
            ]);
        });
    }
};
