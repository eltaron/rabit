<?php

namespace App\Http\Controllers;

use App\Models\FacilityData;
use App\Models\OrgUnit;
use App\Services\MapDataService;
use App\Support\ExcelSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الخريطة التفاعلية لمحافظة كفر الشيخ (البيانات المكانية والوصفية).
 */
class MapController extends Controller
{
    public function __construct(private MapDataService $map) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // فلتر الجهات الجغرافية (المراكز وما دونها حتى الوحدات المحلية)
        $unitOptions = OrgUnit::treeOptions(null, ['governorate', 'center', 'city', 'district', 'local_unit']);

        $focus = null;
        if ($fid = $request->integer('facility')) {
            $f = FacilityData::find($fid);
            if ($f) $focus = ['type' => 'facility', 'id' => $f->id, 'layer' => 'fac:' . $f->module_code, 'lat' => $f->latitude, 'lng' => $f->longitude, 'title' => $f->name];
        }

        return view('map.index', [
            'unitOptions' => $unitOptions,
            'indicators' => $this->map->indicatorCatalog($user),
            'initial' => [
                'unit' => $request->integer('unit') ?: null,
                'layers' => array_values(array_filter(explode(',', (string) $request->input('layer')))),
                'focus' => $focus,
            ],
            'boundary' => MapDataService::boundary(),
        ]);
    }

    public function layers(Request $request): JsonResponse
    {
        return response()->json([
            'groups' => $this->map->layers($request->user()),
        ]);
    }

    public function features(Request $request): JsonResponse
    {
        $request->validate(['layer' => ['required', 'string', 'max:300']]);
        $bbox = $request->filled('bbox') ? array_map('floatval', explode(',', $request->input('bbox'))) : null;

        return response()->json($this->map->features(
            $request->user(),
            $request->input('layer'),
            $request->integer('unit') ?: null,
            $bbox && count($bbox) === 4 ? $bbox : null,
            $request->integer('facility') ?: null,
        ));
    }

    public function units(Request $request): JsonResponse
    {
        return response()->json([
            'centers' => $this->map->units($request->user()),
            'indicators' => $this->map->indicatorCatalog($request->user()),
        ]);
    }

    public function unit(Request $request, OrgUnit $orgUnit): JsonResponse
    {
        return response()->json($this->map->unit($request->user(), $orgUnit));
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->map->search($request->user(), (string) $request->input('q'))]);
    }

    /** توافق مع الروابط القديمة: ?center_id=<legacy centers.id> */
    public function centerData(Request $request): JsonResponse
    {
        $legacy = $request->integer('center_id') ?: $request->integer('center');
        $unit = OrgUnit::where('type', 'center')->where('legacy_center_id', $legacy)->first()
            ?? OrgUnit::where('legacy_center_id', $legacy)->first();
        abort_unless($unit, 404, 'المركز غير موجود');

        return response()->json($this->map->unit($request->user(), $unit));
    }

    /** تصدير المعالم المعروضة (طبقة أو أكثر) إلى Excel */
    public function export(Request $request)
    {
        $layers = array_slice(array_filter(explode(',', (string) $request->input('layers'))), 0, 30);
        abort_if(! $layers, 422, 'لم يتم اختيار طبقات للتصدير');
        $bbox = $request->filled('bbox') ? array_map('floatval', explode(',', $request->input('bbox'))) : null;
        $unit = $request->integer('unit') ?: null;

        $rows = [];
        $attrKeys = [];
        foreach ($layers as $layer) {
            $fc = $this->map->features($request->user(), $layer, $unit, $bbox && count($bbox) === 4 ? $bbox : null);
            foreach ($fc['features'] as $f) {
                $p = $f['properties'];
                $row = [
                    'title' => $p['title'], 'category' => $p['category'] ?? '', 'center' => $p['center'] ?? '',
                    'lat' => $f['geometry']['coordinates'][1], 'lng' => $f['geometry']['coordinates'][0],
                ];
                foreach ($p['attrs'] ?? [] as $k => $v) {
                    $key = 'a_' . md5($k);
                    $attrKeys[$key] = $k;
                    $row[$key] = $v;
                }
                $rows[] = $row;
            }
        }

        $columns = [
            ['key' => 'title', 'label' => 'الاسم', 'width' => 34],
            ['key' => 'category', 'label' => 'الطبقة / التصنيف', 'width' => 28],
            ['key' => 'center', 'label' => 'المركز / الجهة', 'width' => 22],
            ['key' => 'lat', 'label' => 'خط العرض', 'width' => 14],
            ['key' => 'lng', 'label' => 'خط الطول', 'width' => 14],
        ];
        foreach (array_slice($attrKeys, 0, 80, true) as $key => $label) {
            $columns[] = ['key' => $key, 'label' => $label];
        }

        $ss = ExcelSheet::build('بيانات الخريطة', $columns, $rows,
            'بيانات مصدّرة من الخريطة التفاعلية بتاريخ ' . now()->format('Y-m-d H:i') . ' — عدد المعالم: ' . count($rows));

        return ExcelSheet::download($ss, 'خريطة-كفر-الشيخ-' . now()->format('Ymd-His'));
    }
}
