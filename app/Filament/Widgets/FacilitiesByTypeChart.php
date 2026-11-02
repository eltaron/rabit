<?php

namespace App\Filament\Widgets;

use App\Filament\Support\OctagonChart;
use App\Models\FacilityData;

class FacilitiesByTypeChart extends OctagonChart
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'المنشآت والمرافق حسب النوع';

    protected function getData(): array
    {
        $counts = FacilityData::query()
            ->selectRaw('module_name as k, COUNT(*) as c')
            ->groupBy('k')->orderByDesc('c')->pluck('c', 'k');

        return $this->dataset($counts, 'عدد المنشآت');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]]];
    }
}
