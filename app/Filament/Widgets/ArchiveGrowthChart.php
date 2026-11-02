<?php

namespace App\Filament\Widgets;

use App\Filament\Support\OctagonChart;
use App\Models\FacilityData;
use App\Models\FormSubmission;
use App\Models\MaintenancePlan;
use App\Models\SectorData;
use App\Models\StrategicData;
use App\Models\Vehicle;

class ArchiveGrowthChart extends OctagonChart
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'نمو الأرشيف شهرياً (آخر 12 شهر)';

    private const SOURCES = [
        'المركبات' => Vehicle::class,
        'المنشآت' => FacilityData::class,
        'القطاعية' => SectorData::class,
        'الاستراتيجية' => StrategicData::class,
        'الصيانة' => MaintenancePlan::class,
        'الإرسالات' => FormSubmission::class,
    ];

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $from = $months->first();

        $datasets = [];
        $i = 0;
        foreach (self::SOURCES as $label => $model) {
            $rows = $model::query()
                ->where('created_at', '>=', $from)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
                ->groupBy('ym')->pluck('c', 'ym');

            $color = self::PALETTE[$i++ % count(self::PALETTE)];
            $datasets[] = [
                'label' => $label,
                'data' => $months->map(fn ($m) => (int) ($rows[$m->format('Y-m')] ?? 0))->all(),
                'borderColor' => $color,
                'backgroundColor' => $color,
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months->map(fn ($m) => $m->format('Y-m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']]];
    }
}
