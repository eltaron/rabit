<?php

namespace App\Filament\Widgets;

use App\Filament\Support\OctagonChart;
use App\Models\FacilityData;
use App\Models\SectorData;

class CentersDistributionChart extends OctagonChart
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'توزيع البيانات على المراكز (أعلى 15)';

    protected function getData(): array
    {
        $count = fn (string $model) => $model::query()
            ->whereNotNull('center_name')->where('center_name', '!=', '')
            ->selectRaw('center_name, COUNT(*) as c')->groupBy('center_name')
            ->pluck('c', 'center_name');

        $facilities = $count(FacilityData::class);
        $sector = $count(SectorData::class);

        $centers = $facilities->keys()->merge($sector->keys())->unique()
            ->sortByDesc(fn ($c) => ($facilities[$c] ?? 0) + ($sector[$c] ?? 0))
            ->take(15)->values();

        return [
            'datasets' => [
                [
                    'label' => 'المنشآت والمرافق',
                    'data' => $centers->map(fn ($c) => (int) ($facilities[$c] ?? 0))->all(),
                    'backgroundColor' => self::PALETTE[0],
                    'borderColor' => self::PALETTE[0],
                ],
                [
                    'label' => 'البيانات القطاعية',
                    'data' => $centers->map(fn ($c) => (int) ($sector[$c] ?? 0))->all(),
                    'backgroundColor' => self::PALETTE[1],
                    'borderColor' => self::PALETTE[1],
                ],
            ],
            'labels' => $centers->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
