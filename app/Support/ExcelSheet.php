<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * مساعد موحد لملفات Excel الرسمية (قوالب التحميل وإعادة الرفع) بهوية KFS Royal Egyptian.
 *
 * التنسيق الثابت للقوالب:
 *   الصف 1: عنوان رسمي (محافظة كفر الشيخ — اسم النموذج)
 *   الصف 2: تعليمات مختصرة
 *   الصف 3: رؤوس الأعمدة (labels)            ← HEADER_ROW
 *   الصف 4: مفاتيح الأعمدة التقنية (مخفي)     ← KEY_ROW
 *   من الصف 5: البيانات                        ← DATA_START
 * عند القراءة تُطابق الأعمدة بمفاتيح الصف 4 أولاً، ثم بنص الرأس في الصف 3 (لدعم الملفات المعدّلة يدوياً).
 */
class ExcelSheet
{
    public const HEADER_ROW = 3;
    public const KEY_ROW = 4;
    public const DATA_START = 5;

    private const GREEN = '123F35';
    private const GREEN_DEEP = '0A2924';
    private const GOLD = 'B8954F';
    private const PAPYRUS = 'F4F0E6';
    private const IVORY = 'FAF8F2';

    /**
     * @param array<int, array{key:string,label:string,required?:bool,options?:array,width?:int,note?:string,readonly?:bool}> $columns
     * @param array<int, array<string, mixed>> $rows  بيانات مسبقة (للتصدير/التعديل) مفاتيحها = key
     */
    public static function build(string $title, array $columns, array $rows = [], string $instructions = '', array $lists = []): Spreadsheet
    {
        $ss = new Spreadsheet();
        $ss->getProperties()->setCreator('محافظة كفر الشيخ — منصة رابط')->setTitle($title);
        $ws = $ss->getActiveSheet();
        $ws->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $title), 0, 31) ?: 'البيانات');
        $ws->setRightToLeft(true);

        $n = max(1, count($columns));
        $last = Coordinate::stringFromColumnIndex($n);

        // العنوان
        $ws->setCellValue('A1', 'محافظة كفر الشيخ — ' . $title);
        $ws->mergeCells("A1:{$last}1");
        $ws->getStyle("A1:{$last}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FEFDFC'], 'name' => 'Tajawal'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_DEEP]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::GOLD]]],
        ]);
        $ws->getRowDimension(1)->setRowHeight(30);

        // التعليمات
        $ws->setCellValue('A2', $instructions ?: 'أدخل البيانات بدءاً من الصف الخامس. لا تعدّل صفوف العناوين. الأعمدة المعلّمة بـ * إلزامية.');
        $ws->mergeCells("A2:{$last}2");
        $ws->getStyle("A2:{$last}2")->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '626963']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PAPYRUS]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(2)->setRowHeight(34);

        // قوائم الاختيار في شيت مخفي
        $listSheet = null;
        $listCol = 0;

        foreach (array_values($columns) as $i => $col) {
            $c = Coordinate::stringFromColumnIndex($i + 1);
            $label = $col['label'] . (! empty($col['required']) ? ' *' : '');
            $ws->setCellValue("{$c}" . self::HEADER_ROW, $label);
            $ws->setCellValue("{$c}" . self::KEY_ROW, $col['key']);
            $ws->getColumnDimension($c)->setWidth($col['width'] ?? max(14, min(40, mb_strlen($col['label']) + 6)));

            if (! empty($col['note'])) {
                $ws->getComment("{$c}" . self::HEADER_ROW)->getText()->createTextRun($col['note']);
            }

            $options = $col['options'] ?? ($lists[$col['key']] ?? null);
            if ($options) {
                $listSheet ??= tap($ss->createSheet(), fn ($s) => $s->setTitle('_lists'));
                $listCol++;
                $lc = Coordinate::stringFromColumnIndex($listCol);
                foreach (array_values($options) as $k => $opt) {
                    $listSheet->setCellValueExplicit("{$lc}" . ($k + 1), (string) $opt, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $range = "'_lists'!\${$lc}\$1:\${$lc}\$" . max(1, count($options));
                $dv = new DataValidation();
                $dv->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_WARNING)
                    ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
                    ->setErrorTitle('قيمة غير موجودة')->setError('اختر قيمة من القائمة')->setFormula1($range);
                $ws->setDataValidation("{$c}" . self::DATA_START . ":{$c}5000", $dv);
            }

            if (! empty($col['readonly'])) {
                $ws->getStyle("{$c}" . self::DATA_START . ":{$c}5000")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9E2D2');
            }
        }

        $ws->getStyle("A" . self::HEADER_ROW . ":{$last}" . self::HEADER_ROW)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FEFDFC'], 'name' => 'Tajawal'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::GOLD]]],
        ]);
        $ws->getRowDimension(self::HEADER_ROW)->setRowHeight(32);
        $ws->getRowDimension(self::KEY_ROW)->setVisible(false);
        $ws->getStyle("A" . self::KEY_ROW . ":{$last}" . self::KEY_ROW)->getFont()->setSize(8)->getColor()->setRGB('858A84');

        // البيانات
        $r = self::DATA_START;
        foreach ($rows as $row) {
            foreach (array_values($columns) as $i => $col) {
                $v = $row[$col['key']] ?? null;
                if ($v === null || $v === '') continue;
                $cell = Coordinate::stringFromColumnIndex($i + 1) . $r;
                if (is_numeric($v) && strlen((string) $v) < 15 && ! str_starts_with((string) $v, '0')) {
                    $ws->setCellValue($cell, $v);
                } else {
                    $ws->setCellValueExplicit($cell, (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            if (($r - self::DATA_START) % 2 === 1) {
                $ws->getStyle("A{$r}:{$last}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::IVORY);
            }
            $r++;
        }
        $end = max($r - 1, self::DATA_START + 50);
        $ws->getStyle("A" . self::DATA_START . ":{$last}{$end}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DCD2BD');

        $ws->freezePane('A' . self::DATA_START);
        if ($listSheet) $listSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $ss->setActiveSheetIndex(0);

        return $ss;
    }

    public static function download(Spreadsheet $ss, string $filename): StreamedResponse
    {
        $filename = preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $filename);
        if (! str_ends_with($filename, '.xlsx')) $filename .= '.xlsx';

        return response()->streamDownload(function () use ($ss) {
            (new Xlsx($ss))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * يقرأ ملفاً مرفوعاً ويعيد الصفوف كمصفوفات key => value.
     *
     * @param array<int, array{key:string,label:string}> $columns
     * @return array{rows: array<int, array{__row:int} & array<string, mixed>>, matched: array<string,string>, unknown: array<int,string>}
     */
    public static function read(string $path, array $columns): array
    {
        $ss = IOFactory::load($path);
        $ws = $ss->getSheet(0);
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        $highestRow = $ws->getHighestDataRow();

        $byKey = collect($columns)->keyBy('key');
        $norm = fn ($s) => preg_replace('/\s+|\*/u', '', str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], (string) $s));
        $byLabel = collect($columns)->mapWithKeys(fn ($c) => [$norm($c['label']) => $c['key']]);

        // كشف صف الرؤوس: القالب الرسمي (مفاتيح في الصف 4) أو ملف عادي (رؤوس في الصف الأول المطابق)
        $map = [];
        $dataStart = self::DATA_START;
        for ($ci = 1; $ci <= $highestCol; $ci++) {
            $k = trim((string) $ws->getCell(Coordinate::stringFromColumnIndex($ci) . self::KEY_ROW)->getValue());
            if ($k !== '' && $byKey->has($k)) $map[$ci] = $k;
        }
        if (! $map) {
            for ($hr = 1; $hr <= min(10, $highestRow); $hr++) {
                $try = [];
                for ($ci = 1; $ci <= $highestCol; $ci++) {
                    $label = $norm($ws->getCell(Coordinate::stringFromColumnIndex($ci) . $hr)->getCalculatedValue());
                    if ($label !== '' && $byLabel->has($label)) $try[$ci] = $byLabel[$label];
                }
                if (count($try) > count($map)) { $map = $try; $dataStart = $hr + 1; }
            }
        }

        $unknown = [];
        $rows = [];
        for ($r = $dataStart; $r <= $highestRow; $r++) {
            $row = ['__row' => $r];
            $hasValue = false;
            foreach ($map as $ci => $key) {
                $cell = $ws->getCell(Coordinate::stringFromColumnIndex($ci) . $r);
                $v = $cell->getCalculatedValue();
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell) && is_numeric($v)) {
                    $v = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
                }
                $v = is_string($v) ? trim($v) : $v;
                if ($v !== null && $v !== '') $hasValue = true;
                $row[$key] = $v;
            }
            if ($hasValue) $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'matched' => collect($map)->mapWithKeys(fn ($k) => [$k => $byKey[$k]['label'] ?? $k])->all(),
            'unknown' => $unknown,
        ];
    }
}
