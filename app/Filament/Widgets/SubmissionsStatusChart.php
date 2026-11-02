<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FormSubmissionResource;
use App\Filament\Support\OctagonChart;
use App\Models\FormSubmission;

class SubmissionsStatusChart extends OctagonChart
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'إرسالات النماذج حسب الحالة';

    protected function getData(): array
    {
        $labels = FormSubmissionResource::fields()['status']['map'];

        $counts = FormSubmission::query()
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->orderByDesc('c')
            ->pluck('c', 'status')
            ->mapWithKeys(fn ($c, $s) => [$labels[$s] ?? $s => $c]);

        return $this->dataset($counts, 'الإرسالات');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return $this->hideScalesOptions();
    }
}
