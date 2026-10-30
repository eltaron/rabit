<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * توسيع نوع الحقل ليشمل أنواع منشئ النماذج (هاتف، رقم قومي، ملف، إحداثيات …)
 * بدلاً من enum المحدود الذي كان يرفض الحفظ.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE form_fields MODIFY type VARCHAR(30) NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("UPDATE form_fields SET type = 'text' WHERE type NOT IN ('text','textarea','number','email','date','select','multiselect','image','checkbox','radio')");
        DB::statement("ALTER TABLE form_fields MODIFY type ENUM('text','textarea','number','email','date','select','multiselect','image','checkbox','radio') NOT NULL");
    }
};
