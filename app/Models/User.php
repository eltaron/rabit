<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'national_id',
        'phone',
        'job_title',
        'governorate_id',
        'sector_id',
        'department_id',
        'center_id',
        'division_id',
        'org_unit_id',
        'job_position_id',
        'data_scope',
        'is_active',
        'locale',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /** هل المصادقة الثنائية مفعّلة ومكتملة */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_enabled && ! empty($this->two_factor_secret);
    }

    public function orgUnit()
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    /** دور العرض بالعربية */
    public function getRoleLabelAttribute(): string
    {
        $role = $this->roles->first();
        return $role ? (\App\Support\RoleLabels::label($role->name)) : '—';
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class, 'created_by');
    }

    public function createdFormSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'created_by');
    }

    public function assignedFormSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'assigned_to');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function chatbotMessages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class);
    }
}
