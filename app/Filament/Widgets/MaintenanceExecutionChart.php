<?php

namespace App\Filament\Widgets;

use App\Filament\Support\OctagonChart;
use App\Models\MaintenancePlan;

class MaintenanceExecutionChart extends OctagonChart
{
    protected static ?int $sort = 8;

    protected ?string $heading = 'خطط الصيانة: المخطط مقابل المنفذ';

    /** القيم التي تعني "لا يوجد" في خانات الأسابيع */
    private const EMPTY_MARKS = ['', '-', '—', '0', 'x', 'X', '×'];

    protected function getData(): array
    {
        $marked = fn (string $col) => MaintenancePlan::query()
            ->whereNotNull($col)
            ->whereNotIn($col, self::EMPTY_MARKS)
            ->count();

        $planned = [];
        $executed = [];
        foreach (range(1, 4) as $w) {
            $planned[] = $marked("week{$w}_planned");
            $executed[] = $marked("week{$w}_executed");
        }

        return [
            'datasets' => [
                ['label' => 'مخطط', 'data' => $planned, 'backgroundColor' => self::PALETTE[1], 'borderColor' => self::PALETTE[1]],
                ['label' => 'منفذ', 'data' => $executed, 'backgroundColor' => self::PALETTE[2], 'borderColor' => self::PALETTE[2]],
            ],
            'labels' => ['الأسبوع 1', 'الأسبوع 2', 'الأسبوع 3', 'الأسبوع 4'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']]];
    }
}
