<?php

namespace App\Services;

use App\Models\FormSubmission;
use Illuminate\Support\Collection;

class ReportVisualizer
{
    protected array $palette = [
        '#0d1b3e', '#c5a035', '#059669', '#4a7dff', '#dc2626',
        '#d97706', '#7c3aed', '#0891b2', '#db2777', '#65a30d',
        '#ea580c', '#64748b',
    ];

    protected array $excludeNumericNames = [
        'x', 'y', 'lat', 'lng', 'long', 'phone', 'tel', 'fax', 'id', 'code',
    ];

    protected array $excludeNumericKeywords = [
        'كود', 'تليفون', 'هاتف', 'فاكس', 'رقم', 'احداثى', 'احداثيات',
        'عرض', 'طول', 'code', 'phone', 'serial', 'مسلسل',
    ];

    public function analyze(?FormSubmission $submission): array
    {
        if (! $submission) {
            return [
                'table' => ['headers' => [], 'rows' => collect()],
                'kpis' => [],
                'charts' => [],
            ];
        }

        $data = $submission->submissionData()->with('formField')->get();

        $rows = $data
            ->groupBy(fn ($item) => $item->row_index ?? 0)
            ->map(function (Collection $group) {
                return $group->mapWithKeys(function ($item) {
                    $label = $item->formField->label ?? "field_{$item->form_field_id}";

                    return [$label => $item->value ?? '-'];
                });
            })
            ->values();

        $headers = $data->pluck('formField.label')->filter()->unique()->values()->all();

        $nameByLabel = $data->reduce(function (array $carry, $item) {
            $field = $item->formField;
            if ($field) {
                $carry[$field->label] = $field->name;
            }

            return $carry;
        }, []);

        $columns = [];
        foreach ($headers as $label) {
            $values = $rows
                ->map(fn ($r) => $r[$label] ?? null)
                ->filter(fn ($v) => $v !== null && $v !== '-' && $v !== '')
                ->values();

            $columns[$label] = $this->profile($label, $values, $nameByLabel[$label] ?? null);
        }

        $rowLabel = $this->detectLabelField($columns, $rows);

        return [
            'table' => ['headers' => $headers, 'rows' => $rows],
            'kpis' => $this->buildKpis($columns, $rows),
            'charts' => $this->buildCharts($columns, $rows, $rowLabel),
        ];
    }

    protected function profile(string $label, Collection $values, ?string $fieldName = null): array
    {
        $numeric = [];
        foreach ($values as $v) {
            $parsed = $this->parseNumber($v);
            if ($parsed !== null) {
                $numeric[] = $parsed;
            }
        }

        $total = $values->count();
        $numericRatio = $total ? count($numeric) / $total : 0;

        return [
            'label' => $label,
            'name' => mb_strtolower(trim($label)),
            'fieldName' => $fieldName ? mb_strtolower(trim($fieldName)) : null,
            'total' => $total,
            'distinct' => $values->unique()->count(),
            'numericValues' => $numeric,
            'isNumeric' => $numericRatio >= 0.7 && ! $this->isExcludedNumeric($label, $fieldName),
        ];
    }

    protected function isExcludedNumeric(string $label, ?string $fieldName = null): bool
    {
        $name = mb_strtolower(trim($label));
        $fieldName = $fieldName ? mb_strtolower(trim($fieldName)) : $name;

        foreach ($this->excludeNumericNames as $n) {
            if ($name === $n || $fieldName === $n) {
                return true;
            }
        }

        foreach ($this->excludeNumericKeywords as $keyword) {
            if (mb_strpos($name, $keyword) !== false || mb_strpos($fieldName, $keyword) !== false) {
                return true;
            }
        }

        if (preg_match('/^col_\d+$/', $fieldName) || preg_match('/^col_\d+$/', $name)) {
            return true;
        }

        return false;
    }

    protected function parseNumber(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(str_replace([',', '٬', ' '], '', (string) $value));

        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    protected function detectLabelField(array $columns, Collection $rows): ?string
    {
        $preferred = ['الاسم', 'اسم', 'اسم الجهة', 'الجهة', 'المنشأة', 'اسم المنشأة'];

        foreach ($preferred as $p) {
            if (isset($columns[$p]) && ! $columns[$p]['isNumeric']) {
                return $p;
            }
        }

        foreach ($columns as $col) {
            if ($col['isNumeric'] || $col['total'] === 0) {
                continue;
            }

            if ($col['distinct'] < $col['total'] || $col['total'] > 200) {
                continue;
            }

            $avgLength = (float) $rows->avg(fn ($r) => mb_strlen((string) ($r[$col['label']] ?? '')));

            if ($avgLength > 0 && $avgLength <= 40) {
                return $col['label'];
            }
        }

        return null;
    }

    protected function buildKpis(array $columns, Collection $rows): array
    {
        $kpis = [
            [
                'label' => 'إجمالي السجلات',
                'value' => number_format($rows->count()),
                'icon' => 'fa-table',
                'color' => '#0d1b3e',
            ],
        ];

        foreach ($columns as $col) {
            if (! $col['isNumeric'] || ! $col['total']) {
                continue;
            }

            $sum = array_sum($col['numericValues']);
            $avg = $sum / count($col['numericValues']);

            $kpis[] = [
                'label' => 'إجمالي ' . $col['label'],
                'value' => number_format($sum),
                'icon' => 'fa-calculator',
                'color' => '#059669',
            ];

            $kpis[] = [
                'label' => 'متوسط ' . $col['label'],
                'value' => number_format($avg, 1),
                'icon' => 'fa-chart-line',
                'color' => '#4a7dff',
            ];

            if (count($kpis) >= 7) {
                break;
            }
        }

        return $kpis;
    }

    protected function buildCharts(array $columns, Collection $rows, ?string $rowLabel): array
    {
        $charts = [];
        $index = 0;

        foreach ($columns as $col) {
            if ($col['isNumeric'] || $col['distinct'] < 2 || $col['total'] < 5) {
                continue;
            }

            if ($col['distinct'] / max(1, $col['total']) > 0.6) {
                continue;
            }

            if ($this->isExcludedNumeric($col['label'], $col['fieldName'])) {
                continue;
            }

            $counts = [];
            foreach ($rows as $r) {
                $value = trim((string) ($r[$col['label']] ?? ''));

                if ($value === '' || $value === '-') {
                    continue;
                }

                $counts[$value] = ($counts[$value] ?? 0) + 1;
            }

            arsort($counts);

            if (count($counts) < 2) {
                continue;
            }

            $labels = array_keys($counts);
            $values = array_values($counts);

            if (count($labels) > 12) {
                $labels = array_slice($labels, 0, 12);
                $kept = array_slice($values, 0, 12);
                $rest = array_sum(array_slice($values, 12));

                $labels[] = 'أخرى';
                $values = $kept;
                $values[] = $rest;
            }

            $colors = [];
            foreach ($labels as $i => $_label) {
                $colors[] = $this->palette[$i % count($this->palette)];
            }

            $charts[] = [
                'key' => 'chart_' . (++$index),
                'title' => 'توزيع ' . $col['label'],
                'summary' => 'عدد القيم المختلفة: ' . count($labels),
                'type' => 'doughnut',
                'types' => ['doughnut', 'pie', 'bar', 'polarArea'],
                'labels' => $labels,
                'datasets' => [[
                    'label' => $col['label'],
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]],
            ];
        }

        foreach ($columns as $col) {
            if (! $col['isNumeric'] || count($col['numericValues']) < 2) {
                continue;
            }

            if ($rows->count() <= 60) {
                $labels = [];
                $values = [];

                foreach ($rows as $idx => $r) {
                    $raw = $r[$col['label']] ?? null;
                    $labels[] = $rowLabel
                        ? ($r[$rowLabel] ?? ('سجل ' . ($idx + 1)))
                        : ('سجل ' . ($idx + 1));
                    $values[] = $this->parseNumber($raw) ?? 0;
                }

                $avg = array_sum($values) / max(1, count($values));

                $charts[] = [
                    'key' => 'chart_' . (++$index),
                    'title' => $col['label'],
                    'summary' => 'متوسط القيم: ' . number_format($avg, 1),
                    'type' => 'bar',
                    'types' => ['bar', 'line', 'radar'],
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => $col['label'],
                        'data' => $values,
                        'backgroundColor' => '#0d1b3e',
                        'borderColor' => '#0d1b3e',
                        'borderWidth' => 1,
                    ]],
                ];
            } else {
                $nums = $col['numericValues'];
                $min = min($nums);
                $max = max($nums);
                $histogram = $this->histogram($nums, $min, $max);

                $charts[] = [
                    'key' => 'chart_' . (++$index),
                    'title' => 'توزيع ' . $col['label'],
                    'summary' => 'الحد الأدنى: ' . number_format($min, 1) . ' | الحد الأقصى: ' . number_format($max, 1),
                    'type' => 'bar',
                    'types' => ['bar', 'line'],
                    'labels' => array_keys($histogram),
                    'datasets' => [[
                        'label' => 'عدد السجلات',
                        'data' => array_values($histogram),
                        'backgroundColor' => '#c5a035',
                        'borderColor' => '#c5a035',
                        'borderWidth' => 1,
                    ]],
                ];
            }
        }

        return $charts;
    }

    protected function histogram(array $nums, float $min, float $max): array
    {
        $bucketsCount = 8;

        if ($min == $max) {
            return [$min . '' => count($nums)];
        }

        $range = $max - $min;
        $step = $range / $bucketsCount;

        $buckets = [];
        for ($b = 0; $b < $bucketsCount; $b++) {
            $lo = $min + ($b * $step);
            $hi = ($b === $bucketsCount - 1) ? $max : $min + (($b + 1) * $step);

            $label = 'من ' . number_format($lo, 1) . ' إلى ' . number_format($hi, 1);
            $buckets[$label] = 0;
        }

        $keys = array_keys($buckets);

        foreach ($nums as $v) {
            $idx = min($bucketsCount - 1, (int) floor(($v - $min) / $step));
            $buckets[$keys[$idx]]++;
        }

        return $buckets;
    }
}
