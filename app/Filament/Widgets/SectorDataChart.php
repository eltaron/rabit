<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SectorDataResource;
use App\Filament\Support\OctagonChart;
use App\Models\SectorData;

class SectorDataChart extends OctagonChart
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'البيانات القطاعية حسب القطاع';

    protected function getData(): array
    {
        $labels = SectorDataResource::fields()['sector']['map'];

        $counts = SectorData::query()
            ->selectRaw('sector, COUNT(*) as c')->groupBy('sector')->orderByDesc('c')
            ->pluck('c', 'sector')
            ->mapWithKeys(fn ($c, $s) => [$labels[$s] ?? $s => $c]);

        return $this->dataset($counts, 'السجلات');
    }

    protected function getType(): string
    {
        return 'polarArea';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']]];
    }
}
