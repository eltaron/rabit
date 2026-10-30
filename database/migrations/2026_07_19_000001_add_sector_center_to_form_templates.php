<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->foreignId('sector_id')->nullable()->after('requires_approval')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('sector_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
            $table->dropForeign(['center_id']);
            $table->dropColumn(['sector_id', 'center_id']);
        });
    }
};
