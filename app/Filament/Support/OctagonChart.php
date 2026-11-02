<?php

namespace App\Filament\Support;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

/** أساس مشترك لرسوم Octagon البيانية */
abstract class OctagonChart extends ChartWidget
{
    protected ?string $maxHeight = '300px';

    protected const PALETTE = [
        // هوية رابط: أخضر مصري، ذهبي، ثم ألوان مساندة هادئة
        '#123F35', '#B8954F', '#237A52', '#2D6477', '#A93232', '#B27A24',
        '#216455', '#D1B574', '#5B7F6F', '#7A5C2E', '#3F8C8C', '#8E6F9E',
        '#626963', '#9C7C3F', '#185447', '#C4A25E',
    ];

    /** @param Collection<string,int> $counts */
    protected function dataset(Collection $counts, string $label, bool $multiColor = true): array
    {
        $colors = collect(self::PALETTE)->take(max($counts->count(), 1))->values();
        while ($colors->count() < $counts->count()) {
            $colors = $colors->merge(self::PALETTE);
        }
        $colors = $colors->take($counts->count())->all();

        return [
            'datasets' => [[
                'label' => $label,
                'data' => $counts->values()->all(),
                'backgroundColor' => $multiColor ? $colors : self::PALETTE[1],
                'borderColor' => $multiColor ? $colors : self::PALETTE[1],
            ]],
            'labels' => $counts->keys()->all(),
        ];
    }

    protected function hideScalesOptions(): array
    {
        return [
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
