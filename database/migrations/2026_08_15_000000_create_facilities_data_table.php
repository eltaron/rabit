<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities_data', function (Blueprint $table) {
            $table->id();
            $table->string('module_code', 50)->comment('رمز النموذج (مثال: MOSQUES)');
            $table->string('module_name')->nullable()->comment('اسم النموذج بالعربية');
            $table->string('file_name')->nullable()->comment('اسم الملف المرفوع');
            $table->string('sheet_name')->nullable()->comment('اسم الشيت');
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->string('center_name')->nullable()->comment('اسم المركز كما ورد بالبيان');
            $table->string('name')->nullable()->comment('اسم المنشأة');
            $table->string('type')->nullable()->comment('نوع الملكية / النوع');
            $table->text('address')->nullable()->comment('العنوان');
            $table->string('phone')->nullable()->comment('التليفون');
            $table->string('governorate')->nullable()->comment('المحافظة');
            $table->string('directorate')->nullable()->comment('المديرية');
            $table->string('city')->nullable()->comment('المدينة');
            $table->string('district')->nullable()->comment('الحي');
            $table->decimal('x_coord', 12, 8)->nullable()->comment('الإحداثي X');
            $table->decimal('y_coord', 12, 8)->nullable()->comment('الإحداثي Y');
            $table->json('extra_data')->nullable()->comment('باقي الأعمدة كما وردت');
            $table->timestamps();

            $table->index(['module_code', 'file_name']);
            $table->index('center_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities_data');
    }
};
