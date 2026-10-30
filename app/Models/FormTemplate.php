<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FormTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (FormTemplate $template) {
            if (empty($template->slug)) {
                $baseSlug = Str::slug($template->name);
                $slug = $baseSlug;
                $counter = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $template->slug = $slug;
            }
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'settings',
        'is_active',
        'requires_approval',
        'sector_id',
        'department_id',
        'center_id',
        'org_unit_id',
        'distribution',
        'geo_lat_field',
        'geo_lng_field',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /** الجهات الموجه إليها النموذج (تداول البيانات) */
    public function targetUnits(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(OrgUnit::class, 'form_template_org_unit')
            ->withPivot(['include_children', 'due_date'])->withTimestamps();
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
