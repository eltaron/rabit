<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'form_template_id',
        'name',
        'label',
        'type',
        'options',
        'validation_rules',
        'is_required',
        'is_readonly',
        'sort_order',
        'placeholder',
        'help_text',
        'default_value',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'validation_rules' => 'array',
            'is_required' => 'boolean',
            'is_readonly' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function submissionData(): HasMany
    {
        return $this->hasMany(FormSubmissionData::class);
    }
}
