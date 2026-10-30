<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * جهة في الهيكل التنظيمي الموحد للمحافظة (شجرة بمسار مادي path = /1/5/23/).
 */
class OrgUnit extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'governorate' => 'محافظة',
        'sector' => 'قطاع',
        'ministry' => 'وزارة / مديرية وزارة',
        'directorate' => 'مديرية',
        'authority' => 'هيئة',
        'company' => 'شركة / جهاز',
        'center' => 'مركز ومدينة',
        'city' => 'مدينة',
        'district' => 'حي',
        'local_unit' => 'وحدة محلية قروية',
        'village' => 'قرية',
        'hamlet' => 'عزبة / كفر / نجع',
        'department' => 'إدارة',
        'section' => 'قسم',
        'office' => 'مكتب / فرع',
    ];

    /** أيقونة Font Awesome لكل نوع */
    public const ICONS = [
        'governorate' => 'fa-landmark',
        'sector' => 'fa-layer-group',
        'ministry' => 'fa-building-columns',
        'directorate' => 'fa-building',
        'authority' => 'fa-scale-balanced',
        'company' => 'fa-industry',
        'center' => 'fa-city',
        'city' => 'fa-city',
        'district' => 'fa-map-pin',
        'local_unit' => 'fa-house-flag',
        'village' => 'fa-house-chimney',
        'hamlet' => 'fa-house',
        'department' => 'fa-briefcase',
        'section' => 'fa-diagram-project',
        'office' => 'fa-door-open',
    ];

    /** أنواع جغرافية (تقسيم إداري) */
    public const GEO_TYPES = ['governorate', 'center', 'city', 'district', 'local_unit', 'village', 'hamlet'];

    protected $fillable = [
        'parent_id', 'type', 'name', 'name_en', 'code', 'description',
        'latitude', 'longitude', 'geometry', 'population', 'area_km2',
        'head_name', 'head_title', 'phone', 'email', 'address', 'meta',
        'sort_order', 'is_active', 'path', 'depth',
        'legacy_sector_id', 'legacy_department_id', 'legacy_center_id', 'legacy_division_id',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'meta' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'population' => 'integer',
            'depth' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (OrgUnit $unit) {
            if ($unit->wasChanged('parent_id') || $unit->wasRecentlyCreated) {
                $unit->refreshPath();
            }
        });
    }

    /* ------------------------------------------------------------------ relations */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgUnit::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(JobPosition::class)->orderBy('sort_order');
    }

    public function formTemplates(): BelongsToMany
    {
        return $this->belongsToMany(FormTemplate::class, 'form_template_org_unit')
            ->withPivot(['include_children', 'due_date'])->withTimestamps();
    }

    /* ------------------------------------------------------------------ tree helpers */

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** الجهة وكل ما تحتها */
    public function scopeSubtreeOf(Builder $q, OrgUnit|int $unit): Builder
    {
        $path = $unit instanceof OrgUnit ? $unit->path : static::whereKey($unit)->value('path');
        return $q->where('path', 'like', ($path ?: '/__none__/') . '%');
    }

    public function descendantIds(bool $withSelf = true): Collection
    {
        $ids = static::where('path', 'like', $this->path . '%')->pluck('id');
        return $withSelf ? $ids : $ids->reject(fn ($id) => $id === $this->id)->values();
    }

    /** معرفات الأسلاف من الجذر حتى الجهة نفسها */
    public function ancestorIds(bool $withSelf = true): array
    {
        $ids = array_map('intval', array_filter(explode('/', $this->path)));
        return $withSelf ? $ids : array_values(array_diff($ids, [$this->id]));
    }

    public function ancestors(): Collection
    {
        $ids = $this->ancestorIds(false);
        if (! $ids) return collect();
        $units = static::whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(fn ($id) => $units[$id] ?? null)->filter()->values();
    }

    public function getBreadcrumbAttribute(): string
    {
        return $this->ancestors()->push($this)->pluck('name')->implode(' / ');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getIconAttribute(): string
    {
        return self::ICONS[$this->type] ?? 'fa-circle';
    }

    /** أقرب مركز إداري (center) أعلى هذه الجهة */
    public function nearestCenter(): ?OrgUnit
    {
        if ($this->type === 'center') return $this;
        return $this->ancestors()->reverse()->firstWhere('type', 'center');
    }

    public function refreshPath(): void
    {
        $parent = $this->parent_id ? static::withTrashed()->find($this->parent_id) : null;
        $path = ($parent?->path ?? '/') . $this->id . '/';
        $depth = $parent ? $parent->depth + 1 : 0;
        $old = $this->getOriginal('path');

        static::withoutEvents(fn () => static::whereKey($this->id)->update(['path' => $path, 'depth' => $depth]));

        // أعد بناء مسارات الأبناء
        if ($old && $old !== '/' && $old !== $path) {
            static::withTrashed()->where('path', 'like', $old . '%')->where('id', '!=', $this->id)->get()
                ->each(function (OrgUnit $u) use ($old, $path) {
                    $newPath = $path . substr($u->path, strlen($old));
                    static::withoutEvents(fn () => static::whereKey($u->id)->update([
                        'path' => $newPath,
                        'depth' => substr_count(trim($newPath, '/'), '/'),
                    ]));
                });
        }
        $this->path = $path;
        $this->depth = $depth;
    }

    public static function root(): ?OrgUnit
    {
        return static::whereNull('parent_id')->where('type', 'governorate')->orderBy('id')->first();
    }

    /** خيارات قائمة منسدلة مرتبة كشجرة: [id => '— — الاسم'] */
    public static function treeOptions(?Collection $only = null, array $types = []): array
    {
        $q = static::query()->orderBy('path');
        if ($types) $q->whereIn('type', $types);
        if ($only) $q->whereIn('id', $only);
        $units = $q->get(['id', 'name', 'depth', 'type', 'path', 'sort_order']);

        // ترتيب حسب الشجرة (الأب ثم أبناؤه)
        $byParent = [];
        foreach ($units as $u) {
            $parts = array_filter(explode('/', $u->path));
            array_pop($parts);
            $byParent[(int) end($parts) ?: 0][] = $u;
        }
        $out = [];
        $ids = $units->pluck('id')->flip();
        $walk = function ($pid) use (&$walk, &$out, $byParent) {
            foreach (collect($byParent[$pid] ?? [])->sortBy([['sort_order', 'asc'], ['name', 'asc']]) as $u) {
                $out[$u->id] = str_repeat('— ', $u->depth) . $u->name;
                $walk($u->id);
            }
        };
        // الجذور: من ليس أبوه ضمن المجموعة
        foreach ($units as $u) {
            $parts = array_filter(explode('/', $u->path));
            array_pop($parts);
            $pid = (int) end($parts);
            if (! $pid || ! isset($ids[$pid])) {
                if (! isset($out[$u->id])) {
                    $out[$u->id] = str_repeat('— ', $u->depth) . $u->name;
                    $walk($u->id);
                }
            }
        }
        return $out;
    }
}
