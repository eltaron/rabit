<?php

namespace App\Filament\Widgets;

use App\Filament\Support\OctagonChart;
use App\Models\StrategicData;

class StrategicCategoryChart extends OctagonChart
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'البيانات الاستراتيجية حسب الفئة';

    protected function getData(): array
    {
        $counts = StrategicData::query()
            ->selectRaw("COALESCE(NULLIF(category, ''), 'غير محدد') as k, COUNT(*) as c")
            ->groupBy('k')->orderByDesc('c')->pluck('c', 'k');

        return $this->dataset($counts, 'عدد المواقع');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
