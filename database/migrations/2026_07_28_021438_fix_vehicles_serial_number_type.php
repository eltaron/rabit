<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
            $table->string('model_year')->nullable()->change();
            $table->string('manufacture_year')->nullable()->change();
            $table->string('procurement_year')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('serial_number')->nullable()->change();
            $table->integer('model_year')->nullable()->change();
            $table->integer('manufacture_year')->nullable()->change();
            $table->integer('procurement_year')->nullable()->change();
        });
    }
};
