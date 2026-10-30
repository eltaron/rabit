<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_data', function (Blueprint $table) {
            $table->id();
            $table->string('sector', 30)->comment('القطاع: investment/population/education/supply/health');
            $table->string('file_name')->nullable()->comment('اسم الملف المرفوع');
            $table->string('sheet_name')->nullable()->comment('اسم الشيت');
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->string('center_name')->nullable()->comment('اسم المركز كما ورد بالبيان');
            $table->string('name')->nullable()->comment('الاسم الأساسي للمنشأة/البيان');
            $table->string('subcategory')->nullable()->comment('التصنيف الفرعي (الشيت/النوع)');
            $table->text('address')->nullable()->comment('العنوان');
            $table->decimal('x_coord', 11, 8)->nullable()->comment('الإحداثي X');
            $table->decimal('y_coord', 11, 8)->nullable()->comment('الإحداثي Y');
            $table->string('year_period')->nullable()->comment('السنة/الفترة');
            $table->json('extra_data')->nullable()->comment('باقي الأعمدة كما وردت');
            $table->timestamps();

            $table->index(['sector', 'file_name']);
            $table->index('center_id');
            $table->index('sheet_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_data');
    }
};
