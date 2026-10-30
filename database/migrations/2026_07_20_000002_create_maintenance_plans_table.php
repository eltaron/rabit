<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->integer('serial_number')->nullable();
            $table->string('center_name')->nullable()->comment('اسم المركز/الجهة');
            $table->string('vehicle_number')->nullable()->comment('رقم العربة');
            $table->string('vehicle_type')->nullable()->comment('نوعها');
            $table->string('usage_group')->nullable()->comment('مجموعة الاستخدام');
            $table->boolean('week1_planned')->nullable()->comment('الأسبوع الأول - مخطط');
            $table->boolean('week1_executed')->nullable()->comment('الأسبوع الأول - منفذ');
            $table->boolean('week2_planned')->nullable()->comment('الأسبوع الثاني - مخطط');
            $table->boolean('week2_executed')->nullable()->comment('الأسبوع الثاني - منفذ');
            $table->boolean('week3_planned')->nullable()->comment('الأسبوع الثالث - مخطط');
            $table->boolean('week3_executed')->nullable()->comment('الأسبوع الثالث - منفذ');
            $table->boolean('week4_planned')->nullable()->comment('الأسبوع الرابع - مخطط');
            $table->boolean('week4_executed')->nullable()->comment('الأسبوع الرابع - منفذ');
            $table->integer('month')->nullable()->comment('الشهر');
            $table->integer('year')->nullable()->comment('السنة');
            $table->string('file_name')->nullable()->comment('اسم الملف المرفوع');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_plans');
    }
};
