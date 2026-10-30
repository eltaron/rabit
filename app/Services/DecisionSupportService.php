<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\OrgUnit;
use App\Models\User;
use App\Support\DataScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * دعم اتخاذ القرار للقيادات:
 *   البيانات → المعلومات (مؤشرات) → المعرفة (استنتاجات) → القرار (توصيات + مساعد ذكي).
 *
 * كل الأرقام محسوبة من الجداول الفعلية ومقيدة بنطاق بيانات المستخدم (DataScope).
 */
class DecisionSupportService
{
    /** مؤشرات المقارنة بين المراكز: key => [label, unit, higher_is_better] */
    public const INDICATORS = [
        'population' => ['عدد السكان', 'نسمة', null],
        'schools_per100k' => ['المدارس لكل 100 ألف نسمة', 'مدرسة', true],
        'health_per100k' => ['المنشآت الصحية لكل 100 ألف نسمة', 'منشأة', true],
        'bakeries_per100k' => ['المخابز لكل 100 ألف نسمة', 'مخبز', true],
        'outlets_per100k' => ['منافذ السلع التموينية لكل 100 ألف نسمة', 'منفذ', true],
        'youth_per100k' => ['مراكز الشباب لكل 100 ألف نسمة', 'مركز', true],
        'unfit_buildings' => ['مبانٍ مدرسية آيلة للسقوط', 'مبنى', false],
        'vehicles_broken_pct' => ['نسبة المركبات المعطلة', '%', false],
        'maintenance_rate' => ['نسبة تنفيذ خطة الصيانة', '%', true],
        'submissions_pending' => ['إرسالات تنتظر المراجعة', 'إرسال', false],
        'records' => ['إجمالي السجلات المكانية والوصفية', 'سجل', true],
    ];

    public const SEVERITY = [
        'critical' => ['حرج', 4],
        'warning' => ['تنبيه', 3],
        'info' => ['للعلم', 2],
        'positive' => ['إيجابي', 1],
    ];

    public function __construct(protected AiService $ai) {}

    /* =====================================================================
     |  BRIEF
     * ===================================================================== */

    public function brief(User $user, bool $fresh = false): array
    {
        [$centerIds, $scopeNote] = $this->scopeCenters($user);
        $unitIds = DataScope::unitIds($user);
        $key = 'decision.brief.' . md5(json_encode([$centerIds, $unitIds, $user->id, DataScope::level($user)]));

        if ($fresh) Cache::forget($key);

        return Cache::remember($key, 300, function () use ($user, $centerIds, $scopeNote) {
            $brief = $this->build($user, $centerIds);
            $brief['scope_note'] = $scopeNote;
            $brief['insights'] = $this->insights($brief);
            return $brief;
        });
    }

    /** المراكز ضمن نطاق المستخدم: [ids|null, ملاحظة النطاق] */
    protected function scopeCenters(User $user): array
    {
        $ids = DataScope::unitIds($user);
        if ($ids === null) return [null, 'كل محافظة كفر الشيخ'];

        $centers = OrgUnit::where('type', 'center')->whereIn('id', $ids ?: [0])->pluck('id')->all();
        $unit = $user->org_unit_id ? OrgUnit::find($user->org_unit_id) : null;
        if ($unit && ($c = $unit->nearestCenter())) $centers[] = $c->id;
        $centers = array_values(array_unique($centers));

        if ($centers) {
            $names = OrgUnit::whereIn('id', $centers)->pluck('name')->implode('، ');
            return [$centers, 'نطاق: ' . $names];
        }

        return [null, 'بيانات مرجعية على مستوى المحافظة — الإرسالات مقيدة بجهة: ' . ($unit->name ?? '—')];
    }

    protected function build(User $user, ?array $centerIds): array
    {
        $centers = OrgUnit::where('type', 'center')
            ->when($centerIds, fn ($q) => $q->whereIn('id', $centerIds))
            ->orderBy('sort_order')->orderBy('name')->get();

        $ids = $centers->pluck('id')->all();
        $inCenters = fn ($q, $col = 'org_unit_id') => $centerIds ? $q->whereIn($col, $ids) : $q;

        // ---------- administrative / population ----------
        $adminRows = DB::table('sector_data')->where('sector', 'population')
            ->where('subcategory', 'like', 'التقسيم الإدار%')->whereNotNull('org_unit_id')->get(['org_unit_id', 'extra_data']);
        $admin = [];
        foreach ($adminRows as $r) {
            $x = json_decode($r->extra_data ?? '[]', true) ?: [];
            $admin[$r->org_unit_id] = [
                'population' => $this->num($this->pick($x, 'عدد السكان')),
                'cities' => $this->num($this->pick($x, 'عدد المدن')),
                'local_units' => $this->num($this->pick($x, 'الوحدات المحلية')),
                'villages' => $this->num($this->pick($x, 'القرى التوابع')),
                'hamlets' => $this->num($this->pick($x, 'كفور')),
                'known_for' => $this->pick($x, 'شهرة'),
            ];
        }

        // org_units sub-counts (in case the administrative structure was imported)
        $geoCounts = [];
        foreach ($centers as $c) {
            $geoCounts[$c->id] = OrgUnit::where('path', 'like', $c->path . '%')->whereIn('type', ['city', 'district', 'local_unit', 'village', 'hamlet'])
                ->selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type')->all();
        }

        // ---------- sector & facility counts per center ----------
        $sec = fn (string $sub) => $inCenters(DB::table('sector_data')->where('subcategory', $sub))
            ->whereNotNull('org_unit_id')->selectRaw('org_unit_id, count(*) c')->groupBy('org_unit_id')->pluck('c', 'org_unit_id');
        $fac = fn (string $mod) => $inCenters(DB::table('facilities_data')->where('module_code', $mod))
            ->whereNotNull('org_unit_id')->selectRaw('org_unit_id, count(*) c')->groupBy('org_unit_id')->pluck('c', 'org_unit_id');

        $schools = $sec('بيان المدارس');
        $health = $sec('المستشفيات والوحدات الصحية');
        $hospitals = $fac('HOSPITALS');
        $bakeries = $sec('المخابز');
        $outlets = $sec('منافذ بيع السلع التموينية');
        $youth = $fac('YOUTH_CENTERS');
        $water = $fac('WATER_STATIONS');
        $power = $fac('POWER_STATIONS');
        $shelters = $fac('SHELTER_CENTERS');

        $unfitRows = DB::table('sector_data')->where('subcategory', 'like', '%ايله للسقوط%')->get(['org_unit_id', 'extra_data']);
        $unfit = [];
        foreach ($unfitRows as $r) {
            $cid = $r->org_unit_id ?: $this->centerIdByName($centers, $this->pick(json_decode($r->extra_data ?? '[]', true) ?: [], 'المركز'));
            if ($cid) $unfit[$cid] = ($unfit[$cid] ?? 0) + 1;
        }

        $recordsSec = $inCenters(DB::table('sector_data'))->whereNotNull('org_unit_id')->selectRaw('org_unit_id, count(*) c')->groupBy('org_unit_id')->pluck('c', 'org_unit_id');
        $recordsFac = $inCenters(DB::table('facilities_data'))->whereNotNull('org_unit_id')->selectRaw('org_unit_id, count(*) c')->groupBy('org_unit_id')->pluck('c', 'org_unit_id');

        // ---------- vehicles (matched to centers by location text) ----------
        $vehicles = $this->vehicles($centers);
        $maintenance = $this->maintenance($centers);

        // ---------- forms / submissions ----------
        $subsBase = fn () => DataScope::submissions(FormSubmission::query(), $user);
        $byStatus = $subsBase()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all();
        $subsByUnit = $subsBase()->whereNotNull('org_unit_id')->selectRaw('org_unit_id, status, count(*) c')->groupBy('org_unit_id', 'status')->get();
        $unitPaths = OrgUnit::whereIn('id', $subsByUnit->pluck('org_unit_id')->unique())->pluck('path', 'id');

        $centerSubs = [];
        foreach ($centers as $c) {
            $agg = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
            foreach ($subsByUnit as $row) {
                if (! str_starts_with($unitPaths[$row->org_unit_id] ?? '', $c->path)) continue;
                $agg['total'] += $row->c;
                if (in_array($row->status, ['approved', 'report_approved', 'report_sent'])) $agg['approved'] += $row->c;
                if (in_array($row->status, ['under_review', 'revision_required'])) $agg['pending'] += $row->c;
                if ($row->status === 'rejected') $agg['rejected'] += $row->c;
            }
            $centerSubs[$c->id] = $agg;
        }

        $staleReview = $subsBase()->where('status', 'under_review')
            ->where(fn ($q) => $q->where('submitted_at', '<', now()->subDays(7))->orWhere(fn ($w) => $w->whereNull('submitted_at')->where('updated_at', '<', now()->subDays(7))))
            ->count();

        $coverage = $this->formsCoverage($user);

        // ---------- per-center rows ----------
        $rows = [];
        foreach ($centers as $c) {
            $a = $admin[$c->id] ?? [];
            $pop = $c->population ?: ($a['population'] ?? null);
            $per = fn ($n) => $pop ? round(($n / $pop) * 100000, 1) : null;
            $h = (int) ($health[$c->id] ?? 0) + (int) ($hospitals[$c->id] ?? 0);
            $g = $geoCounts[$c->id] ?? [];
            $v = $vehicles['by_center'][$c->id] ?? ['total' => 0, 'broken' => 0];
            $m = $maintenance['by_center'][$c->id] ?? null;

            $rows[$c->id] = [
                'id' => $c->id,
                'name' => $c->name,
                'short' => $this->shortName($c->name),
                'population' => $pop,
                'cities' => $g['city'] ?? ($a['cities'] ?? null),
                'local_units' => $g['local_unit'] ?? ($a['local_units'] ?? null),
                'villages' => ($g['village'] ?? 0) ?: ($a['villages'] ?? null),
                'hamlets' => ($g['hamlet'] ?? 0) ?: ($a['hamlets'] ?? null),
                'known_for' => $a['known_for'] ?? null,
                'schools' => (int) ($schools[$c->id] ?? 0),
                'health' => $h,
                'bakeries' => (int) ($bakeries[$c->id] ?? 0),
                'outlets' => (int) ($outlets[$c->id] ?? 0),
                'youth' => (int) ($youth[$c->id] ?? 0),
                'water_stations' => (int) ($water[$c->id] ?? 0),
                'power_stations' => (int) ($power[$c->id] ?? 0),
                'shelters' => (int) ($shelters[$c->id] ?? 0),
                'unfit_buildings' => (int) ($unfit[$c->id] ?? 0),
                'schools_per100k' => $per((int) ($schools[$c->id] ?? 0)),
                'health_per100k' => $per($h),
                'bakeries_per100k' => $per((int) ($bakeries[$c->id] ?? 0)),
                'outlets_per100k' => $per((int) ($outlets[$c->id] ?? 0)),
                'youth_per100k' => $per((int) ($youth[$c->id] ?? 0)),
                'vehicles' => $v['total'],
                'vehicles_broken' => $v['broken'],
                'vehicles_broken_pct' => $v['total'] ? round($v['broken'] / $v['total'] * 100, 1) : null,
                'maintenance_rate' => $m ? $m['rate'] : null,
                'submissions' => $centerSubs[$c->id]['total'],
                'submissions_approved' => $centerSubs[$c->id]['approved'],
                'submissions_pending' => $centerSubs[$c->id]['pending'],
                'records' => (int) ($recordsSec[$c->id] ?? 0) + (int) ($recordsFac[$c->id] ?? 0),
            ];
        }

        // ---------- sector summaries ----------
        $sectorTotals = $inCenters(DB::table('sector_data'))->selectRaw('sector, count(*) c')->groupBy('sector')->pluck('c', 'sector')->all();
        $facTotals = $inCenters(DB::table('facilities_data'))->selectRaw('module_name, count(*) c')->groupBy('module_name')->orderByDesc('c')->pluck('c', 'module_name')->all();
        $investment = $inCenters(DB::table('sector_data')->where('sector', 'investment'))->selectRaw('subcategory, count(*) c')->groupBy('subcategory')->pluck('c', 'subcategory')->all();

        $unassigned = [
            'sector' => DB::table('sector_data')->whereNull('org_unit_id')->count(),
            'facilities' => DB::table('facilities_data')->whereNull('org_unit_id')->count(),
            'total_sector' => DB::table('sector_data')->count(),
            'total_facilities' => DB::table('facilities_data')->count(),
            'geo_facilities' => DB::table('facilities_data')->where(fn ($q) => $q->whereNotNull('latitude')->orWhereNotNull('x_coord'))->count(),
        ];

        $totalPop = collect($rows)->sum('population');

        return [
            'generated_at' => now()->toIso8601String(),
            'generated_label' => now()->locale('ar')->translatedFormat('l j F Y — h:i a'),
            'kpis' => [
                'population' => $totalPop ?: null,
                'centers' => count($rows),
                'local_units' => collect($rows)->sum('local_units'),
                'villages' => collect($rows)->sum('villages'),
                'hamlets' => collect($rows)->sum('hamlets'),
                'records' => array_sum($sectorTotals) + array_sum($facTotals),
                'facilities' => array_sum($facTotals),
                'schools' => collect($rows)->sum('schools'),
                'health' => collect($rows)->sum('health'),
                'vehicles' => $vehicles['total'],
                'vehicles_broken' => $vehicles['broken'],
                'maintenance_rate' => $maintenance['rate'],
                'templates' => DataScope::templates(\App\Models\FormTemplate::query(), $user)->where('is_active', true)->count(),
                'submissions' => array_sum($byStatus),
                'pending_review' => ($byStatus['under_review'] ?? 0) + ($byStatus['revision_required'] ?? 0),
                'overdue_targets' => $coverage['overdue'],
                'active_users' => User::where('is_active', true)->when($centerIds, fn ($q) => $q->whereIn('org_unit_id', OrgUnit::where(function ($w) use ($centers) {
                    foreach ($centers as $c) $w->orWhere('path', 'like', $c->path . '%');
                })->pluck('id')))->count(),
                'active_users_30d' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            ],
            'centers' => array_values($rows),
            'sectors' => $sectorTotals,
            'facilities' => $facTotals,
            'investment' => $investment,
            'vehicles' => $vehicles,
            'maintenance' => $maintenance,
            'forms' => [
                'by_status' => $byStatus,
                'stale_review' => $staleReview,
                'coverage' => $coverage,
                'approval_rate' => array_sum($byStatus) ? round((($byStatus['approved'] ?? 0) + ($byStatus['report_approved'] ?? 0) + ($byStatus['report_sent'] ?? 0)) / array_sum($byStatus) * 100, 1) : null,
            ],
            'data_quality' => $unassigned,
            'freshness' => $this->freshness(),
        ];
    }

    protected function vehicles($centers): array
    {
        $rows = DB::table('vehicles')->selectRaw('location, status, count(*) c')->groupBy('location', 'status')->get();
        $byStatus = [];
        $byCenter = [];
        $byLocation = [];
        $total = 0; $broken = 0;
        foreach ($rows as $r) {
            $isBroken = in_array(trim((string) $r->status), ['عاطل', 'جسيم'], true);
            $byStatus[$r->status ?: 'غير محدد'] = ($byStatus[$r->status ?: 'غير محدد'] ?? 0) + $r->c;
            $total += $r->c; if ($isBroken) $broken += $r->c;
            $loc = trim((string) $r->location) ?: 'غير محدد';
            $byLocation[$loc] ??= ['total' => 0, 'broken' => 0];
            $byLocation[$loc]['total'] += $r->c;
            if ($isBroken) $byLocation[$loc]['broken'] += $r->c;
            if ($cid = $this->centerIdByName($centers, $loc)) {
                $byCenter[$cid] ??= ['total' => 0, 'broken' => 0];
                $byCenter[$cid]['total'] += $r->c;
                if ($isBroken) $byCenter[$cid]['broken'] += $r->c;
            }
        }
        uasort($byLocation, fn ($a, $b) => $b['broken'] <=> $a['broken'] ?: $b['total'] <=> $a['total']);
        arsort($byStatus);

        return [
            'total' => $total,
            'broken' => $broken,
            'broken_pct' => $total ? round($broken / $total * 100, 1) : null,
            'by_status' => $byStatus,
            'top_broken_locations' => array_slice(array_filter($byLocation, fn ($l) => $l['broken'] > 0), 0, 6, true),
            'by_center' => $byCenter,
        ];
    }

    protected function maintenance($centers): array
    {
        $isMark = fn ($v) => (bool) preg_match('/[√ü✓]/u', (string) $v);
        $plans = DB::table('maintenance_plans')->get();
        $planned = 0; $executed = 0; $byCenter = []; $byEntity = [];
        foreach ($plans as $p) {
            $pl = 0; $ex = 0;
            foreach ([1, 2, 3, 4] as $w) {
                if ($isMark($p->{"week{$w}_planned"})) { $pl++; if ($isMark($p->{"week{$w}_executed"})) $ex++; }
            }
            if (! $pl) continue;
            $planned += $pl; $executed += $ex;
            $name = trim((string) $p->center_name) ?: 'غير محدد';
            $byEntity[$name] ??= ['planned' => 0, 'executed' => 0];
            $byEntity[$name]['planned'] += $pl; $byEntity[$name]['executed'] += $ex;
            if ($cid = $this->centerIdByName($centers, $name)) {
                $byCenter[$cid] ??= ['planned' => 0, 'executed' => 0];
                $byCenter[$cid]['planned'] += $pl; $byCenter[$cid]['executed'] += $ex;
            }
        }
        foreach ($byCenter as &$c) $c['rate'] = $c['planned'] ? round($c['executed'] / $c['planned'] * 100, 1) : null;
        unset($c);
        foreach ($byEntity as &$e) $e['rate'] = $e['planned'] ? round($e['executed'] / $e['planned'] * 100, 1) : null;
        unset($e);
        uasort($byEntity, fn ($a, $b) => ($a['rate'] ?? 101) <=> ($b['rate'] ?? 101));

        return [
            'vehicles_in_plan' => $plans->count(),
            'planned' => $planned,
            'executed' => $executed,
            'rate' => $planned ? round($executed / $planned * 100, 1) : null,
            'by_center' => $byCenter,
            'by_entity' => $byEntity,
        ];
    }

    /** تغطية النماذج الموجهة للجهات + المتأخر عن موعده */
    protected function formsCoverage(User $user): array
    {
        $targets = DB::table('form_template_org_unit as t')
            ->join('form_templates as f', 'f.id', '=', 't.form_template_id')
            ->join('org_units as o', 'o.id', '=', 't.org_unit_id')
            ->whereNull('f.deleted_at')->where('f.is_active', true)
            ->get(['t.form_template_id', 't.org_unit_id', 't.include_children', 't.due_date', 'f.name as template', 'o.name as unit', 'o.path']);

        $scopeIds = DataScope::unitIds($user);
        $done = 0; $overdue = 0; $overdueList = [];
        foreach ($targets as $t) {
            if ($scopeIds !== null && ! in_array($t->org_unit_id, $scopeIds)) continue;
            $q = FormSubmission::where('form_template_id', $t->form_template_id)->whereNotIn('status', ['draft']);
            $t->include_children
                ? $q->whereIn('org_unit_id', OrgUnit::where('path', 'like', $t->path . '%')->pluck('id'))
                : $q->where('org_unit_id', $t->org_unit_id);
            if ($q->exists()) { $done++; continue; }
            if ($t->due_date && Carbon::parse($t->due_date)->isPast()) {
                $overdue++;
                $overdueList[] = ['template' => $t->template, 'unit' => $t->unit, 'due_date' => $t->due_date, 'days_late' => (int) Carbon::parse($t->due_date)->diffInDays(now())];
            }
        }
        $total = $scopeIds === null ? $targets->count() : $targets->whereIn('org_unit_id', $scopeIds)->count();
        usort($overdueList, fn ($a, $b) => $b['days_late'] <=> $a['days_late']);

        return [
            'targets' => $total,
            'responded' => $done,
            'rate' => $total ? round($done / $total * 100, 1) : null,
            'overdue' => $overdue,
            'overdue_list' => array_slice($overdueList, 0, 15),
        ];
    }

    protected function freshness(): array
    {
        $src = [
            'قاعدة بيانات التعليم' => DB::table('sector_data')->where('sector', 'education')->max('updated_at'),
            'قاعدة بيانات الصحة' => DB::table('sector_data')->where('sector', 'health')->max('updated_at'),
            'قاعدة بيانات التموين' => DB::table('sector_data')->where('sector', 'supply')->max('updated_at'),
            'قاعدة بيانات الاستثمار' => DB::table('sector_data')->where('sector', 'investment')->max('updated_at'),
            'قاعدة بيانات السكان' => DB::table('sector_data')->where('sector', 'population')->max('updated_at'),
            'نوتة المعلومات (المنشآت)' => DB::table('facilities_data')->max('updated_at'),
            'بيان المركبات' => DB::table('vehicles')->max('updated_at'),
            'خطة الصيانة' => DB::table('maintenance_plans')->max('updated_at'),
            'بيانات القيادة الاستراتيجية' => DB::table('strategic_data')->max('updated_at'),
            'إرسالات النماذج' => DB::table('form_submissions')->max('updated_at'),
        ];
        $out = [];
        foreach ($src as $name => $ts) {
            $out[] = [
                'source' => $name,
                'updated_at' => $ts,
                'days' => $ts ? (int) Carbon::parse($ts)->diffInDays(now()) : null,
                'label' => $ts ? Carbon::parse($ts)->locale('ar')->diffForHumans() : 'لا توجد بيانات',
            ];
        }
        usort($out, fn ($a, $b) => ($b['days'] ?? 9999) <=> ($a['days'] ?? 9999));
        return $out;
    }

    /* =====================================================================
     |  INSIGHTS (knowledge)
     * ===================================================================== */

    public function insights(array $b): array
    {
        $out = [];
        $add = function (string $sev, string $area, string $title, string $detail, array $evidence, string $rec, ?string $center = null) use (&$out) {
            $out[] = compact('sev', 'area', 'title', 'detail', 'evidence', 'rec', 'center') + [
                'severity' => $sev,
                'severity_label' => self::SEVERITY[$sev][0],
                'weight' => self::SEVERITY[$sev][1],
            ];
        };
        $centers = collect($b['centers']);
        $fmt = fn ($n, $d = 0) => $n === null ? '—' : number_format((float) $n, $d);

        // 1) فجوات الخدمات لكل 100 ألف نسمة
        $gapChecks = [
            'schools_per100k' => ['التعليم', 'المدارس'],
            'health_per100k' => ['الصحة', 'المنشآت الصحية'],
            'bakeries_per100k' => ['التموين', 'المخابز'],
            'outlets_per100k' => ['التموين', 'منافذ السلع التموينية'],
            'youth_per100k' => ['الشباب', 'مراكز الشباب'],
        ];
        foreach ($gapChecks as $key => [$area, $label]) {
            $vals = $centers->filter(fn ($c) => $c[$key] !== null && $c[$key] > 0);
            if ($vals->count() < 3) continue;
            $avg = $vals->avg($key);

            // تفاوت شديد (أكثر من 15 ضعفاً) أو مراكز بلا سجلات = بيانات غير مكتملة وليست فجوة خدمية
            $missing = $centers->filter(fn ($c) => $c['population'] && ! $c[$key])->count();
            if ($vals->max($key) / max($vals->min($key), 0.01) > 15 || $missing >= 3) {
                $add('info', 'جودة البيانات', "بيانات {$label} غير مكتملة على مستوى المراكز",
                    "التفاوت بين المراكز كبير جداً ({$fmt($vals->min($key), 1)} إلى {$fmt($vals->max($key), 1)} لكل 100 ألف نسمة)، و{$missing} مركز بلا سجلات؛ لا يصلح المؤشر للمقارنة قبل استكمال البيانات.",
                    $centers->sortBy($key)->take(4)->mapWithKeys(fn ($c) => [$c['short'] => $fmt($c[$key], 1)])->all(),
                    "توجيه نموذج حصر {$label} للمديرية المختصة لاستكمال بيانات كل المراكز قبل اتخاذ قرارات توزيع.");
                continue;
            }
            $low = $vals->filter(fn ($c) => $c[$key] < $avg * 0.7)->sortBy($key);
            if ($low->isNotEmpty()) {
                $worst = $low->first();
                $gap = $worst['population'] ? (int) ceil(($avg - $worst[$key]) * $worst['population'] / 100000) : null;
                $add($low->count() >= 3 ? 'critical' : 'warning', $area,
                    "فجوة في {$label} بـ" . $low->pluck('short')->implode(' و'),
                    "معدل {$label} لكل 100 ألف نسمة أقل من 70% من متوسط المراكز ({$fmt($avg, 1)}).",
                    $low->take(4)->mapWithKeys(fn ($c) => [$c['short'] => $fmt($c[$key], 1)])->all(),
                    $gap ? "إعطاء أولوية لـ{$worst['short']} في خطة الاستثمار القادمة؛ الوصول للمتوسط يتطلب نحو {$fmt($gap)} وحدة إضافية من {$label}، مع التحقق أولاً من اكتمال بيانات المركز." : "مراجعة اكتمال البيانات ثم إدراج المراكز المتأخرة في خطة التوزيع.",
                    $worst['name']);
            }
            $best = $vals->sortByDesc($key)->first();
            if ($best && $best[$key] > $avg * 1.3 && $key === 'schools_per100k') {
                $add('positive', $area, "{$best['short']} الأعلى تغطية في {$label}", "يتجاوز متوسط المراكز بنسبة " . $fmt(($best[$key] / $avg - 1) * 100) . '%.',
                    [$best['short'] => $fmt($best[$key], 1), 'المتوسط' => $fmt($avg, 1)], 'يمكن الاستفادة من نموذج التوزيع في هذا المركز عند تخطيط المراكز الأقل تغطية.', $best['name']);
            }
        }

        // 2) مبانٍ مدرسية آيلة للسقوط
        $unfit = $centers->sum('unfit_buildings');
        if ($unfit > 0) {
            $top = $centers->where('unfit_buildings', '>', 0)->sortByDesc('unfit_buildings');
            $add('critical', 'التعليم', "{$fmt($unfit)} مبنى مدرسي آيل للسقوط",
                'مبانٍ مسجلة بحالة "آيلة للسقوط" في قاعدة بيانات الأبنية التعليمية — تمثل خطراً مباشراً على سلامة الطلاب.',
                $top->take(5)->mapWithKeys(fn ($c) => [$c['short'] => $c['unfit_buildings']])->all(),
                'تشكيل لجنة عاجلة مع هيئة الأبنية التعليمية لحسم قرارات الإزالة والإحلال وتوفير بدائل مؤقتة للطلاب قبل بدء الفصل الدراسي.',
                $top->first()['name'] ?? null);
        }

        // 3) المركبات
        $v = $b['vehicles'];
        if ($v['total']) {
            $sev = $v['broken_pct'] >= 10 ? 'critical' : ($v['broken_pct'] >= 5 ? 'warning' : 'positive');
            $locs = collect($v['top_broken_locations'])->take(4)->map(fn ($l) => $l['broken'] . ' معطلة')->all();
            $add($sev, 'المركبات والمعدات', $sev === 'positive' ? 'أسطول المركبات في حالة جيدة' : "{$fmt($v['broken'])} مركبة معطلة من {$fmt($v['total'])}",
                "نسبة الأعطال (عاطل + جسيم) {$fmt($v['broken_pct'], 1)}% من الأسطول. المستغنى عنها: " . $fmt($v['by_status']['مستغني'] ?? 0) . '.',
                $locs ?: ['إجمالي' => $fmt($v['total'])],
                $sev === 'positive' ? 'الاستمرار في تنفيذ خطط الصيانة الوقائية.' : 'توجيه اعتمادات الإصلاح للجهات الأعلى أعطالاً، وطرح المركبات المستغنى عنها للبيع بالمزاد لتمويل الإحلال.');
        }

        // 4) الصيانة
        $m = $b['maintenance'];
        if ($m['rate'] !== null) {
            $sev = $m['rate'] < 60 ? 'critical' : ($m['rate'] < 85 ? 'warning' : 'positive');
            $weak = collect($m['by_entity'])->filter(fn ($e) => $e['rate'] !== null && $e['rate'] < 85)->take(4)->map(fn ($e) => $e['rate'] . '%')->all();
            $add($sev, 'الصيانة', "نسبة تنفيذ خطة الصيانة الشهرية {$fmt($m['rate'], 1)}%",
                "نُفذ {$fmt($m['executed'])} من {$fmt($m['planned'])} بنداً مخططاً على {$fmt($m['vehicles_in_plan'])} مركبة.",
                $weak ?: ['المخطط' => $fmt($m['planned']), 'المنفذ' => $fmt($m['executed'])],
                $sev === 'positive' ? 'الحفاظ على معدل التنفيذ ومتابعته شهرياً.' : 'إلزام الجهات المتأخرة بتقرير أسبوعي عن أسباب عدم التنفيذ وربط صرف قطع الغيار بالالتزام بالخطة.');
        }

        // 5) النماذج: المتأخر والمعلّق
        $f = $b['forms'];
        if ($f['coverage']['overdue'] > 0) {
            $add('critical', 'تداول البيانات', "{$f['coverage']['overdue']} جهة متأخرة عن تسليم نماذج موجهة",
                'نماذج تم توجيهها للجهات وانقضى موعد التسليم دون أي إرسال.',
                collect($f['coverage']['overdue_list'])->take(4)->mapWithKeys(fn ($o) => [$o['unit'] => $o['days_late'] . ' يوم'])->all(),
                'إرسال إخطار رسمي للجهات المتأخرة ومتابعة التسليم خلال 48 ساعة، وإدراج الالتزام ضمن تقييم الأداء.');
        }
        if (($f['stale_review'] ?? 0) > 0) {
            $add('warning', 'تداول البيانات', "{$f['stale_review']} إرسال ينتظر المراجعة منذ أكثر من 7 أيام",
                'تأخر المراجعة يعطّل الاعتماد ويؤخر وصول البيانات المحدثة للقيادات.',
                ['قيد المراجعة' => $fmt($f['by_status']['under_review'] ?? 0), 'متأخر' => $fmt($f['stale_review'])],
                'تحديد مهلة مراجعة لا تتجاوز 3 أيام عمل وتوزيع الإرسالات على المراجعين بالتساوي.');
        }
        if ($f['coverage']['targets'] === 0) {
            $add('info', 'تداول البيانات', 'لم يتم توجيه نماذج للجهات بعد',
                'لا توجد نماذج موجهة لجهات بعينها بمواعيد تسليم؛ لذا لا يمكن قياس الالتزام.',
                ['النماذج النشطة' => $fmt($b['kpis']['templates'])],
                'توجيه النماذج الدورية (الحصر الشهري، خطة الصيانة) للجهات المختصة مع تحديد موعد تسليم من شاشة النموذج.');
        }

        // 6) الاستثمار
        $inv = $b['investment'];
        $stopped = collect($inv)->filter(fn ($c, $k) => str_contains($k, 'متوقفة'))->sum();
        $noLicense = collect($inv)->filter(fn ($c, $k) => str_contains($k, 'لم تتقدم') || str_contains($k, 'ولم'))->sum();
        if ($stopped || $noLicense) {
            $add('warning', 'الاستثمار', 'مشروعات ومصانع متعثرة بالمناطق الصناعية',
                "{$fmt($stopped)} مصنع متوقف عن الإنتاج و{$fmt($noLicense)} مشروع لم يستكمل إجراءات الترخيص أو البناء.",
                collect($inv)->sortDesc()->take(4)->map(fn ($c) => $fmt($c))->all(),
                'عقد لقاء مع المستثمرين المتعثرين لحل المعوقات، وسحب الأراضي غير الجادة وفق القانون وإعادة طرحها.');
        }

        // 7) جودة البيانات
        $dq = $b['data_quality'];
        $unassignedPct = ($dq['total_sector'] + $dq['total_facilities']) ? ($dq['sector'] + $dq['facilities']) / ($dq['total_sector'] + $dq['total_facilities']) * 100 : 0;
        if ($unassignedPct > 10) {
            $add('info', 'جودة البيانات', number_format($unassignedPct, 0) . '% من السجلات غير مرتبطة بمركز إداري',
                "{$fmt($dq['sector'] + $dq['facilities'])} سجلاً بلا ربط جغرافي بالمركز؛ لا تظهر في مقارنات المراكز أو على الخريطة.",
                ['قطاعية' => $fmt($dq['sector']), 'منشآت' => $fmt($dq['facilities'])],
                'تكليف مسؤول نظم المعلومات الجغرافية باستكمال حقل المركز والإحداثيات في القوالب قبل إعادة رفعها.');
        }

        // 8) حداثة البيانات
        $stale = collect($b['freshness'])->filter(fn ($s) => $s['days'] !== null && $s['days'] > 60);
        if ($stale->isNotEmpty()) {
            $add('info', 'حداثة البيانات', $stale->count() . ' مصدر بيانات لم يُحدَّث منذ أكثر من شهرين',
                'القرارات المبنية على بيانات قديمة قد لا تعكس الوضع الحالي.',
                $stale->take(5)->mapWithKeys(fn ($s) => [$s['source'] => $s['days'] . ' يوم'])->all(),
                'اعتماد دورية تحديث شهرية لكل قاعدة بيانات وتوجيه نماذج التحديث للمديريات المختصة.');
        }

        // 9) معدل الاعتماد
        if ($f['approval_rate'] !== null && $f['approval_rate'] >= 60) {
            $add('positive', 'تداول البيانات', "معدل اعتماد الإرسالات {$fmt($f['approval_rate'], 1)}%",
                'نسبة مرتفعة من البيانات المرسلة يتم اعتمادها، مما يعكس جودة الإدخال.',
                ['الإرسالات' => $fmt($b['kpis']['submissions'])], 'تعميم ممارسات الجهات الأكثر التزاماً على باقي الجهات.');
        }

        usort($out, fn ($a, $b) => $b['weight'] <=> $a['weight']);
        return $out;
    }

    /* =====================================================================
     |  ASSISTANT (decision)
     * ===================================================================== */

    public const SUGGESTIONS = [
        'أعطني ملخصاً تنفيذياً للوضع الحالي',
        'ما أهم 3 أولويات تتطلب قراراً هذا الشهر؟',
        'أي المراكز الأقل في الخدمات الصحية؟',
        'قارن بين المراكز في التعليم',
        'ما وضع المركبات المعطلة؟',
        'ما نسبة تنفيذ خطة الصيانة؟',
        'ما الجهات المتأخرة في تسليم النماذج؟',
        'ما وضع الاستثمار والمصانع المتعثرة؟',
    ];

    /**
     * @return array{content:string, provider:string, provider_label:string, model:?string}
     */
    public function answer(User $user, string $question, array $history = []): array
    {
        $brief = $this->brief($user);

        $system = $this->systemPrompt($brief);
        $messages = array_merge(array_slice($history, -8), [['role' => 'user', 'content' => $question]]);
        $res = $this->ai->chat($system, $messages);

        if ($res['success']) {
            return ['content' => $res['content'], 'provider' => $res['provider'], 'provider_label' => $res['provider_label'], 'model' => $res['model'] ?? null];
        }

        return ['content' => $this->fallbackAnswer($brief, $question), 'provider' => 'internal', 'provider_label' => AiService::LABELS['internal'], 'model' => null];
    }

    public function systemPrompt(array $brief): string
    {
        return <<<TXT
أنت المستشار الرسمي لدعم اتخاذ القرار لدى السيد محافظ كفر الشيخ والقيادات التنفيذية، ضمن منصة "رابط".
القواعد:
- أجب باللغة العربية الفصحى وبأسلوب رسمي موجز يليق بالقيادات.
- اعتمد حصراً على "موجز بيانات المحافظة" أدناه. لا تختلق أرقاماً أو أسماء. إن لم تتوفر البيانات اللازمة فقل ذلك صراحةً واقترح ما يلزم جمعه.
- اذكر الأرقام التي تستند إليها في كل استنتاج.
- عند طلب رأي أو قرار: قدّم (1) الموقف الحالي، (2) البدائل المتاحة، (3) التوصية المقترحة، (4) المخاطر ومؤشرات المتابعة.
- نسّق الإجابة بعناوين قصيرة تبدأ بـ ### ونقاط تبدأ بـ - واستخدم **للتأكيد**. لا تستخدم جداول HTML ولا رموزاً تعبيرية.
- نطاق البيانات: {$brief['scope_note']}. تاريخ الموجز: {$brief['generated_label']}.

موجز بيانات المحافظة:
{$this->contextText($brief)}
TXT;
    }

    /** نص مضغوط للموجز (للنموذج اللغوي) */
    public function contextText(array $b): string
    {
        $k = $b['kpis'];
        $lines = [];
        $lines[] = '## مؤشرات عامة';
        $lines[] = "السكان: " . ($k['population'] ? number_format($k['population']) : 'غير متاح') . " | المراكز: {$k['centers']} | الوحدات المحلية القروية: {$k['local_units']} | القرى التوابع: {$k['villages']} | العزب والكفور: {$k['hamlets']}";
        $lines[] = "السجلات المكانية والوصفية: {$k['records']} | المدارس: {$k['schools']} | المنشآت الصحية: {$k['health']}";
        $lines[] = "المركبات: {$k['vehicles']} (معطلة {$k['vehicles_broken']}) | تنفيذ الصيانة: " . ($k['maintenance_rate'] ?? '—') . '%';
        $lines[] = "النماذج النشطة: {$k['templates']} | الإرسالات: {$k['submissions']} | تنتظر المراجعة: {$k['pending_review']} | جهات متأخرة عن نماذج موجهة: {$k['overdue_targets']}";

        $lines[] = "\n## المراكز (السكان | مدارس | مدارس/100ألف | صحة | صحة/100ألف | مخابز/100ألف | منافذ تموين/100ألف | شباب/100ألف | آيلة للسقوط | مركبات معطلة/إجمالي | تنفيذ صيانة% | إرسالات معتمدة/معلقة | قرى توابع | وحدات محلية | شهرة)";
        foreach ($b['centers'] as $c) {
            $lines[] = sprintf('- %s: %s | %d | %s | %d | %s | %s | %s | %s | %d | %d/%d | %s | %d/%d | %s | %s | %s',
                $c['name'], $c['population'] ? number_format($c['population']) : '—', $c['schools'], $c['schools_per100k'] ?? '—', $c['health'], $c['health_per100k'] ?? '—',
                $c['bakeries_per100k'] ?? '—', $c['outlets_per100k'] ?? '—', $c['youth_per100k'] ?? '—', $c['unfit_buildings'],
                $c['vehicles_broken'], $c['vehicles'], $c['maintenance_rate'] ?? '—', $c['submissions_approved'], $c['submissions_pending'],
                $c['villages'] ?? '—', $c['local_units'] ?? '—', Str::limit((string) $c['known_for'], 60));
        }

        $lines[] = "\n## المركبات حسب الحالة: " . collect($b['vehicles']['by_status'])->map(fn ($v, $s) => "$s $v")->implode('، ');
        $lines[] = 'أكثر الجهات أعطالاً: ' . collect($b['vehicles']['top_broken_locations'])->map(fn ($l, $n) => "$n ({$l['broken']} من {$l['total']})")->implode('، ');
        $lines[] = 'الصيانة حسب الجهة (أقل تنفيذاً أولاً): ' . collect($b['maintenance']['by_entity'])->take(10)->map(fn ($e, $n) => "$n {$e['rate']}%")->implode('، ');
        $lines[] = "\n## القطاعات (عدد السجلات): " . collect($b['sectors'])->map(fn ($v, $s) => "$s $v")->implode('، ');
        $lines[] = 'المنشآت: ' . collect($b['facilities'])->map(fn ($v, $s) => "$s $v")->implode('، ');
        $lines[] = 'الاستثمار: ' . collect($b['investment'])->map(fn ($v, $s) => "$s $v")->implode('، ');
        $lines[] = "\n## النماذج حسب الحالة: " . collect($b['forms']['by_status'])->map(fn ($v, $s) => "$s $v")->implode('، ');
        $lines[] = 'متأخرات النماذج الموجهة: ' . (collect($b['forms']['coverage']['overdue_list'])->map(fn ($o) => "{$o['unit']} — {$o['template']} ({$o['days_late']} يوم)")->implode('؛ ') ?: 'لا يوجد');
        $lines[] = "\n## حداثة البيانات: " . collect($b['freshness'])->map(fn ($s) => "{$s['source']}: {$s['label']}")->implode('، ');
        $lines[] = "\n## الاستنتاجات الآلية";
        foreach ($b['insights'] as $i) {
            $lines[] = "- [{$i['severity_label']}] {$i['title']}: {$i['detail']} التوصية: {$i['rec']}";
        }

        return implode("\n", $lines);
    }

    /* =====================================================================
     |  FALLBACK ENGINE (deterministic, offline)
     * ===================================================================== */

    public function fallbackAnswer(array $b, string $q): string
    {
        $n = $this->norm($q);
        $has = fn (array $words) => collect($words)->contains(fn ($w) => str_contains($n, $this->norm($w)));
        $centers = collect($b['centers']);

        // مركز بعينه
        $center = $centers->first(fn ($c) => str_contains($n, $this->norm($c['short'])));

        $indicator = match (true) {
            $has(['صحه', 'صحي', 'مستشفي', 'مستشفيات', 'وحدات صحيه']) => 'health_per100k',
            $has(['مدارس', 'مدرسه', 'تعليم']) => 'schools_per100k',
            $has(['مخابز', 'خبز']) => 'bakeries_per100k',
            $has(['منافذ', 'تموين', 'سلع']) => 'outlets_per100k',
            $has(['شباب']) => 'youth_per100k',
            $has(['سكان', 'تعداد']) => 'population',
            $has(['ايله', 'سقوط']) => 'unfit_buildings',
            $has(['اعطال', 'معطل']) => 'vehicles_broken_pct',
            default => null,
        };

        $out = [];
        $wantsRank = $has(['اقل', 'اضعف', 'اسوا', 'ادني', 'اكثر', 'اعلي', 'افضل', 'ترتيب', 'قارن', 'مقارنه']);

        if ($indicator && str_ends_with($indicator, '_per100k') && $has(['كم', 'عدد', 'اجمالي'])) {
            $raw = str_replace('_per100k', '', $indicator);
            $label = str_replace(' لكل 100 ألف نسمة', '', self::INDICATORS[$indicator][0]);
            $rows = $center ? collect([$center]) : $centers;
            $out[] = "### {$label}\n- **الإجمالي" . ($center ? " في {$center['name']}" : ' المسجل') . ':** ' . number_format($rows->sum($raw)) . ' ' . self::INDICATORS[$indicator][1]
                . "\n- " . $rows->sortByDesc($raw)->map(fn ($c) => "{$c['short']}: " . number_format($c[$raw]))->implode('، ');
            if (! $center) $out[] = $this->ranking($b, $indicator, null);
        } elseif ($center && ! $wantsRank) {
            $out[] = $this->centerProfile($center, $b);
        } elseif ($wantsRank && $indicator) {
            $out[] = $this->ranking($b, $indicator, $has(['اقل', 'اضعف', 'اسوا', 'ادني']) ? 'asc' : ($has(['اكثر', 'اعلي', 'افضل']) ? 'desc' : null));
        } elseif ($has(['مركبات', 'سيارات', 'معدات', 'اسطول'])) {
            $out[] = $this->vehiclesAnswer($b);
        } elseif ($has(['صيانه'])) {
            $out[] = $this->maintenanceAnswer($b);
        } elseif ($has(['نماذج', 'نموذج', 'متاخر', 'ارسالات', 'مراجعه', 'تسليم'])) {
            $out[] = $this->formsAnswer($b);
        } elseif ($has(['استثمار', 'مصانع', 'مشروعات', 'صناعيه'])) {
            $out[] = $this->sectorInsightsAnswer($b, 'الاستثمار', 'investment');
        } elseif ($has(['ايله', 'سقوط', 'تعليم', 'مدارس'])) {
            $out[] = $this->ranking($b, 'schools_per100k', null);
            $out[] = $this->insightsBlock($b, ['التعليم']);
        } elseif ($has(['صحه', 'مستشفي'])) {
            $out[] = $this->ranking($b, 'health_per100k', null);
        } elseif ($has(['تموين', 'مخابز', 'خبز'])) {
            $out[] = $this->ranking($b, 'bakeries_per100k', null);
            $out[] = $this->ranking($b, 'outlets_per100k', null);
        } elseif ($has(['نواقص', 'فجوات', 'عجز', 'احتياج', 'اولويات', 'اولويه', 'قرار', 'توصيات', 'مخاطر'])) {
            $out[] = $this->prioritiesAnswer($b);
        } elseif ($has(['جوده', 'حداثه', 'تحديث'])) {
            $out[] = $this->insightsBlock($b, ['جودة البيانات', 'حداثة البيانات']);
        } else {
            $out[] = $this->summaryAnswer($b);
        }

        $out[] = "\n---\nأُعدّت هذه الإجابة آلياً بواسطة **محرك التحليل الداخلي** من بيانات المنصة ({$b['scope_note']}) بتاريخ {$b['generated_label']}. للتحليل اللغوي الموسّع يلزم تفعيل مزود الذكاء الاصطناعي.";

        return trim(implode("\n\n", array_filter($out)));
    }

    protected function summaryAnswer(array $b): string
    {
        $k = $b['kpis'];
        $f = fn ($x, $d = 0) => $x === null ? '—' : number_format((float) $x, $d);
        $s = ["### الملخص التنفيذي"];
        $s[] = "- **السكان:** {$f($k['population'])} نسمة موزعين على {$k['centers']} مركزاً ومدينة، تضم {$f($k['local_units'])} وحدة محلية قروية و{$f($k['villages'])} قرية تابعة و{$f($k['hamlets'])} عزبة وكفر ونجع.";
        $s[] = "- **قاعدة البيانات:** {$f($k['records'])} سجلاً مكانياً ووصفياً، منها {$f($k['schools'])} مدرسة و{$f($k['health'])} منشأة صحية.";
        $s[] = "- **المركبات:** {$f($k['vehicles'])} مركبة، المعطل منها {$f($k['vehicles_broken'])} (" . $f($b['vehicles']['broken_pct'], 1) . "%).";
        $s[] = "- **الصيانة:** نسبة تنفيذ الخطة الشهرية " . $f($k['maintenance_rate'], 1) . "%.";
        $s[] = "- **تداول البيانات:** {$f($k['templates'])} نموذجاً نشطاً، {$f($k['submissions'])} إرسالاً، منها {$f($k['pending_review'])} ينتظر المراجعة.";
        $s[] = $this->prioritiesAnswer($b, 3);
        return implode("\n", $s);
    }

    protected function prioritiesAnswer(array $b, int $limit = 5): string
    {
        $items = collect($b['insights'])->whereIn('severity', ['critical', 'warning'])->take($limit)->values();
        if ($items->isEmpty()) return "### الأولويات\n- لا توجد مؤشرات حرجة حالياً وفق البيانات المتاحة.";
        $s = ["### أهم الأولويات المقترحة"];
        foreach ($items as $i => $it) {
            $ev = collect($it['evidence'])->map(fn ($v, $k) => "$k: $v")->implode('، ');
            $s[] = ($i + 1) . ". **{$it['title']}** ({$it['severity_label']} — {$it['area']})";
            $s[] = "- الموقف: {$it['detail']}" . ($ev ? " [{$ev}]" : '');
            $s[] = "- التوصية: {$it['rec']}";
        }
        return implode("\n", $s);
    }

    protected function ranking(array $b, string $key, ?string $dir): string
    {
        [$label, $unit, $higherBetter] = self::INDICATORS[$key];
        $rows = collect($b['centers'])->filter(fn ($c) => $c[$key] !== null);
        if ($rows->isEmpty()) return "### {$label}\n- لا تتوفر بيانات كافية لحساب هذا المؤشر.";

        $asc = $dir === 'asc' || ($dir === null && $higherBetter);
        $sorted = $asc ? $rows->sortBy($key) : $rows->sortByDesc($key);
        $avg = $rows->avg($key);
        $s = ["### ترتيب المراكز: {$label}" . ($asc ? ' (من الأقل)' : ' (من الأعلى)')];
        foreach ($sorted->values() as $i => $c) {
            $mark = $higherBetter === null ? '' : (($higherBetter ? $c[$key] < $avg * 0.7 : $c[$key] > $avg * 1.3) ? ' — **أقل من المستوى المطلوب**' : '');
            $s[] = ($i + 1) . ". {$c['name']}: " . number_format((float) $c[$key], $key === 'population' ? 0 : 1) . " {$unit}{$mark}";
        }
        $s[] = "- **متوسط المراكز:** " . number_format($avg, 1) . " {$unit}";
        $nonZero = $rows->filter(fn ($c) => $c[$key] > 0);
        $incomplete = $higherBetter !== null && $nonZero->count() >= 2 && $nonZero->max($key) / max($nonZero->min($key), 0.01) > 15;
        if ($incomplete) {
            $s[] = "### تنبيه بشأن جودة البيانات\n- التفاوت بين المراكز يتجاوز 15 ضعفاً، ما يرجّح أن بيانات هذا القطاع **غير مكتملة** في بعض المراكز وليس فجوة خدمية فعلية.\n- التوصية: توجيه نموذج حصر للمديرية المختصة لاستكمال البيانات لكل المراكز قبل اتخاذ قرار توزيع أو استثمار.";
        } elseif ($higherBetter !== null) {
            $worst = $higherBetter ? $rows->sortBy($key)->first() : $rows->sortByDesc($key)->first();
            $best = $higherBetter ? $rows->sortByDesc($key)->first() : $rows->sortBy($key)->first();
            $s[] = "- **الفجوة:** أفضل مركز ({$best['short']}) مقابل الأضعف ({$worst['short']}).";
            $s[] = "### التوصية\n- توجيه الاستثمارات والتوزيع الجديد نحو {$worst['name']}، مع التحقق من اكتمال بياناته قبل اعتماد القرار.";
        }
        if (str_ends_with($key, 'per100k')) $s[] = '- المؤشر محسوب نسبةً إلى تعداد السكان المسجل بقاعدة بيانات السكان.';
        return implode("\n", $s);
    }

    protected function centerProfile(array $c, array $b): string
    {
        $avg = fn ($k) => collect($b['centers'])->avg($k);
        $f = fn ($x, $d = 0) => $x === null ? '—' : number_format((float) $x, $d);
        $cmp = function ($k) use ($c, $avg, $f) {
            if ($c[$k] === null || ! $avg($k)) return '';
            $r = $c[$k] / $avg($k);
            return $r < .85 ? ' (أقل من المتوسط ' . $f($avg($k), 1) . ')' : ($r > 1.15 ? ' (أعلى من المتوسط ' . $f($avg($k), 1) . ')' : ' (قريب من المتوسط)');
        };
        $s = ["### {$c['name']}"];
        if ($c['known_for']) $s[] = "- **يشتهر بـ:** {$c['known_for']}";
        $s[] = "- **السكان:** {$f($c['population'])} نسمة | القرى التوابع: {$f($c['villages'])} | الوحدات المحلية: {$f($c['local_units'])}";
        $s[] = "- **المدارس:** {$f($c['schools'])} — {$f($c['schools_per100k'], 1)} لكل 100 ألف{$cmp('schools_per100k')}";
        $s[] = "- **المنشآت الصحية:** {$f($c['health'])} — {$f($c['health_per100k'], 1)} لكل 100 ألف{$cmp('health_per100k')}";
        $s[] = "- **المخابز:** {$f($c['bakeries'])}{$cmp('bakeries_per100k')} | **منافذ التموين:** {$f($c['outlets'])}{$cmp('outlets_per100k')}";
        $s[] = "- **مراكز الشباب:** {$f($c['youth'])} | **محطات المياه:** {$f($c['water_stations'])} | **محطات الكهرباء:** {$f($c['power_stations'])}";
        if ($c['unfit_buildings']) $s[] = "- **مبانٍ مدرسية آيلة للسقوط:** {$c['unfit_buildings']}";
        if ($c['vehicles']) $s[] = "- **المركبات:** {$c['vehicles']} (معطلة {$c['vehicles_broken']})";
        if ($c['maintenance_rate'] !== null) $s[] = "- **تنفيذ الصيانة:** {$c['maintenance_rate']}%";
        $s[] = "- **الإرسالات:** {$c['submissions']} (معتمدة {$c['submissions_approved']}، معلقة {$c['submissions_pending']})";
        $related = collect($b['insights'])->where('center', $c['name'])->take(3);
        if ($related->isNotEmpty()) {
            $s[] = "### ملاحظات وتوصيات";
            foreach ($related as $i) $s[] = "- **{$i['title']}:** {$i['rec']}";
        }
        return implode("\n", $s);
    }

    protected function vehiclesAnswer(array $b): string
    {
        $v = $b['vehicles'];
        $s = ["### موقف المركبات والمعدات"];
        $s[] = "- **الإجمالي:** " . number_format($v['total']) . " مركبة، المعطل منها **" . number_format($v['broken']) . "** (" . ($v['broken_pct'] ?? '—') . "%).";
        $s[] = '- **حسب الحالة:** ' . collect($v['by_status'])->map(fn ($c, $st) => "$st " . number_format($c))->implode('، ');
        if ($v['top_broken_locations']) {
            $s[] = "### الجهات الأعلى أعطالاً";
            foreach ($v['top_broken_locations'] as $loc => $l) $s[] = "- {$loc}: {$l['broken']} معطلة من {$l['total']}";
        }
        $s[] = $this->insightsBlock($b, ['المركبات والمعدات'], false);
        return implode("\n", $s);
    }

    protected function maintenanceAnswer(array $b): string
    {
        $m = $b['maintenance'];
        $s = ["### خطة الصيانة الشهرية"];
        $s[] = "- **نسبة التنفيذ:** " . ($m['rate'] ?? '—') . "% (نُفذ {$m['executed']} من {$m['planned']} بنداً مخططاً على {$m['vehicles_in_plan']} مركبة).";
        $s[] = "### الجهات حسب نسبة التنفيذ (الأقل أولاً)";
        foreach (collect($m['by_entity'])->take(8) as $name => $e) $s[] = "- {$name}: {$e['rate']}% ({$e['executed']}/{$e['planned']})";
        $s[] = $this->insightsBlock($b, ['الصيانة'], false);
        return implode("\n", $s);
    }

    protected function formsAnswer(array $b): string
    {
        $f = $b['forms'];
        $labels = ['draft' => 'مسودة', 'assigned' => 'مسند', 'data_entry' => 'إدخال بيانات', 'under_review' => 'قيد المراجعة', 'revision_required' => 'يحتاج تعديل', 'approved' => 'معتمد', 'rejected' => 'مرفوض', 'report_approved' => 'تقرير معتمد', 'report_sent' => 'تقرير مرسل'];
        $s = ["### تداول البيانات والنماذج"];
        $s[] = '- **الإرسالات حسب الحالة:** ' . (collect($f['by_status'])->map(fn ($c, $st) => ($labels[$st] ?? $st) . " $c")->implode('، ') ?: 'لا توجد');
        $s[] = "- **معدل الاعتماد:** " . ($f['approval_rate'] ?? '—') . "% | **متأخرة في المراجعة (> 7 أيام):** {$f['stale_review']}";
        $c = $f['coverage'];
        $s[] = "- **النماذج الموجهة للجهات:** {$c['targets']} توجيهاً، استجابت {$c['responded']}" . ($c['rate'] !== null ? " ({$c['rate']}%)" : '') . "، متأخر {$c['overdue']}.";
        if ($c['overdue_list']) {
            $s[] = "### الجهات المتأخرة";
            foreach ($c['overdue_list'] as $o) $s[] = "- {$o['unit']} — {$o['template']} (متأخر {$o['days_late']} يوماً عن {$o['due_date']})";
        }
        $s[] = $this->insightsBlock($b, ['تداول البيانات'], false);
        return implode("\n", $s);
    }

    protected function sectorInsightsAnswer(array $b, string $area, string $key): string
    {
        $s = ["### {$area}"];
        foreach ($b[$key] ?? [] as $k => $v) $s[] = "- {$k}: " . number_format($v);
        $s[] = $this->insightsBlock($b, [$area], false);
        return implode("\n", $s);
    }

    protected function insightsBlock(array $b, array $areas, bool $title = true): string
    {
        $items = collect($b['insights'])->whereIn('area', $areas);
        if ($items->isEmpty()) return '';
        $s = $title ? ['### الاستنتاجات والتوصيات'] : ['### التوصية'];
        foreach ($items as $i) $s[] = "- **{$i['title']}** ({$i['severity_label']}): {$i['detail']} — {$i['rec']}";
        return implode("\n", $s);
    }

    /* =====================================================================
     |  helpers
     * ===================================================================== */

    protected function pick(array $x, string $needle): ?string
    {
        foreach ($x as $k => $v) {
            if (str_contains($this->norm($k), $this->norm($needle))) return is_scalar($v) ? trim((string) $v) : null;
        }
        return null;
    }

    protected function num($v): ?int
    {
        if ($v === null) return null;
        $v = strtr((string) $v, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', ',' => '', '،' => '']);
        return is_numeric($v) ? (int) $v : null;
    }

    public function norm(?string $s): string
    {
        $s = str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ـ'], ['ا', 'ا', 'ا', 'ه', 'ي', ''], (string) $s);
        return preg_replace('/\s+/u', '', mb_strtolower($s));
    }

    public function shortName(string $name): string
    {
        return trim(preg_replace('/^(مركز|مدينة|مركز ومدينة)\s+/u', '', $name));
    }

    protected function centerIdByName($centers, ?string $text): ?int
    {
        if (! $text) return null;
        $t = $this->norm($text);
        if (str_contains($t, 'ديوان') || str_contains($t, 'محافظه')) return null; // جهات مركزية بالمحافظة
        foreach ($centers as $c) {
            $short = $this->norm($this->shortName($c->name));
            if ($short !== '' && str_contains($t, $short)) return $c->id;
        }
        // أحياء مدينة كفر الشيخ
        if (preg_match('/(حيشرق|حيغرب|حملهشرق|حملهغرب)/u', $t)) {
            return $centers->first(fn ($c) => $this->norm($this->shortName($c->name)) === $this->norm('كفر الشيخ'))?->id;
        }
        if (str_contains($t, 'برلس') || str_contains($t, 'مصيفبلطيم')) {
            return $centers->first(fn ($c) => str_contains($this->norm($c->name), 'بلطيم'))?->id;
        }
        return null;
    }
}
