<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->integer('serial_number')->nullable();
            $table->string('entity')->nullable()->comment('الجهة/الوزارة/المحافظة');
            $table->string('affiliated_entity')->nullable()->comment('الجهة التابعة');
            $table->string('license_plate')->nullable()->comment('رقم اللوحة المعدنية');
            $table->string('chassis_number')->nullable()->comment('رقم الشاسيه');
            $table->string('engine_number')->nullable()->comment('رقم المحرك');
            $table->string('vehicle_type')->nullable()->comment('نوع المركبة');
            $table->string('vehicle_type_per_license')->nullable()->comment('النوع طبقا للرخصة');
            $table->string('color')->nullable()->comment('اللون');
            $table->integer('model_year')->nullable()->comment('موديل المركبة');
            $table->integer('manufacture_year')->nullable()->comment('سنة الصنع');
            $table->string('technical_condition')->nullable()->comment('الحالة الفنية');
            $table->string('fuel_type')->nullable()->comment('نوع الوقود');
            $table->string('procurement_type')->nullable()->comment('نوع التدبير');
            $table->string('procurement_source')->nullable()->comment('جهة التدبير');
            $table->integer('procurement_year')->nullable()->comment('سنة التدبير');
            $table->string('odometer_reading')->nullable()->comment('قراءة العداد');
            $table->string('equipment_level')->nullable()->comment('التجهيز');
            $table->string('location')->nullable()->comment('مكان التواجد');
            $table->string('maintenance_contract')->nullable()->comment('عقد صيانة للمركبة');
            $table->string('equipment_maintenance_contract')->nullable()->comment('عقد صيانة للتجهيز');
            $table->string('annual_usage_distance')->nullable()->comment('ماسافات الاستخدام السنوية');
            $table->string('breakdown_type')->nullable()->comment('نوع العطل');
            $table->string('breakdown_date')->nullable()->comment('تاريخ العطل');
            $table->string('action_taken')->nullable()->comment('الإجراء المتخذ');
            $table->string('reason_repair_failed')->nullable()->comment('أسباب تعذر الإصلاح');
            $table->string('entity_need')->nullable()->comment('مدى حاجة الجهة للمركبة');
            $table->string('breakdown_cause')->nullable()->comment('سبب العطل');
            $table->string('breakdown_details')->nullable()->comment('بيان العطل');
            $table->string('reason_surplus')->nullable()->comment('أسباب الاستغناء');
            $table->string('technical_validity')->nullable()->comment('الصلاحية الفنية');
            $table->string('vehicle_photo')->nullable()->comment('صورة المركبة');
            $table->string('status')->nullable()->comment('الحالة: مجمع/صالح/عاطل/جسيم/مستغني');
            $table->string('sheet_name')->nullable()->comment('اسم الشيت');
            $table->string('file_name')->nullable()->comment('اسم الملف المرفوع');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
