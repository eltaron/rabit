<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * وظيفة في الهيكل الوظيفي (تتبع جهة، ولها رئيس مباشر، وشاغل).
 */
class JobPosition extends Model
{
    public const CATEGORIES = [
        'leadership' => 'قيادية',
        'supervisory' => 'إشرافية',
        'specialist' => 'تخصصية',
        'executive' => 'تنفيذية',
        'support' => 'معاونة',
    ];

    public const GRADES = [
        'ممتازة', 'عالية', 'مدير عام', 'الأولى', 'الثانية', 'الثالثة', 'الرابعة', 'الخامسة', 'السادسة',
    ];

    protected $fillable = [
        'org_unit_id', 'parent_id', 'title', 'code', 'grade', 'category',
        'user_id', 'headcount', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'headcount' => 'integer',
        ];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(JobPosition::class, 'parent_id')->orderBy('sort_order');
    }

    /** الشاغل الرئيسي */
    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** كل المستخدمين المسكنين على هذه الوظيفة */
    public function occupants(): HasMany
    {
        return $this->hasMany(User::class, 'job_position_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
