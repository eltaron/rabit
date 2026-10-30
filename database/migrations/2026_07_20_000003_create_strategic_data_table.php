<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_data', function (Blueprint $table) {
            $table->id();
            $table->integer('serial_number')->nullable();
            $table->string('category')->nullable()->comment('الفئة/النوع');
            $table->string('name')->nullable()->comment('الاسم');
            $table->string('headquarters')->nullable()->comment('المقر');
            $table->string('address')->nullable()->comment('العنوان');
            $table->string('phone')->nullable()->comment('التليفون');
            $table->string('center')->nullable()->comment('المركز');
            $table->string('city')->nullable()->comment('المدينة');
            $table->string('neighborhood')->nullable()->comment('الحي');
            $table->string('governorate')->nullable()->comment('المحافظة');
            $table->string('type')->nullable()->comment('النوع');
            $table->string('governorate_code')->nullable()->comment('كود المحافظة');
            $table->string('center_code')->nullable()->comment('كود المركز');
            $table->string('city_code')->nullable()->comment('كود المدينة');
            $table->string('x_coord')->nullable()->comment('خط العرض');
            $table->string('y_coord')->nullable()->comment('خط الطول');
            $table->json('additional_data')->nullable()->comment('بيانات إضافية');
            $table->string('sheet_name')->nullable()->comment('اسم الشيت');
            $table->string('file_name')->nullable()->comment('اسم الملف المرفوع');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_data');
    }
};
