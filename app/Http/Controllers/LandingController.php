<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * الصفحة الرئيسية العامة: إحصاءات حقيقية من قاعدة البيانات + حدود المحافظة كـ SVG.
 */
class LandingController extends Controller
{
    private const VIEW_W = 600;
    private const VIEW_H = 420;

    public function __invoke(): View
    {
        $data = Cache::remember('landing.v2', 600, function () {
            $outline = $this->outline();

            return [
                'stats' => $this->stats(),
                'centers' => $this->centers($outline['project']),
                'outlinePath' => $outline['path'],
            ];
        });

        return view('welcome', $data);
    }

    private function stats(): array
    {
        $count = function (string $table, ?callable $where = null): int {
            try {
                if (! Schema::hasTable($table)) return 0;
                $q = DB::table($table);
                if ($where) $where($q);
                return (int) $q->count();
            } catch (\Throwable) {
                return 0;
            }
        };
        $units = fn (array $types) => $count('org_units', fn ($q) => $q->whereIn('type', $types)->whereNull('deleted_at'));

        return [
            ['icon' => 'fa-city', 'value' => $units(['center']), 'label' => 'مركز ومدينة'],
            ['icon' => 'fa-house-flag', 'value' => $units(['city', 'district', 'local_unit']), 'label' => 'مدينة وحي ووحدة محلية'],
            ['icon' => 'fa-house-chimney', 'value' => $units(['village', 'hamlet']), 'label' => 'قرية وتابع'],
            ['icon' => 'fa-building-columns', 'value' => $units(['sector', 'ministry', 'directorate', 'authority', 'company', 'department', 'section', 'office']), 'label' => 'جهة حكومية مسجلة'],
            ['icon' => 'fa-location-dot', 'value' => $count('facilities_data') + $count('sector_data'), 'label' => 'سجل مكاني ووصفي'],
            ['icon' => 'fa-file-lines', 'value' => $count('form_templates', fn ($q) => $q->whereNull('deleted_at')), 'label' => 'نموذج بيانات'],
        ];
    }

    /** يحوّل حدود المحافظة إلى مسار SVG ودالة إسقاط للإحداثيات */
    private function outline(): array
    {
        $identity = ['path' => '', 'project' => null];
        $file = public_path('data/kafr-el-sheikh.geojson');
        if (! is_file($file)) return $identity;

        $geo = json_decode(file_get_contents($file), true);
        $ring = $geo['features'][0]['geometry']['coordinates'][0] ?? [];
        if (count($ring) < 3) return $identity;

        $lons = array_column($ring, 0);
        $lats = array_column($ring, 1);
        [$minX, $maxX, $minY, $maxY] = [min($lons), max($lons), min($lats), max($lats)];
        $k = cos(deg2rad(($minY + $maxY) / 2)); // تصحيح تقريبي لنسبة الطول/العرض
        $pad = 20;
        $scale = min((self::VIEW_W - 2 * $pad) / (($maxX - $minX) * $k), (self::VIEW_H - 2 * $pad) / ($maxY - $minY));
        $offX = (self::VIEW_W - ($maxX - $minX) * $k * $scale) / 2;
        $offY = (self::VIEW_H - ($maxY - $minY) * $scale) / 2;

        // RTL page but map stays geographic (north up, east right)
        $project = fn (float $lon, float $lat) => [
            round($offX + ($lon - $minX) * $k * $scale, 1),
            round($offY + ($maxY - $lat) * $scale, 1),
        ];

        $d = '';
        foreach ($ring as $i => [$lon, $lat]) {
            [$x, $y] = $project($lon, $lat);
            $d .= ($i ? 'L' : 'M') . $x . ' ' . $y;
        }

        return ['path' => $d . 'Z', 'project' => $project];
    }

    private function centers(?callable $project): array
    {
        try {
            return DB::table('org_units')->where('type', 'center')->whereNull('deleted_at')
                ->orderBy('sort_order')->orderBy('name')
                ->get(['name', 'latitude', 'longitude'])
                ->map(function ($c) use ($project) {
                    $pt = ($project && $c->latitude && $c->longitude) ? $project((float) $c->longitude, (float) $c->latitude) : null;
                    return [
                        'name' => $c->name,
                        'short' => trim(preg_replace('/^مركز\s+/u', '', $c->name)),
                        'x' => $pt[0] ?? null,
                        'y' => $pt[1] ?? null,
                    ];
                })->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
