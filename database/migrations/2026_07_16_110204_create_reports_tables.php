<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('form_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'sent'])->default('draft');
            $table->json('selected_fields')->nullable();
            $table->json('report_config')->nullable();
            $table->longText('content')->nullable();
            $table->longText('ai_generated_content')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->integer('current_version')->default(1);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->longText('content')->nullable();
            $table->longText('ai_generated_content')->nullable();
            $table->json('selected_fields')->nullable();
            $table->json('report_config')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('change_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('report_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->constrained('users');
            $table->foreignId('sent_to')->constrained('users');
            $table->enum('status', ['sent', 'viewed', 'acknowledged', 'under_review']);
            $table->text('notes')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_submissions');
        Schema::dropIfExists('report_versions');
        Schema::dropIfExists('reports');
    }
};
