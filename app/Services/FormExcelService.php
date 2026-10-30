<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionData;
use App\Models\FormTemplate;
use App\Support\ExcelSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * قوالب Excel لكل نموذج: تحميل قالب فارغ، تصدير البيانات الحالية، وإعادة الرفع للإضافة أو التعديل.
 *
 * مفتاح عمود كل حقل = "f{field_id}"، والعمود الأول "row_index" يحمل رقم الصف (row_index)
 * ليُستخدم في وضع التحديث؛ الصفوف التي بلا رقم تُضاف كصفوف جديدة.
 */
class FormExcelService
{
    public const MODES = [
        'append' => 'إضافة الصفوف كبيانات جديدة',
        'update' => 'تحديث الصفوف المطابقة برقم الصف وإضافة الجديد',
        'replace' => 'استبدال كل البيانات الحالية بمحتوى الملف',
    ];

    public const ROW_KEY = 'row_index';

    /** الحالات التي يُسمح فيها لمدخل البيانات بالتعديل */
    public const EDITABLE_STATUSES = ['draft', 'pending_assignment', 'assigned', 'data_entry', 'revision_required'];

    private const YES = ['نعم', 'yes', 'true', '1', 'صح', 'y', 'x', '✓'];
    private const NO = ['لا', 'no', 'false', '0', 'خطأ', 'n'];

    public function fields(FormTemplate $template): Collection
    {
        return $template->formFields()
            ->where('is_visible', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /** أعمدة القالب بصيغة ExcelSheet */
    public function columns(FormTemplate $template, ?Collection $fields = null): array
    {
        $fields ??= $this->fields($template);

        $cols = [[
            'key' => self::ROW_KEY,
            'label' => 'رقم الصف',
            'width' => 11,
            'readonly' => true,
            'note' => 'رقم الصف في النظام — لا تعدّله. اتركه فارغاً عند إضافة صف جديد.',
        ]];

        foreach ($fields as $f) {
            $col = [
                'key' => 'f' . $f->id,
                'label' => $f->label ?: $f->name,
                'required' => (bool) $f->is_required,
                'note' => $this->hint($f),
            ];
            $opts = $this->options($f);
            if ($opts && in_array($f->type, ['select', 'radio', 'checkbox', 'boolean'], true)) {
                $col['options'] = $opts;
            }
            if ($f->is_readonly) $col['readonly'] = true;
            $cols[] = $col;
        }

        return $cols;
    }

    public function options(FormField $f): array
    {
        $opts = array_values(array_filter(array_map(
            fn ($o) => is_array($o) ? ($o['label'] ?? $o['value'] ?? '') : (string) $o,
            (array) ($f->options ?? [])
        ), fn ($o) => $o !== ''));

        if (! $opts && in_array($f->type, ['checkbox', 'boolean'], true)) {
            $opts = ['نعم', 'لا'];
        }
        return $opts;
    }

    private function hint(FormField $f): string
    {
        $parts = [];
        $parts[] = match ($f->type) {
            'number' => 'رقم',
            'date' => 'تاريخ بصيغة 2026-10-07',
            'email' => 'بريد إلكتروني',
            'phone' => 'رقم هاتف',
            'national_id' => 'رقم قومي من 14 رقماً',
            'select', 'radio' => 'اختر من القائمة',
            'multiselect' => 'قيم متعددة مفصولة بفاصلة',
            'checkbox', 'boolean' => 'نعم / لا',
            default => 'نص',
        };
        if ($f->is_required) $parts[] = 'إلزامي';
        if ($f->help_text) $parts[] = $f->help_text;
        return implode(' — ', $parts);
    }

    public function blankTemplate(FormTemplate $template): Spreadsheet
    {
        return ExcelSheet::build(
            $template->name,
            $this->columns($template),
            [],
            'قالب إدخال بيانات «' . $template->name . '». املأ البيانات بدءاً من الصف الخامس، صفاً لكل سجل. اترك عمود «رقم الصف» فارغاً. الأعمدة المعلّمة بـ * إلزامية.'
        );
    }

    public function exportSubmission(FormSubmission $submission): Spreadsheet
    {
        $template = $submission->formTemplate;
        $fields = $this->fields($template);

        return ExcelSheet::build(
            $template->name . ' - ' . $submission->reference_number,
            $this->columns($template, $fields),
            $this->rowsFor($submission),
            'بيانات الإرسال ' . $submission->reference_number . '. عدّل القيم مباشرة وأضف صفوفاً جديدة في الأسفل (بدون رقم صف)، ثم ارفع الملف بوضع «تحديث». لا تعدّل عمود «رقم الصف».'
        );
    }

    /** صفوف الإرسال الحالية: [[__row => idx, f12 => value, ...], ...] */
    public function rowsFor(FormSubmission $submission): array
    {
        $rows = [];
        FormSubmissionData::where('form_submission_id', $submission->id)
            ->orderBy('row_index')->orderBy('id')
            ->select(['row_index', 'form_field_id', 'value'])
            ->chunk(5000, function ($chunk) use (&$rows) {
                foreach ($chunk as $d) {
                    $idx = (int) $d->row_index;
                    $rows[$idx][self::ROW_KEY] = $idx;
                    $rows[$idx]['f' . $d->form_field_id] = $d->value;
                }
            });
        ksort($rows);
        return array_values($rows);
    }

    /**
     * يقرأ الملف ويتحقق من كل صف حسب نوع الحقل.
     *
     * @return array{rows: array, errors: array, total: int, matched: array, missing: array}
     */
    public function parse(string $path, FormTemplate $template): array
    {
        $fields = $this->fields($template)->keyBy(fn ($f) => 'f' . $f->id);
        $read = ExcelSheet::read($path, $this->columns($template, $fields->values()));

        $matched = $read['matched'];
        unset($matched[self::ROW_KEY]);
        $missing = $fields->filter(fn ($f, $k) => ! isset($matched[$k]) && $f->is_required)
            ->map(fn ($f) => $f->label)->values()->all();

        $valid = [];
        $errors = [];
        foreach ($read['rows'] as $row) {
            $excelRow = $row['__row'];
            $rowErrors = [];
            $clean = [];

            $rowNo = $row[self::ROW_KEY] ?? null;
            if ($rowNo !== null && $rowNo !== '') {
                $rowNo = $this->toNumber($rowNo);
                if ($rowNo === null || $rowNo < 0 || floor($rowNo) != $rowNo) {
                    $rowErrors[] = ['field' => 'رقم الصف', 'message' => 'رقم الصف غير صالح — اتركه فارغاً للصفوف الجديدة'];
                    $rowNo = null;
                } else {
                    $rowNo = (int) $rowNo;
                }
            } else {
                $rowNo = null;
            }

            foreach ($fields as $key => $f) {
                if (! array_key_exists($key, $row)) {
                    continue; // العمود غير موجود في الملف
                }
                [$value, $err] = $this->normalize($f, $row[$key]);
                if ($err) {
                    $rowErrors[] = ['field' => $f->label, 'message' => $err, 'value' => is_scalar($row[$key]) ? (string) $row[$key] : ''];
                    continue;
                }
                if ($value !== null && $value !== '') {
                    $clean[$f->id] = $value;
                }
            }

            foreach ($fields as $key => $f) {
                if ($f->is_required && isset($matched[$key]) && ! isset($clean[$f->id])
                    && ! collect($rowErrors)->contains('field', $f->label)) {
                    $rowErrors[] = ['field' => $f->label, 'message' => 'حقل إلزامي فارغ'];
                }
            }

            if ($rowErrors) {
                foreach ($rowErrors as $e) {
                    $errors[] = $e + ['row' => $excelRow];
                }
            } else {
                $valid[] = ['row_no' => $rowNo, 'values' => $clean, 'excel_row' => $excelRow];
            }
        }

        if ($missing) {
            array_unshift($errors, [
                'row' => '—', 'field' => implode('، ', $missing),
                'message' => 'أعمدة إلزامية غير موجودة في الملف — استخدم القالب الرسمي المحمّل من النظام',
            ]);
        }

        return [
            'rows' => $valid,
            'errors' => $errors,
            'total' => count($read['rows']),
            'matched' => count($matched),
            'missing' => $missing,
            'invalid_rows' => collect($errors)->pluck('row')->unique()->reject(fn ($r) => $r === '—')->count(),
        ];
    }

    /** @return array{0: mixed, 1: ?string} [القيمة المنسقة، رسالة الخطأ] */
    public function normalize(FormField $f, mixed $raw): array
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return [null, null];
        }
        $v = is_string($raw) ? trim($this->arabicDigits($raw)) : $raw;

        switch ($f->type) {
            case 'number':
                $n = $this->toNumber($v);
                if ($n === null) return [null, 'يجب أن تكون القيمة رقماً'];
                return [floor($n) == $n && abs($n) < 1e15 ? (string) (int) $n : (string) $n, null];

            case 'date':
                $d = $this->toDate($v);
                return $d ? [$d, null] : [null, 'تاريخ غير صالح — استخدم الصيغة 2026-10-07'];

            case 'email':
                return filter_var($v, FILTER_VALIDATE_EMAIL) ? [strtolower($v), null] : [null, 'بريد إلكتروني غير صالح'];

            case 'national_id':
                $digits = preg_replace('/\D/', '', (string) $v);
                return strlen($digits) === 14 ? [$digits, null] : [null, 'الرقم القومي يجب أن يكون 14 رقماً'];

            case 'phone':
                $digits = preg_replace('/[^\d+]/', '', (string) $v);
                return strlen($digits) >= 6 ? [$digits, null] : [null, 'رقم هاتف غير صالح'];

            case 'checkbox':
            case 'boolean':
                $opts = $this->options($f);
                if ($opts !== ['نعم', 'لا']) {
                    return $this->matchOption($opts, (string) $v);
                }
                $s = mb_strtolower((string) $v);
                if (in_array($s, self::YES, true) || $v === true) return ['نعم', null];
                if (in_array($s, self::NO, true) || $v === false) return ['لا', null];
                return [null, 'القيمة يجب أن تكون «نعم» أو «لا»'];

            case 'select':
            case 'radio':
                $opts = $this->options($f);
                return $opts ? $this->matchOption($opts, (string) $v) : [(string) $v, null];

            case 'multiselect':
                $opts = $this->options($f);
                $parts = array_filter(array_map('trim', preg_split('/[,،;]/u', (string) $v)));
                if (! $opts) return [implode('، ', $parts), null];
                $out = [];
                foreach ($parts as $p) {
                    [$m, $err] = $this->matchOption($opts, $p);
                    if ($err) return [null, $err];
                    $out[] = $m;
                }
                return [implode('، ', $out), null];

            default:
                if (is_float($v) && floor($v) == $v && abs($v) < 1e15) $v = (string) (int) $v;
                $s = (string) $v;
                if (mb_strlen($s) > 5000) return [null, 'النص أطول من المسموح (5000 حرف)'];
                return [$s, null];
        }
    }

    private function matchOption(array $opts, string $v): array
    {
        $norm = fn ($s) => preg_replace('/\s+/u', ' ', str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], mb_strtolower(trim($s))));
        foreach ($opts as $o) {
            if ($norm($o) === $norm($v)) return [$o, null];
        }
        return [null, 'قيمة غير موجودة في قائمة الاختيارات (' . implode('، ', array_slice($opts, 0, 6)) . (count($opts) > 6 ? '…' : '') . ')'];
    }

    private function arabicDigits(string $s): string
    {
        return strtr($s, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', '٬' => '']);
    }

    private function toNumber(mixed $v): ?float
    {
        if (is_int($v) || is_float($v)) return (float) $v;
        $s = str_replace([',', ' '], '', $this->arabicDigits((string) $v));
        return is_numeric($s) ? (float) $s : null;
    }

    private function toDate(mixed $v): ?string
    {
        if (is_numeric($v) && $v > 20000 && $v < 80000) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
        }
        $s = str_replace(['\\', '.'], ['/', '/'], (string) $v);
        foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'j/n/Y', 'Y-m-d H:i:s', 'd/m/Y H:i'] as $fmt) {
            $d = \DateTime::createFromFormat('!' . $fmt, $s);
            if ($d && $d->format($fmt) === $s) return $d->format('Y-m-d');
        }
        $ts = strtotime($s);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * يطبّق الصفوف الصحيحة على الإرسال داخل معاملة واحدة.
     *
     * @return array{added:int, updated:int, removed:int}
     */
    public function apply(FormSubmission $submission, array $rows, string $mode, int $userId): array
    {
        return DB::transaction(function () use ($submission, $rows, $mode, $userId) {
            $stats = ['added' => 0, 'updated' => 0, 'removed' => 0];
            $base = FormSubmissionData::where('form_submission_id', $submission->id);

            if ($mode === 'replace') {
                $stats['removed'] = (clone $base)->distinct()->count('row_index');
                (clone $base)->delete();
                $next = 0;
            } else {
                $next = ((int) (clone $base)->max('row_index')) + 1;
                if (! (clone $base)->exists()) $next = 0;
            }

            $existing = $mode === 'update'
                ? (clone $base)->distinct()->pluck('row_index')->map(fn ($r) => (int) $r)->flip()
                : collect();

            $insert = [];
            $now = now();
            foreach ($rows as $r) {
                $idx = null;
                if ($mode === 'update' && $r['row_no'] !== null && $existing->has($r['row_no'])) {
                    $idx = $r['row_no'];
                    FormSubmissionData::where('form_submission_id', $submission->id)->where('row_index', $idx)->delete();
                    $stats['updated']++;
                } else {
                    $idx = $next++;
                    $stats['added']++;
                }

                foreach ($r['values'] as $fieldId => $value) {
                    $insert[] = [
                        'form_submission_id' => $submission->id,
                        'form_field_id' => $fieldId,
                        'row_index' => $idx,
                        'value' => $value,
                        'raw_value' => json_encode(['original' => $value, 'row' => $idx, 'source' => 'excel'], JSON_UNESCAPED_UNICODE),
                        'entered_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($insert) >= 1000) {
                        FormSubmissionData::insert($insert);
                        $insert = [];
                    }
                }
            }
            if ($insert) FormSubmissionData::insert($insert);

            return $stats;
        });
    }
}
