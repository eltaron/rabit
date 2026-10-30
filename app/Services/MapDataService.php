<?php

namespace App\Services;

use App\Models\FacilityData;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionData;
use App\Models\FormTemplate;
use App\Models\JobPosition;
use App\Models\OrgUnit;
use App\Models\SectorData;
use App\Models\User;
use App\Support\DataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * مصدر بيانات الخريطة التفاعلية: كتالوج الطبقات، المعالم (GeoJSON)، مؤشرات الجهات، والبحث المكاني.
 *
 * معرفات الطبقات:
 *   fac:{MODULE_CODE}            مرافق ومنشآت (facilities_data)
 *   sec:{sector}:{subcategory}   قواعد البيانات القطاعية (sector_data)
 *   unit:{center|local|entity}   التقسيم الإداري والجهات (org_units)
 *   form:{template_id}           بيانات النماذج ذات الإحداثيات (form_submission_data)
 */
class MapDataService
{
    public const MAX_FEATURES = 6000;

    /** لوحة ألوان هادئة متوافقة مع الهوية (بدون نيون) */
    public const PALETTE = [
        '#123F35', '#B8954F', '#2D6477', '#9B2C2C', '#5E6B3A', '#8C6D33',
        '#216455', '#6B4A5A', '#6B5A3E', '#3E7F6B', '#626963', '#A07A3A',
        '#41626F', '#7A3E3E', '#4F5D2F', '#857049',
    ];

    public const SECTORS = [
        'investment' => ['الاستثمار والصناعة', 'fa-industry'],
        'population' => ['السكان', 'fa-people-group'],
        'education' => ['التعليم', 'fa-school'],
        'supply' => ['التموين', 'fa-wheat-awn'],
        'health' => ['الصحة', 'fa-hospital'],
    ];

    public const MODULE_ICONS = [
        'MOSQUES' => 'fa-mosque', 'CHURCHES' => 'fa-church', 'SCHOOLS' => 'fa-school', 'HOSPITALS' => 'fa-hospital',
        'COURTS' => 'fa-scale-balanced', 'HOTELS' => 'fa-hotel', 'POWER_STATIONS' => 'fa-bolt', 'WATER_STATIONS' => 'fa-droplet',
        'YOUTH_CENTERS' => 'fa-futbol', 'SHELTER_CENTERS' => 'fa-house-chimney-medical', 'FLOUR_MILLS' => 'fa-wheat-awn',
        'INFORMAL_AREAS' => 'fa-house-crack', 'STRATEGIC_SITES' => 'fa-shield-halved', 'ARCHAEOLOGICAL_ATTRACTIONS' => 'fa-landmark',
        'CULTURAL_ATTRACTIONS' => 'fa-masks-theater', 'RECREATIONAL_ATTRACTIONS' => 'fa-tree', 'AIRPORTS' => 'fa-plane',
        'BRIDGES' => 'fa-bridge', 'TUNNELS' => 'fa-road', 'EMBASSIES' => 'fa-flag', 'MINISTRIES' => 'fa-building-columns',
    ];

    private const FACILITY_LABELS = [
        'name' => 'الاسم', 'module_name' => 'التصنيف', 'type' => 'النوع / التبعية', 'center_name' => 'المركز',
        'address' => 'العنوان', 'phone' => 'التليفون', 'governorate' => 'المحافظة', 'directorate' => 'المديرية',
        'city' => 'المدينة', 'district' => 'الحي / الجهة',
    ];

    private const COORD_KEY_RE = '/^(x|y|lat|lng|lon|long|latitude|longitude)$|إحداث|احداث|خط العرض|خط الطول/iu';

    /* =====================================================================
     | Geometry
     * ===================================================================== */

    /** حلقة حدود المحافظة [[lng,lat], ...] */
    public static function boundary(): array
    {
        return Cache::rememberForever('map.boundary.v1', function () {
            $g = json_decode(@file_get_contents(public_path('data/kafr-el-sheikh.geojson')), true);
            $geom = $g['features'][0]['geometry'] ?? null;
            if (! $geom) return [];
            return $geom['type'] === 'MultiPolygon' ? $geom['coordinates'][0][0] : $geom['coordinates'][0];
        });
    }

    public static function pointInPolygon(float $lng, float $lat, ?array $ring = null): bool
    {
        $ring ??= static::boundary();
        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $inside = ! $inside;
            }
        }
        return $inside;
    }

    /** داخل الحدود مع هامش تسامح (درجة ≈ 111كم؛ 0.03 ≈ 3كم) */
    public static function nearBoundary(float $lng, float $lat, float $tol = 0.03): bool
    {
        if (static::pointInPolygon($lng, $lat)) return true;
        foreach ([[$tol, 0], [-$tol, 0], [0, $tol], [0, -$tol], [$tol, $tol], [-$tol, -$tol], [$tol, -$tol], [-$tol, $tol]] as [$dx, $dy]) {
            if (static::pointInPolygon($lng + $dx, $lat + $dy)) return true;
        }
        return false;
    }

    /**
     * تحويل زوج (X,Y) غير موثوق الترتيب إلى [lat, lng] أو null.
     * يجرّب الترتيبين ويختار ما يقع داخل حدود المحافظة.
     */
    public static function normalizePair(mixed $x, mixed $y): ?array
    {
        $x = static::toFloat($x);
        $y = static::toFloat($y);
        if ($x === null || $y === null) return null;
        if (abs($x) > 180 || abs($y) > 180 || $x == 0.0 || $y == 0.0) return null;

        // الترتيب الشائع: X = خط الطول، Y = خط العرض
        foreach ([[$y, $x], [$x, $y]] as [$lat, $lng]) {
            if (static::pointInPolygon($lng, $lat)) return [round($lat, 7), round($lng, 7)];
        }
        foreach ([[$y, $x], [$x, $y]] as [$lat, $lng]) {
            if (static::nearBoundary($lng, $lat)) return [round($lat, 7), round($lng, 7)];
        }
        return null;
    }

    public static function toFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float) $v;
        $v = strtr((string) $v, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', ',' => '.']);
        $v = preg_replace('/[^0-9.\-]/', '', $v);
        return is_numeric($v) ? (float) $v : null;
    }

    /* =====================================================================
     | Scope
     * ===================================================================== */

    /**
     * البيانات المرجعية (مرافق/قطاعات) مرتبطة بالمراكز الإدارية.
     * - نطاق "كل المحافظة": بلا قيود.
     * - مستخدم ينتمي لجهة جغرافية (مركز/مدينة/قرية): يرى نطاق مركزه فقط (DataScope::byUnit على المركز).
     * - مستخدم في جهة قطاعية غير جغرافية (مديرية/إدارة): البيانات المرجعية العامة على مستوى المحافظة.
     */
    public function referenceUnitIds(?User $user): ?array
    {
        $ids = DataScope::unitIds($user);
        if ($ids === null) return null;
        if (! $user?->org_unit_id) return null;

        $unit = OrgUnit::find($user->org_unit_id);
        if (! $unit || ! in_array($unit->type, OrgUnit::GEO_TYPES, true) || $unit->type === 'governorate') return null;

        $center = $unit->nearestCenter() ?? $unit;
        return OrgUnit::subtreeOf($center)->pluck('id')->all();
    }

    private function scopeRef(Builder $q, ?User $user): Builder
    {
        $ids = $this->referenceUnitIds($user);
        return $ids === null ? $q : $q->whereIn('org_unit_id', $ids ?: [0]);
    }

    private function filterUnit(Builder $q, ?int $unitId, string $col = 'org_unit_id'): Builder
    {
        if (! $unitId) return $q;
        $unit = OrgUnit::find($unitId);
        if (! $unit) return $q;
        // المرافق مرتبطة بمستوى المركز: عند اختيار جهة أدنى استخدم مركزها
        $anchor = in_array($unit->type, ['city', 'district', 'local_unit', 'village', 'hamlet'], true) ? ($unit->nearestCenter() ?? $unit) : $unit;
        return $q->whereIn($col, OrgUnit::subtreeOf($anchor)->pluck('id')->all() ?: [0]);
    }

    /* =====================================================================
     | Layers catalog
     * ===================================================================== */

    public function layers(?User $user): array
    {
        $groups = [];
        $colorIdx = 0;
        $color = function () use (&$colorIdx) { return self::PALETTE[$colorIdx++ % count(self::PALETTE)]; };

        // 1) المرافق والمنشآت
        $fac = $this->scopeRef(FacilityData::query(), $user)
            ->select('module_code', 'module_name', DB::raw('count(*) as total'), DB::raw('sum(latitude is not null) as geo'))
            ->groupBy('module_code', 'module_name')->orderByDesc('geo')->orderBy('module_name')->get();
        $groups[] = [
            'key' => 'facilities', 'label' => 'المرافق والمنشآت', 'icon' => 'fa-building',
            'layers' => $fac->map(fn ($r) => [
                'id' => 'fac:' . $r->module_code, 'label' => $r->module_name ?: $r->module_code,
                'icon' => self::MODULE_ICONS[$r->module_code] ?? 'fa-location-dot', 'color' => $color(),
                'total' => (int) $r->total, 'geo' => (int) $r->geo,
            ])->values()->all(),
        ];

        // 2) القطاعات
        $sec = $this->scopeRef(SectorData::query(), $user)
            ->select('sector', 'subcategory', DB::raw('count(*) as total'), DB::raw('sum(latitude is not null) as geo'))
            ->groupBy('sector', 'subcategory')->get()->groupBy('sector');
        $secLayers = [];
        foreach ($sec as $sector => $rows) {
            [$label, $icon] = self::SECTORS[$sector] ?? [$sector, 'fa-database'];
            foreach ($rows->where('geo', '>', 0)->sortByDesc('geo') as $r) {
                $secLayers[] = [
                    'id' => 'sec:' . $sector . ':' . $r->subcategory, 'label' => $r->subcategory, 'parent' => $label,
                    'icon' => $icon, 'color' => $color(), 'total' => (int) $r->total, 'geo' => (int) $r->geo,
                ];
            }
        }
        $groups[] = [
            'key' => 'sectors', 'label' => 'القطاعات', 'icon' => 'fa-layer-group', 'layers' => $secLayers,
            'summary' => $sec->map(fn ($rows, $s) => ['label' => self::SECTORS[$s][0] ?? $s, 'total' => (int) $rows->sum('total'), 'geo' => (int) $rows->sum('geo')])->values()->all(),
        ];

        // 3) التقسيم الإداري والجهات
        $unitCounts = OrgUnit::active()->select('type', DB::raw('count(*) as total'), DB::raw('sum(latitude is not null and longitude is not null) as geo'))
            ->groupBy('type')->get()->keyBy('type');
        $sum = fn (array $types, string $k) => (int) collect($types)->sum(fn ($t) => $unitCounts[$t]->$k ?? 0);
        $groups[] = [
            'key' => 'admin', 'label' => 'التقسيم الإداري', 'icon' => 'fa-map',
            'layers' => [
                ['id' => 'unit:center', 'label' => 'المراكز والمدن الرئيسية', 'icon' => 'fa-city', 'color' => '#0A2924', 'total' => $sum(['center'], 'total'), 'geo' => $sum(['center'], 'geo')],
                ['id' => 'unit:local', 'label' => 'المدن والأحياء والوحدات والقرى', 'icon' => 'fa-house-chimney', 'color' => '#B8954F', 'total' => $sum(['city', 'district', 'local_unit', 'village', 'hamlet'], 'total'), 'geo' => $sum(['city', 'district', 'local_unit', 'village', 'hamlet'], 'geo')],
                ['id' => 'unit:entity', 'label' => 'الجهات والمديريات والهيئات', 'icon' => 'fa-building-columns', 'color' => '#2D6477', 'total' => $sum(['ministry', 'directorate', 'authority', 'company', 'department', 'office', 'section'], 'total'), 'geo' => $sum(['ministry', 'directorate', 'authority', 'company', 'department', 'office', 'section'], 'geo')],
            ],
        ];

        // 4) بيانات النماذج
        $formLayers = [];
        foreach ($this->geoTemplates($user) as $tpl) {
            $formLayers[] = [
                'id' => 'form:' . $tpl['template']->id, 'label' => $tpl['template']->name, 'icon' => 'fa-file-lines',
                'color' => $color(), 'total' => $tpl['rows'], 'geo' => $tpl['rows'],
            ];
        }
        $groups[] = ['key' => 'forms', 'label' => 'بيانات النماذج', 'icon' => 'fa-file-lines', 'layers' => $formLayers];

        return $groups;
    }

    /**
     * النماذج التي تحتوي على حقول إحداثيات: [template, latField, lngField, rows]
     */
    public function geoTemplates(?User $user): Collection
    {
        $templates = DataScope::templates(FormTemplate::query(), $user)->with('formFields:id,form_template_id,name,label')->get();
        $out = collect();
        foreach ($templates as $t) {
            [$latF, $lngF] = $this->detectGeoFields($t);
            if (! $latF || ! $lngF) continue;
            $subIds = DataScope::submissions(FormSubmission::where('form_template_id', $t->id), $user)->pluck('id');
            if ($subIds->isEmpty()) continue;
            $rows = FormSubmissionData::whereIn('form_submission_id', $subIds)->whereIn('form_field_id', [$latF->id])
                ->whereNotNull('value')->where('value', '!=', '')->count();
            if ($rows > 0) $out->push(['template' => $t, 'lat' => $latF, 'lng' => $lngF, 'rows' => $rows, 'submissions' => $subIds]);
        }
        return $out;
    }

    /** @return array{0:?FormField,1:?FormField} */
    public function detectGeoFields(FormTemplate $t): array
    {
        $fields = $t->relationLoaded('formFields') ? $t->formFields : $t->formFields()->get();
        $find = function (?string $explicit, string $re) use ($fields) {
            if ($explicit) {
                $f = $fields->first(fn ($f) => $f->name === $explicit || $f->label === $explicit);
                if ($f) return $f;
            }
            return $fields->first(fn ($f) => preg_match($re, trim($f->name)) || preg_match($re, trim($f->label)));
        };
        $lat = $find($t->geo_lat_field, '/^(y|lat|latitude)$|خط\s*العرض/iu');
        $lng = $find($t->geo_lng_field, '/^(x|lng|lon|long|longitude)$|خط\s*الطول/iu');
        return [$lat, $lng];
    }

    /* =====================================================================
     | Features (GeoJSON)
     * ===================================================================== */

    public function features(?User $user, string $layer, ?int $unitId = null, ?array $bbox = null, ?int $facilityId = null): array
    {
        [$kind, $rest] = array_pad(explode(':', $layer, 2), 2, '');
        $features = match ($kind) {
            'fac' => $this->facilityFeatures($user, $rest, $unitId, $bbox, $facilityId),
            'sec' => $this->sectorFeatures($user, $rest, $unitId, $bbox),
            'unit' => $this->unitFeatures($rest, $unitId, $bbox),
            'form' => $this->formFeatures($user, (int) $rest, $unitId),
            default => [],
        };

        return ['type' => 'FeatureCollection', 'layer' => $layer, 'count' => count($features), 'truncated' => count($features) >= self::MAX_FEATURES, 'features' => $features];
    }

    private function bbox(Builder $q, ?array $bbox): Builder
    {
        if (! $bbox || count($bbox) !== 4) return $q;
        [$minLng, $minLat, $maxLng, $maxLat] = array_map('floatval', $bbox);
        return $q->whereBetween('latitude', [$minLat, $maxLat])->whereBetween('longitude', [$minLng, $maxLng]);
    }

    private function point(float $lat, float $lng, array $props): array
    {
        return ['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [round($lng, 6), round($lat, 6)]], 'properties' => $props];
    }

    private function flattenExtra(?array $extra, array $skipValues = []): array
    {
        $out = [];
        foreach ((array) $extra as $k => $v) {
            $k = trim((string) $k);
            if ($k === '' || $k === 'م' || preg_match(self::COORD_KEY_RE, $k)) continue;
            if (is_array($v)) $v = implode('، ', array_filter(array_map('strval', $v)));
            $v = trim((string) $v);
            if ($v === '' || $v === '-' || in_array($v, $skipValues, true)) continue;
            $out[$k] = $v;
        }
        return $out;
    }

    public function facilityFeatures(?User $user, string $module, ?int $unitId, ?array $bbox, ?int $facilityId = null): array
    {
        $q = $this->scopeRef(FacilityData::query(), $user)->whereNotNull('latitude')->whereNotNull('longitude');
        if ($facilityId) $q->whereKey($facilityId); else $q->where('module_code', $module);
        $this->filterUnit($q, $unitId);
        $this->bbox($q, $bbox);

        return $q->limit(self::MAX_FEATURES)->get()->map(fn (FacilityData $f) => $this->point($f->latitude, $f->longitude, [
            'id' => $f->id, 'layer' => 'fac:' . $f->module_code, 'kind' => 'facility',
            'title' => $f->name ?: ($f->module_name . ' #' . $f->id), 'category' => $f->module_name,
            'center' => $f->center_name, 'unit_id' => $f->org_unit_id,
            'attrs' => $this->facilityAttrs($f),
        ]))->all();
    }

    public function facilityAttrs(FacilityData $f): array
    {
        $attrs = [];
        foreach (self::FACILITY_LABELS as $col => $label) {
            $v = trim((string) $f->$col);
            if ($v !== '' && $v !== '-') $attrs[$label] = $v;
        }
        $extra = $this->flattenExtra($f->extra_data, array_values($attrs));
        return $attrs + $extra;
    }

    public function sectorFeatures(?User $user, string $rest, ?int $unitId, ?array $bbox): array
    {
        [$sector, $sub] = array_pad(explode(':', $rest, 2), 2, null);
        $q = $this->scopeRef(SectorData::query(), $user)->where('sector', $sector)->whereNotNull('latitude')->whereNotNull('longitude');
        if ($sub !== null && $sub !== '') $q->where('subcategory', $sub);
        $this->filterUnit($q, $unitId);
        $this->bbox($q, $bbox);
        $sectorLabel = self::SECTORS[$sector][0] ?? $sector;

        return $q->limit(self::MAX_FEATURES)->get()->map(fn (SectorData $r) => $this->point($r->latitude, $r->longitude, [
            'id' => $r->id, 'layer' => 'sec:' . $sector . ':' . $r->subcategory, 'kind' => 'sector',
            'title' => $this->sectorTitle($r), 'category' => $sectorLabel . ' — ' . $r->subcategory,
            'center' => $r->center_name, 'unit_id' => $r->org_unit_id,
            'attrs' => $this->sectorAttrs($r, $sectorLabel),
        ]))->all();
    }

    private function sectorTitle(SectorData $r): string
    {
        $extra = (array) $r->extra_data;
        foreach (['الاسم', 'اسم المنشأة', 'اسم المنشاة', 'اسم المخبز', 'اسم المحطة', 'اسم المشروع', 'اسم المصنع', 'اسم المدرسة'] as $k) {
            if (! empty($extra[$k]) && is_string($extra[$k])) return trim($extra[$k]);
        }
        return $r->name ?: ($r->subcategory . ' #' . $r->id);
    }

    public function sectorAttrs(SectorData $r, ?string $sectorLabel = null): array
    {
        $attrs = array_filter([
            'القطاع' => $sectorLabel ?? (self::SECTORS[$r->sector][0] ?? $r->sector),
            'التصنيف' => $r->subcategory,
            'المركز' => $r->center_name,
            'العنوان' => $r->address,
            'الفترة' => $r->year_period,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');
        return $attrs + $this->flattenExtra($r->extra_data, array_values($attrs));
    }

    public function unitFeatures(string $which, ?int $unitId, ?array $bbox): array
    {
        $types = match ($which) {
            'center' => ['center'],
            'local' => ['city', 'district', 'local_unit', 'village', 'hamlet'],
            'entity' => ['ministry', 'directorate', 'authority', 'company', 'department', 'office', 'section'],
            default => ['center'],
        };
        $q = OrgUnit::active()->whereIn('type', $types)->whereNotNull('latitude')->whereNotNull('longitude');
        if ($unitId) $q->subtreeOf($unitId);
        $this->bbox($q, $bbox);

        return $q->limit(self::MAX_FEATURES)->get()->map(fn (OrgUnit $u) => $this->point($u->latitude, $u->longitude, [
            'id' => $u->id, 'layer' => 'unit:' . $which, 'kind' => 'unit',
            'title' => $u->name, 'category' => $u->type_label, 'unit_id' => $u->id, 'icon' => $u->icon,
            'attrs' => array_filter([
                'النوع' => $u->type_label, 'الكود' => $u->code, 'رئيس الجهة' => trim(($u->head_title ? $u->head_title . ': ' : '') . $u->head_name),
                'التليفون' => $u->phone, 'البريد' => $u->email, 'العنوان' => $u->address,
                'عدد السكان' => $u->population ? number_format($u->population) : null,
                'المساحة (كم²)' => $u->area_km2 ? number_format((float) $u->area_km2, 1) : null,
            ], fn ($v) => $v !== null && $v !== ''),
        ]))->all();
    }

    public function formFeatures(?User $user, int $templateId, ?int $unitId): array
    {
        $tpl = $this->geoTemplates($user)->first(fn ($t) => $t['template']->id === $templateId);
        if (! $tpl) return [];

        $subIds = $tpl['submissions'];
        if ($unitId) {
            $ids = OrgUnit::subtreeOf($unitId)->pluck('id');
            $subIds = FormSubmission::whereIn('id', $subIds)->whereIn('org_unit_id', $ids)->pluck('id');
        }
        $fields = $tpl['template']->formFields->keyBy('id');
        $titleField = $fields->first(fn ($f) => preg_match('/اسم|الاسم|name/iu', $f->label . ' ' . $f->name));
        $subs = FormSubmission::whereIn('id', $subIds)->with('orgUnit:id,name')->get(['id', 'reference_number', 'status', 'org_unit_id'])->keyBy('id');

        $features = [];
        FormSubmissionData::whereIn('form_submission_id', $subIds)
            ->orderBy('form_submission_id')->orderBy('row_index')
            ->get(['form_submission_id', 'form_field_id', 'row_index', 'value'])
            ->groupBy(fn ($d) => $d->form_submission_id . '#' . $d->row_index)
            ->each(function ($cells) use (&$features, $tpl, $fields, $titleField, $subs) {
                if (count($features) >= self::MAX_FEATURES) return false;
                $byField = $cells->pluck('value', 'form_field_id');
                $pair = static::normalizePair($byField[$tpl['lng']->id] ?? null, $byField[$tpl['lat']->id] ?? null);
                if (! $pair) return;
                $first = $cells->first();
                $sub = $subs[$first->form_submission_id] ?? null;
                $attrs = [];
                foreach ($fields as $fid => $f) {
                    if ($fid === $tpl['lat']->id || $fid === $tpl['lng']->id) continue;
                    $v = trim((string) ($byField[$fid] ?? ''));
                    $label = trim(preg_replace('/\s+/u', ' ', $f->label));
                    if ($v !== '' && $v !== '-' && $v !== 'ـــ' && $label !== 'م') $attrs[$label] = $v;
                }
                $features[] = $this->point($pair[0], $pair[1], [
                    'id' => $first->form_submission_id . '-' . $first->row_index, 'layer' => 'form:' . $tpl['template']->id, 'kind' => 'form',
                    'title' => ($titleField ? trim((string) ($byField[$titleField->id] ?? '')) : '') ?: ($tpl['template']->name . ' — صف ' . ($first->row_index + 1)),
                    'category' => $tpl['template']->name, 'center' => $sub?->orgUnit?->name,
                    'submission_id' => $first->form_submission_id, 'reference' => $sub?->reference_number,
                    'url' => route('submissions.show', $first->form_submission_id),
                    'attrs' => $attrs,
                ]);
            });

        return $features;
    }

    /* =====================================================================
     | Unit indicators
     * ===================================================================== */

    /** المراكز مع مؤشرات للدوائر الموضوعية */
    public function units(?User $user): array
    {
        $refIds = $this->referenceUnitIds($user);
        $centers = OrgUnit::active()->where('type', 'center')->orderBy('sort_order')->orderBy('name')->get();
        $all = OrgUnit::get(['id', 'path', 'type']);

        $facByUnit = $this->scopeRef(FacilityData::query(), $user)->whereNotNull('org_unit_id')
            ->select('org_unit_id', 'module_code', DB::raw('count(*) c'))->groupBy('org_unit_id', 'module_code')->get();
        $secByUnit = $this->scopeRef(SectorData::query(), $user)->whereNotNull('org_unit_id')
            ->select('org_unit_id', 'sector', DB::raw('count(*) c'))->groupBy('org_unit_id', 'sector')->get();
        $subsByUnit = DataScope::submissions(FormSubmission::query(), $user)->whereNotNull('org_unit_id')
            ->select('org_unit_id', DB::raw('count(*) c'))->groupBy('org_unit_id')->pluck('c', 'org_unit_id');
        $usersByUnit = User::whereNotNull('org_unit_id')->select('org_unit_id', DB::raw('count(*) c'))->groupBy('org_unit_id')->pluck('c', 'org_unit_id');

        $out = [];
        foreach ($centers as $c) {
            if ($refIds !== null && ! in_array($c->id, $refIds, true)) continue;
            $sub = $all->filter(fn ($u) => str_starts_with($u->path, $c->path));
            $ids = $sub->pluck('id')->flip();
            $fac = $facByUnit->filter(fn ($r) => isset($ids[$r->org_unit_id]));
            $sec = $secByUnit->filter(fn ($r) => isset($ids[$r->org_unit_id]));
            $ind = [
                'facilities' => (int) $fac->sum('c'),
                'sector_records' => (int) $sec->sum('c'),
                'submissions' => (int) $sub->sum(fn ($u) => $subsByUnit[$u->id] ?? 0),
                'users' => (int) $sub->sum(fn ($u) => $usersByUnit[$u->id] ?? 0),
                'villages' => $sub->whereIn('type', ['village', 'local_unit'])->count(),
                'hamlets' => $sub->where('type', 'hamlet')->count(),
                'population' => (int) ($c->population ?? 0),
            ];
            foreach ($fac->groupBy('module_code') as $code => $rows) $ind['fac:' . $code] = (int) $rows->sum('c');
            foreach ($sec->groupBy('sector') as $s => $rows) $ind['sec:' . $s] = (int) $rows->sum('c');

            $out[] = [
                'id' => $c->id, 'name' => $c->name, 'code' => $c->code,
                'lat' => $c->latitude, 'lng' => $c->longitude, 'indicators' => $ind,
            ];
        }

        return $out;
    }

    /** قائمة المؤشرات المتاحة للدوائر الموضوعية */
    public function indicatorCatalog(?User $user): array
    {
        $cat = [
            'facilities' => 'إجمالي المرافق والمنشآت', 'sector_records' => 'سجلات القطاعات', 'submissions' => 'إرسالات النماذج',
            'users' => 'الحسابات', 'villages' => 'الوحدات المحلية والقرى', 'hamlets' => 'العزب والكفور والنجوع',
        ];
        foreach ($this->scopeRef(FacilityData::query(), $user)->select('module_code', 'module_name')->distinct()->get() as $m) {
            $cat['fac:' . $m->module_code] = $m->module_name ?: $m->module_code;
        }
        foreach (self::SECTORS as $k => [$label]) $cat['sec:' . $k] = 'قطاع ' . $label;
        return $cat;
    }

    /** بطاقة تفاصيل جهة */
    public function unit(?User $user, OrgUnit $unit): array
    {
        $subtree = OrgUnit::subtreeOf($unit)->pluck('id');
        $anchor = in_array($unit->type, ['city', 'district', 'local_unit', 'village', 'hamlet'], true) ? ($unit->nearestCenter() ?? $unit) : $unit;
        $anchorIds = $anchor->id === $unit->id ? $subtree : OrgUnit::subtreeOf($anchor)->pluck('id');

        $facQ = fn () => $this->scopeRef(FacilityData::query(), $user)->whereIn('org_unit_id', $anchorIds);
        $secQ = fn () => $this->scopeRef(SectorData::query(), $user)->whereIn('org_unit_id', $anchorIds);

        $coords = [$unit->latitude, $unit->longitude];
        if (! $unit->latitude) {
            $withGeo = $unit->ancestors()->reverse()->first(fn ($a) => $a->latitude && $a->longitude);
            if ($withGeo) $coords = [$withGeo->latitude, $withGeo->longitude];
        }

        $children = OrgUnit::where('parent_id', $unit->id)->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'type', 'latitude', 'longitude']);

        return [
            'unit' => [
                'id' => $unit->id, 'name' => $unit->name, 'type' => $unit->type, 'type_label' => $unit->type_label, 'icon' => $unit->icon,
                'code' => $unit->code, 'breadcrumb' => $unit->ancestors()->pluck('name')->all(),
                'lat' => $coords[0], 'lng' => $coords[1], 'approx' => ! $unit->latitude,
                'head' => trim(($unit->head_title ? $unit->head_title . ': ' : '') . $unit->head_name),
                'phone' => $unit->phone, 'email' => $unit->email, 'address' => $unit->address,
                'population' => $unit->population, 'area_km2' => $unit->area_km2, 'description' => $unit->description,
                'is_partial' => $anchor->id !== $unit->id ? $anchor->name : null,
            ],
            'children_by_type' => $children->groupBy('type')->map(fn ($g, $t) => ['label' => OrgUnit::TYPES[$t] ?? $t, 'count' => $g->count()])->values()->all(),
            'children' => $children->take(60)->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => OrgUnit::TYPES[$c->type] ?? $c->type, 'geo' => (bool) $c->latitude])->values()->all(),
            'children_total' => $children->count(),
            'descendants_total' => max(0, $subtree->count() - 1),
            'facilities' => $facQ()->select('module_code', 'module_name', DB::raw('count(*) c'))->groupBy('module_code', 'module_name')->orderByDesc('c')->get()
                ->map(fn ($r) => ['layer' => 'fac:' . $r->module_code, 'label' => $r->module_name, 'count' => (int) $r->c])->all(),
            'sectors' => $secQ()->select('sector', DB::raw('count(*) c'))->groupBy('sector')->orderByDesc('c')->get()
                ->map(fn ($r) => ['label' => self::SECTORS[$r->sector][0] ?? $r->sector, 'count' => (int) $r->c])->all(),
            'recent_places' => $facQ()->whereNotNull('latitude')->latest('id')->limit(8)->get(['id', 'name', 'module_name', 'latitude', 'longitude'])
                ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'category' => $f->module_name, 'lat' => $f->latitude, 'lng' => $f->longitude])->all(),
            'submissions' => DataScope::submissions(FormSubmission::query(), $user)->whereIn('org_unit_id', $subtree)
                ->select('status', DB::raw('count(*) c'))->groupBy('status')->pluck('c', 'status'),
            'users' => User::whereIn('org_unit_id', $subtree)->count(),
            'positions' => JobPosition::whereIn('org_unit_id', $subtree)->count(),
            'vacant_positions' => JobPosition::whereIn('org_unit_id', $subtree)->whereNull('user_id')->count(),
            'templates' => $unit->formTemplates()->pluck('name')->take(10)->all(),
        ];
    }

    /* =====================================================================
     | Search
     * ===================================================================== */

    public function search(?User $user, string $q, int $limit = 20): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) return [];
        $like = '%' . $q . '%';
        $out = [];

        foreach (OrgUnit::active()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like))
                     ->orderBy('depth')->limit(8)->get() as $u) {
            $geo = $u->latitude ? $u : $u->ancestors()->reverse()->first(fn ($a) => $a->latitude);
            $out[] = ['type' => 'unit', 'id' => $u->id, 'title' => $u->name, 'subtitle' => $u->type_label . ($u->parent ? ' — ' . $u->parent->name : ''),
                'icon' => $u->icon, 'lat' => $geo?->latitude, 'lng' => $geo?->longitude, 'approx' => ! $u->latitude];
        }

        foreach ($this->scopeRef(FacilityData::query(), $user)->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('address', 'like', $like))
                     ->orderByRaw('latitude is null')->limit($limit)->get() as $f) {
            $out[] = ['type' => 'facility', 'id' => $f->id, 'layer' => 'fac:' . $f->module_code, 'title' => $f->name,
                'subtitle' => trim($f->module_name . ' — ' . ($f->center_name ?: $f->address), ' —'),
                'icon' => self::MODULE_ICONS[$f->module_code] ?? 'fa-location-dot', 'lat' => $f->latitude, 'lng' => $f->longitude];
        }

        foreach ($this->scopeRef(SectorData::query(), $user)->whereNotNull('latitude')
                     ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('address', 'like', $like)->orWhere('extra_data', 'like', '%' . trim(json_encode($q), '"') . '%'))
                     ->limit(10)->get() as $r) {
            $out[] = ['type' => 'sector', 'id' => $r->id, 'layer' => 'sec:' . $r->sector . ':' . $r->subcategory, 'title' => $this->sectorTitle($r),
                'subtitle' => (self::SECTORS[$r->sector][0] ?? $r->sector) . ' — ' . $r->subcategory, 'icon' => self::SECTORS[$r->sector][1] ?? 'fa-database',
                'lat' => $r->latitude, 'lng' => $r->longitude];
        }

        return array_slice($out, 0, 30);
    }

    /* =====================================================================
     | Normalisation (used by kfs:geo-normalize and model hooks)
     * ===================================================================== */

    /** @return array{checked:int, located:int, rejected:int} */
    public static function normalizeTable(string $table, bool $force = false): array
    {
        $stats = ['checked' => 0, 'located' => 0, 'rejected' => 0, 'linked' => 0, 'nearest' => 0];
        $centerMap = OrgUnit::where('type', 'center')->whereNotNull('legacy_center_id')->pluck('id', 'legacy_center_id');

        $q = DB::table($table)->whereNotNull('x_coord')->whereNotNull('y_coord');
        if (! $force) $q->whereNull('latitude');
        $q->orderBy('id')->chunkById(500, function ($rows) use ($table, &$stats) {
            foreach ($rows as $r) {
                $stats['checked']++;
                $pair = static::normalizePair($r->x_coord, $r->y_coord);
                if ($pair) {
                    DB::table($table)->where('id', $r->id)->update(['latitude' => $pair[0], 'longitude' => $pair[1]]);
                    $stats['located']++;
                } else {
                    DB::table($table)->where('id', $r->id)->update(['latitude' => null, 'longitude' => null]);
                    $stats['rejected']++;
                }
            }
        });

        // ربط الجهة من المركز القديم إن لم تكن مربوطة
        foreach ($centerMap as $legacy => $unitId) {
            $stats['linked'] += DB::table($table)->whereNull('org_unit_id')->where('center_id', $legacy)->update(['org_unit_id' => $unitId]);
        }

        // النقاط غير المربوطة بمركز: تُنسب لأقرب مركز إداري (تقريب مكاني)
        $centers = OrgUnit::where('type', 'center')->whereNotNull('latitude')->get(['id', 'latitude', 'longitude']);
        if ($centers->isNotEmpty()) {
            DB::table($table)->whereNull('org_unit_id')->whereNotNull('latitude')->orderBy('id')
                ->chunkById(500, function ($rows) use ($table, $centers, &$stats) {
                    foreach ($rows as $r) {
                        $nearest = $centers->sortBy(fn ($c) => ($c->latitude - $r->latitude) ** 2 + (($c->longitude - $r->longitude) * 0.86) ** 2)->first();
                        DB::table($table)->where('id', $r->id)->update(['org_unit_id' => $nearest->id]);
                        $stats['nearest']++;
                    }
                });
        }

        return $stats;
    }

    /** يُستدعى من خطافات النماذج عند الحفظ (للصفوف المستوردة مستقبلاً) */
    public static function fillModelCoordinates(\Illuminate\Database\Eloquent\Model $m): void
    {
        if (($m->latitude === null || $m->isDirty(['x_coord', 'y_coord'])) && $m->x_coord !== null && $m->y_coord !== null) {
            $pair = static::normalizePair($m->x_coord, $m->y_coord);
            $m->latitude = $pair[0] ?? null;
            $m->longitude = $pair[1] ?? null;
        }
        if (! $m->org_unit_id && $m->center_id) {
            $m->org_unit_id = OrgUnit::where('type', 'center')->where('legacy_center_id', $m->center_id)->value('id');
        }
    }
}
