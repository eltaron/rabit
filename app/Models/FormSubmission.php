<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (FormSubmission $s) {
            if (empty($s->org_unit_id) && $s->created_by) {
                $s->org_unit_id = User::whereKey($s->created_by)->value('org_unit_id');
            }
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_number',
        'form_template_id',
        'created_by',
        'assigned_to',
        'org_unit_id',
        'status',
        'current_step',
        'revision_notes',
        'revision_count',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'reviewed_by',
        'approved_by',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'revision_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function submissionData(): HasMany
    {
        return $this->hasMany(FormSubmissionData::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }
}
