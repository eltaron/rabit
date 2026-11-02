<?php

namespace App\Filament\Widgets;

use App\Filament\Support\OctagonChart;
use App\Models\Vehicle;

class VehiclesStatusChart extends OctagonChart
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'المركبات حسب الحالة';

    protected function getData(): array
    {
        $counts = Vehicle::query()
            ->selectRaw("COALESCE(NULLIF(status, ''), 'غير محدد') as k, COUNT(*) as c")
            ->groupBy('k')->orderByDesc('c')->pluck('c', 'k');

        return $this->dataset($counts, 'المركبات');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return $this->hideScalesOptions();
    }
}
