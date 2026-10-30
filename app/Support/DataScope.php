<?php

namespace App\Support;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * نطاق البيانات: يحدد ما يراه المستخدم حسب جهته في الهيكل التنظيمي.
 *
 *  - all     : كل بيانات المحافظة (أو صلاحية view_all_data)
 *  - subtree : جهته وكل الجهات التابعة لها (افتراضي القيادات والمديرين)
 *  - unit    : جهته فقط
 *  - own     : ما أنشأه أو كُلّف به فقط
 */
class DataScope
{
    public const LEVELS = [
        'own' => 'بياناته فقط',
        'unit' => 'جهته فقط',
        'subtree' => 'جهته والجهات التابعة',
        'all' => 'كل المحافظة',
    ];

    public static function level(?User $user): string
    {
        if (! $user) return 'own';
        if ($user->can('view_all_data')) return 'all';
        return $user->data_scope ?: 'unit';
    }

    /** null = بلا قيود (كل المحافظة) */
    public static function unitIds(?User $user): ?array
    {
        $level = static::level($user);
        if ($level === 'all') return null;
        if (! $user->org_unit_id) return [];
        if ($level === 'own') return [];

        return Cache::remember("scope.units.{$user->id}.{$level}.{$user->org_unit_id}", 300, function () use ($user, $level) {
            $unit = OrgUnit::find($user->org_unit_id);
            if (! $unit) return [];
            return $level === 'subtree' ? $unit->descendantIds()->all() : [$unit->id];
        });
    }

    /** قيد الإرسالات (FormSubmission) */
    public static function submissions(Builder $q, ?User $user): Builder
    {
        $ids = static::unitIds($user);
        if ($ids === null) return $q;

        return $q->where(function ($w) use ($ids, $user) {
            $w->where('form_submissions.created_by', $user?->id)
              ->orWhere('form_submissions.assigned_to', $user?->id);
            if ($ids) $w->orWhereIn('form_submissions.org_unit_id', $ids);
        });
    }

    /** النماذج المتاحة للمستخدم: العامة، أو الموجهة لجهته/أسلافها، أو التي تملكها جهته */
    public static function templates(Builder $q, ?User $user): Builder
    {
        if (static::level($user) === 'all') return $q;

        $unit = $user?->org_unit_id ? OrgUnit::find($user->org_unit_id) : null;
        $ancestors = $unit ? $unit->ancestorIds() : [];
        $own = static::unitIds($user) ?? [];

        return $q->where(function ($w) use ($ancestors, $own, $unit) {
            $w->where('form_templates.distribution', 'all')
              ->orWhereIn('form_templates.org_unit_id', $own ?: [0]);
            if ($unit) {
                $w->orWhereHas('targetUnits', function ($t) use ($ancestors, $unit) {
                    $t->where(function ($tt) use ($ancestors, $unit) {
                        $tt->where('org_units.id', $unit->id)
                           ->orWhere(fn ($x) => $x->whereIn('org_units.id', $ancestors)->where('form_template_org_unit.include_children', true));
                    });
                });
            }
        });
    }

    /** قيد عام لأي جدول به org_unit_id (بيانات مكانية/قطاعية) */
    public static function byUnit(Builder $q, ?User $user, string $column = 'org_unit_id'): Builder
    {
        $ids = static::unitIds($user);
        if ($ids === null) return $q;
        return $q->whereIn($column, $ids ?: [0]);
    }

    public static function canSeeSubmission(?User $user, FormSubmission $s): bool
    {
        $ids = static::unitIds($user);
        if ($ids === null) return true;
        return $s->created_by === $user?->id || $s->assigned_to === $user?->id || in_array($s->org_unit_id, $ids, true);
    }

    public static function canUseTemplate(?User $user, FormTemplate $t): bool
    {
        return static::templates(FormTemplate::whereKey($t->id), $user)->exists();
    }

    public static function forget(User $user): void
    {
        foreach (array_keys(self::LEVELS) as $lvl) {
            Cache::forget("scope.units.{$user->id}.{$lvl}.{$user->org_unit_id}");
        }
    }
}
