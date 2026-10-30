<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'form_submission_id',
        'created_by',
        'approved_by',
        'status',
        'selected_fields',
        'report_config',
        'content',
        'ai_generated_content',
        'is_ai_generated',
        'current_version',
        'approved_at',
        'sent_at',
        'sent_to',
        'rejection_notes',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_fields' => 'array',
            'report_config' => 'array',
            'is_ai_generated' => 'boolean',
            'current_version' => 'integer',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_to');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReportVersion::class);
    }

    public function reportSubmissions(): HasMany
    {
        return $this->hasMany(ReportSubmission::class);
    }
}
