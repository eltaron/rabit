<?php

namespace App\Services;

use App\Models\Center;
use App\Models\SectorData;
use Illuminate\Support\Collection;

class SectorDataImportService
{
    public const SECTORS = ['investment', 'population', 'education', 'supply', 'health'];

    public const SECTOR_LABELS = [
        'investment' => 'الاستثمار',
        'population' => 'السكان',
        'education'  => 'التعليم',
        'supply'     => 'التموين',
        'health'     => 'الصحة',
    ];

    /** Aliases for centre name matching (before letter normalisation). */
    private const CENTER_ALIASES = [
        'كفر الشيخ' => [
            'كفر الشيخ', 'كفرالشيخ', 'بندر كفر الشيخ', 'بندر كفرالشيخ',
            'مركز كفر الشيخ', 'مركز كفرالشيخ', 'مدينة كفر الشيخ', 'كفرالشيخ',
            'قسم اول كفر الشيخ', 'قسم اول كفرالشيخ', 'قسم ثان كفر الشيخ', 'قسم اول', 'قسم ثان',
            'ادارة تموين بندر كفر الشيخ', 'ادارة تموين مركز كفر الشيخ',
            'الادارة التعليمية كفر الشيخ', 'كفر الشيخ الاولى',
        ],
        'دسوق' => [
            'دسوق', 'بندر دسوق', 'مركز دسوق', 'مدينة دسوق',
            'ادارة تموين بندر دسوق', 'ادارة تموين مركز دسوق',
            'الادارة التعليمية دسوق', 'دسوق التعليمية',
        ],
        'سيدي سالم' => [
            'سيدي سالم', 'سيدى سالم', 'مركز سيدي سالم', 'مركز سيدى سالم', 'مدينة سيدي سالم',
            'ادارة تموين سيدي سالم', 'الادارة التعليمية سيدي سالم',
        ],
        'بيلا' => [
            'بيلا', 'مركز بيلا', 'مدينة بيلا', 'ادارة تموين بيلا',
        ],
        'قلين' => [
            'قلين', 'مركز قلين', 'مدينة قلين', 'ادارة تموين مركز قلين', 'ادارة تموين قلين',
        ],
        'الحامول' => [
            'الحامول', 'مركز الحامول', 'ادارة تموين مركز الحامول',
        ],
        'بلطيم' => [
            'بلطيم', 'مركز بلطيم', 'مدينة بلطيم', 'مصيف بلطيم', 'برج البرلس', 'ادارة تموين بلطيم',
        ],
        'مطوبس' => [
            'مطوبس', 'مركز مطوبس', 'مدينة مطوبس', 'المنطقة الصناعية مطوبس',
        ],
        'الرياض' => [
            'الرياض', 'مركز الرياض', 'ادارة تموين الرياض',
        ],
        'فوه' => [
            'فوه', 'فوة', 'مركز فوه', 'مركز فوة', 'مدينة فوه', 'ادارة تموين فوه',
        ],
        'سيدي غازي' => [
            'سيدي غازي', 'سيدى غازى', 'مركز سيدي غازي', 'مركز سيدى غازى', 'ادارة تموين سيدى غازى',
        ],
    ];

    /** Known header keywords used to locate the header row of a sheet. */
    private const HEADER_KEYWORDS = [
        'م', 'م ', 'اسم', 'الاسم', 'الأسم', 'الاسم التجارى', 'الاسم التجاري', 'اســم', 'أسم',
        'مركز', 'المركز', 'المدينة', 'المركز / المدينة', 'المركز/المدينة', 'المركز او العنوان',
        'قسم/مركز', 'قسم / مركز', 'الادارة', 'الإدارة', 'ادارة', 'إدارة', 'المدرسة', 'المدارس',
        'المستشفى', 'المستشفيات', 'الوحدات الصحية', 'المخبز', 'المخابز', 'مخبز',
        'القطعة', 'رقم القطعة', 'المساحة', 'النشاط', 'نشاط', 'المشروع', 'المصنع', 'المصانع',
        'المنطقة', 'المناطق', 'المستودع', 'المستودعات', 'الصومعة', 'الشونة', 'المطحن', 'المطاحن',
        'المحطة', 'محطات', 'المنفذ', 'منفذ', 'الجمعية', 'الجمعيات', 'المجمع', 'المجمعات',
        'السكان', 'الشياخة', 'القرية', 'التقسيم', 'الهرم', 'المواليد', 'الوفيات', 'المتزوجات',
        'البطالة', 'الامية', 'الأمية', 'المؤشر', 'المؤشرات', 'البيان', 'المراحل', 'المرحلة',
        'التعليم', 'التعليمية', 'تلاميذ', 'الطلبة', 'الطلاب', 'الطالبات', 'التلاميذ',
        'مدارس', 'فصول', 'مدرسين', 'مدرسين اصلى', 'المدرسين', 'الموظفين', 'الاخصائيين',
        'العنوان', 'الموقع', 'المكان', 'التبعية', 'التشغيل', 'الترخيص', 'الاستخدام',
        'عدد', 'العدد', 'النوع', 'نوع', 'التليفون', 'الهاتف', 'تليفون', 'موبايل', 'الاتصال',
        'الطاقة', 'الانتاجية', 'الإنتاجية', 'السعة', 'التخزينية', 'الكمية', 'الحصة', 'الدقيق',
        'البطاقات', 'الافراد', 'التجار', 'السنة', 'السنوات', 'الشهر', 'التاريخ', 'تاريخ',
        'ذكور', 'اناث', 'إجمالي', 'الإجمالي', 'اجمالى', 'الإجمالى', 'جملة', 'حضر', 'ريف',
        'فئات', 'الجدول', 'البيئة', 'الحي', 'الاحياء', 'شهرة', 'الكود', 'الرمز',
        'المديرية', 'القرية', 'قرية', 'الشياخة', 'شياخة', 'المفردات', 'المنتجات', 'الخدمات',
    ];

    private ?Collection $centers = null;
    private ?Collection $centersByName = null;

    /**
     * Detect the sector (investment/population/education/supply/health) from file name or content.
     */
    public function detectSector(?string $fileName, array $sheetsData): ?string
    {
        $fileNameNorm = $this->normalizeText((string) $fileName);
        $allText = '';
        foreach ($sheetsData as $sheetName => $rows) {
            $allText .= ' ' . $sheetName . ' ';
            foreach ($rows as $row) {
                $allText .= implode(' ', array_map(fn($c) => (string) ($c ?? ''), $row)) . ' ';
            }
        }
        $text = $this->normalizeText($allText);

        $nameChecks = [
            'supply'     => ['التموين', 'تموين'],
            'health'     => ['المستشفيات', 'صحه', 'صحة'],
            'education'  => ['الابنية التعليمية', 'التعليم رقميا', 'تعليم', 'ابنية', 'مدارس'],
            'investment' => ['الاستثمار', 'استثمار', 'مناطق صناعية', 'مصانع'],
            'population' => ['السكان', 'سكان'],
        ];

        foreach ($nameChecks as $sector => $needles) {
            foreach ($needles as $needle) {
                if ($this->normContains($fileNameNorm, $needle)) {
                    return $sector;
                }
            }
        }

        $contentChecks = [
            'investment' => ['منطقة صناعية', 'مناطق صناعية', 'الاستثمار', 'مشروعات جارى', 'رقم القطعة', 'مساحة عسكرية'],
            'population' => ['الهرم السكاني', 'التقسيم الاداري', 'المتزوجات', 'المواليد والوفيات', 'معدل البطالة', 'الشياخة'],
            'education'  => ['المدرسة', 'المدارس', 'المديرية', 'التعليمية', 'الابنية التعليمية', 'طلبة', 'تلاميذ', 'فصول'],
            'supply'     => ['المخابز', 'مستودعات البوتجاز', 'السلع التموينية', 'البوتاجاز', 'الجمعيات', 'منافذ بيع', 'محطات الوقود'],
            'health'     => ['الوحدات الصحية', 'الشئون الصحية', 'المستشفيات', 'الاطباء', 'المراكز الصحية'],
        ];

        foreach ($contentChecks as $sector => $needles) {
            foreach ($needles as $needle) {
                if ($this->normContains($text, $needle)) {
                    return $sector;
                }
            }
        }

        return null;
    }

    private function normContains(string $haystack, string $needle): bool
    {
        if ($needle === '') return false;
        return str_contains($haystack, $this->normalizeText($needle));
    }

    /**
     * Import all sheets of one file into sector_data.
     * Re-importing the same file replaces its previous rows (update semantics).
     */
    public function import(array $sheetsData, string $fileName, string $sector): array
    {
        if (!in_array($sector, self::SECTORS, true)) {
            throw new \InvalidArgumentException("قطاع غير معروف: {$sector}");
        }

        $this->deleteForFile($fileName, $sector);

        $imported = 0;
        $skipped = 0;
        $details = [];

        foreach ($sheetsData as $sheetName => $rows) {
            $result = $this->importSheet($rows, $sheetName, $fileName, $sector);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
            $details[$sheetName] = ['imported' => $result['imported'], 'skipped' => $result['skipped'], 'header_row' => $result['header_row']];
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'sheets' => $details];
    }

    /**
     * Analyse a single sheet without writing to the DB (for verification).
     */
    public function analyseSheet(array $rows, string $sheetName, string $sector): array
    {
        $headerInfo = $this->findHeader($rows);
        $centerColumn = $headerInfo['center_column'] ?? null;
        $centerFromName = $this->centerFromText($sheetName);

        $importable = 0;
        foreach ($this->dataRows($rows, $headerInfo['header_row'] ?? null, $headerInfo['data_start'] ?? 0) as $row) {
            $importable++;
        }

        return [
            'rows' => count($rows),
            'header_row' => $headerInfo['header_row'] ?? null,
            'data_start' => $headerInfo['data_start'] ?? null,
            'center_column' => $centerColumn,
            'center_from_sheet' => $centerFromName?->name,
            'importable_rows' => $importable,
            'sector' => $sector,
        ];
    }

    private function importSheet(array $rows, string $sheetName, string $fileName, string $sector): array
    {
        $headerInfo = $this->findHeader($rows);
        $imported = 0;
        $skipped = 0;

        $centerColumn = $headerInfo['center_column'] ?? null;
        $sheetCenter = $this->centerFromText($sheetName);
        $lastCenter = $sheetCenter;

        foreach ($this->dataRows($rows, $headerInfo['header_row'] ?? null, $headerInfo['data_start'] ?? 0) as $row) {
            $data = $this->mapRow($row, $headerInfo, $sheetName, $fileName, $sector, $centerColumn, $lastCenter, $sheetCenter);

            if (count($data['extra_data']) === 0 && empty($data['name']) && empty($data['center_name'])) {
                $skipped++;
                continue;
            }

            SectorData::create($data);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'header_row' => $headerInfo['header_row'] ?? null];
    }

    /**
     * Locate the header row(s) and data start for a sheet.
     */
    private function findHeader(array $rows): array
    {
        $limit = min(count($rows), 12);
        $bestRow = null;
        $bestScore = 0;

        for ($i = 0; $i < $limit; $i++) {
            $score = $this->headerScore($rows[$i]);
            // Bonus for rows that are immediately followed by a data row (numeric values).
            if ($this->hasDataNearby($rows, $i + 1, 4)) {
                $score += 2;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $i;
            }
        }

        if ($bestRow === null || $bestScore < 2) {
            return ['header_row' => null, 'data_start' => 0, 'headers' => [], 'col_map' => []];
        }

        $headerRow = $bestRow;
        $dataStart = $this->findDataStart($rows, $headerRow + 1);

        // Extend the header block upward through contiguous header-like rows
        // (multi-row headers: the scored subheader may sit below the real header).
        while ($headerRow > 0 && $this->isHeaderLikeRow($rows[$headerRow - 1])) {
            $headerRow--;
        }

        $headerRows = [];
        for ($i = $headerRow; $i < $dataStart; $i++) {
            $headerRows[] = $rows[$i];
        }
        $headers = $this->buildHeaders($headerRows);
        $colMap = $this->buildColMap($headers);

        return [
            'header_row' => $headerRow,
            'data_start' => $dataStart,
            'headers' => $headers,
            'col_map' => $colMap,
            'center_column' => $colMap['center'] ?? null,
        ];
    }

    private function headerScore(array $row): float
    {
        $score = 0;
        $numericCells = 0;
        $nonEmpty = 0;
        foreach ($row as $cell) {
            if ($cell === null || $cell === '') continue;
            $nonEmpty++;
            $value = trim((string) $cell);
            if ($this->isNumericLike($value)) {
                $numericCells++;
                continue;
            }
            $norm = $this->normalizeText($value);
            $match = false;
            foreach (self::HEADER_KEYWORDS as $kw) {
                $kwNorm = $this->normalizeText($kw);
                if (mb_strlen($kw) <= 2) {
                    if ($norm === $kwNorm) {
                        $score += 0.5;
                        $match = true;
                        break;
                    }
                } else {
                    if (str_contains($norm, $kwNorm)) {
                        $score += 1.5;
                        $match = true;
                        break;
                    }
                }
            }
            if (!$match && mb_strlen($value) >= 3 && preg_match('/\p{Arabic}/u', $value)) {
                $score += 0.3;
            }
        }
        if ($nonEmpty < 2) {
            return -999;
        }
        if ($numericCells > 0) {
            $score -= $numericCells * 0.3;
        }
        return $score;
    }

    private function hasDataNearby(array $rows, int $from, int $range): bool
    {
        $n = count($rows);
        for ($i = $from; $i < min($from + $range, $n); $i++) {
            $numeric = 0;
            $nonEmpty = 0;
            foreach ($rows[$i] as $cell) {
                if ($cell === null || $cell === '') continue;
                $nonEmpty++;
                if ($this->isNumericLike(trim((string) $cell))) {
                    $numeric++;
                }
            }
            if ($nonEmpty >= 2 && $numeric > 0) {
                return true;
            }
        }
        return false;
    }

    private function isHeaderLikeRow(array $row): bool
    {
        $nonEmpty = 0;
        $numeric = 0;
        foreach ($row as $cell) {
            if ($cell === null || $cell === '') continue;
            $nonEmpty++;
            if ($this->isNumericLike(trim((string) $cell))) {
                $numeric++;
            }
        }
        return $nonEmpty >= 2 && $numeric === 0;
    }

    private function findDataStart(array $rows, int $from): int
    {
        $n = count($rows);
        for ($i = $from; $i < $n; $i++) {
            $nonEmpty = 0;
            $numeric = 0;
            foreach ($rows[$i] as $cell) {
                if ($cell === null || $cell === '') continue;
                $nonEmpty++;
                $value = trim((string) $cell);
                if ($this->isNumericLike($value)) {
                    $numeric++;
                }
            }
            if ($nonEmpty > 0 && $numeric > 0) {
                return $i;
            }
        }
        return $n;
    }

    private function isNumericLike(string $value): bool
    {
        $v = trim($value);
        if ($v === '') return false;
        if (str_starts_with($v, '=')) return true;
        if (is_numeric($v)) return true;
        return (bool) preg_match('/^[-+]?[0-9][0-9.,]*$/', $v);
    }

    private function buildHeaders(array $headerRows): array
    {
        $width = 0;
        foreach ($headerRows as $row) {
            $width = max($width, count($row));
        }
        $headers = [];
        for ($c = 0; $c < $width; $c++) {
            $parts = [];
            foreach ($headerRows as $row) {
                $cell = $row[$c] ?? null;
                if ($cell !== null && trim((string) $cell) !== '') {
                    $parts[] = trim((string) $cell);
                }
            }
            $headers[$c] = implode(' / ', $parts);
        }
        return $headers;
    }

    private function buildColMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $colIdx => $header) {
            if ($header === '') continue;
            $norm = $this->normalizeText($header);
            $key = null;

            if ($this->containsAny($norm, ['المركز/المدينة', 'المركز / المدينة', 'المركزاوالعنوان', 'المراكز'])) {
                $key = 'center';
            } elseif ($this->containsAny($norm, ['مركز', 'المدينة', 'الادارة', 'ادارة', 'قسم/مركز', 'قسم/مدينة'])) {
                $key = 'center';
            } elseif ($this->containsAny($norm, ['المنطقة']) && !$this->containsAny($norm, ['عدد', 'صناعية', 'المناطق'])) {
                $key = 'center';
            } elseif ($this->containsAny($norm, ['المدرسة', 'اسم المستشفى', 'اسم المخبز', 'اسم المستودع', 'اسم المنفذ', 'اسم الجمعية', 'اسم الصومعة', 'اسم المطحن', 'اسم المجمع', 'الاسم التجارى', 'الاسم التجاري', 'اسم الملف', 'اسم المشروع', 'اسم صاحب', 'اسم الحضانه', 'المدرسه'])) {
                $key = 'name';
            } elseif (preg_match('/^(م|الأسم|الاسم|اسم|اســم)\b/', $header) && !$this->containsAny($norm, ['رقم', 'كود', 'التليفون', 'تليفون'])) {
                $key = 'name';
            } elseif ($this->containsAny($norm, ['العنوان', 'الموقع', 'المكان', 'بالتفصيل'])) {
                $key = 'address';
            } elseif ($this->containsAny($norm, ['التليفون', 'الهاتف', 'تليفون', 'موبايل', 'الاتصال'])) {
                $key = 'phone';
            } elseif ($this->containsAny($norm, ['نوع', 'التبعية', 'التشغيل', 'الترخيص', 'المرحلة', 'الاستخدام', 'الدرجة', 'الوضع'])) {
                $key = 'type';
            } elseif ($this->containsAny($norm, ['السنة', 'العام الدراسي', 'الفترة'])) {
                $key = 'year';
            } elseif (preg_match('/^[xy]$/i', $header) || preg_match('/^(خط|احداثيات|التوقيع|توقيع)/', $header) && preg_match('/\b[xXyY]\b/', $header)) {
                // coordinates handled below per exact cell
            } elseif (preg_match('/^[xX]$/', $header)) {
                $key = 'x';
            } elseif (preg_match('/^[yY]$/', $header)) {
                $key = 'y';
            }

            if ($key === 'center') {
                if (!isset($map['center'])) {
                    $map['center'] = $colIdx;
                }
            } elseif ($key) {
                $map[$key] = $colIdx;
            }
        }

        // Coordinate columns: headers containing X/Y tokens
        foreach ($headers as $colIdx => $header) {
            if ($header === '') continue;
            if (preg_match('/\b[Xx]\b/', $header) && !isset($map['x'])) {
                $map['x'] = $colIdx;
            }
            if (preg_match('/\b[Yy]\b/', $header) && !isset($map['y'])) {
                $map['y'] = $colIdx;
            }
        }

        // 'م' serial column (first occurrence, skip if followed by serial indicators)
        if (!isset($map['serial'])) {
            foreach ($headers as $colIdx => $header) {
                $norm = $this->normalizeText($header);
                if (preg_match('/^م([^a-zء-ي]|$)/i', $norm) && !$this->containsAny($norm, ['مركز', 'عدد', 'رقم'])) {
                    $map['serial'] = $colIdx;
                    break;
                }
            }
        }

        return $map;
    }

    private function containsAny(string $norm, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($norm, $this->normalizeText($needle))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build the list of data rows (filtering empty/total/source rows).
     */
    private function dataRows(array $rows, ?int $headerRow, int $dataStart): \Generator
    {
        $n = count($rows);
        for ($i = $dataStart; $i < $n; $i++) {
            $row = $rows[$i];
            if ($this->isEmptyRow($row)) continue;
            if ($this->isSkipRow($row)) continue;
            yield $row;
        }
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function isSkipRow(array $row): bool
    {
        $joined = trim(implode(' ', array_map(fn($c) => (string) ($c ?? ''), $row)));
        if ($joined === '') return true;
        $norm = $this->normalizeText($joined);
        $skipWords = ['الاجمالى', 'الاجمالي', 'الاجملى', 'اجمالى', 'الاجمالي الكلي', 'مصدر البيان', 'المصدر', 'صادر من', 'مديرية', 'جهاز', 'الادارة العامة', 'الادارة العامه', 'محافظة', 'المحافظة'];
        foreach ($skipWords as $word) {
            if (str_contains($norm, $this->normalizeText($word))) {
                return true;
            }
        }
        return false;
    }

    private function mapRow(array $row, array $headerInfo, string $sheetName, string $fileName, string $sector, ?int $centerColumn, ?Center &$lastCenter, ?Center $sheetCenter): array
    {
        $colMap = $headerInfo['col_map'] ?? [];
        $headers = $headerInfo['headers'] ?? [];

        $extraData = [];
        foreach ($row as $colIdx => $cell) {
            if ($cell === null || trim((string) $cell) === '') continue;
            $header = $headers[$colIdx] ?? '';
            $value = trim((string) $cell);
            $extraData[$header !== '' ? $header : "col_" . ($colIdx + 1)] = $value;
        }

        $name = $this->getByMap($row, $colMap, 'name') ?? $this->getByMap($row, $colMap, 'name2') ?? $this->firstTextCell($row);
        $type = $this->getByMap($row, $colMap, 'type');
        $address = $this->getByMap($row, $colMap, 'address');

        $centerRaw = $centerColumn !== null ? $this->cell($row, $centerColumn) : null;
        $center = $centerRaw ? $this->centerFromText($centerRaw) : null;
        if (!$center && $lastCenter) {
            $center = $lastCenter;
        } elseif ($center) {
            $lastCenter = $center;
        }
        if (!$center) {
            $center = $this->centerFromText($address) ?? $this->centerFromText($name);
        }

        $x = $this->parseCoord($this->getByMap($row, $colMap, 'x'));
        $y = $this->parseCoord($this->getByMap($row, $colMap, 'y'));

        // For population/education summary sheets where name column doesn't exist, use center as name context
        if (empty($name) && $center) {
            $name = trim($center->name . ' — ' . $sheetName);
        }

        return [
            'sector' => $sector,
            'file_name' => $fileName,
            'sheet_name' => $sheetName,
            'center_id' => $center?->id,
            'center_name' => $centerRaw ?: $center?->name,
            'name' => $name ?: null,
            'subcategory' => $sheetName,
            'address' => $address ?: null,
            'x_coord' => $x,
            'y_coord' => $y,
            'year_period' => $this->getByMap($row, $colMap, 'year'),
            'extra_data' => !empty($extraData) ? $extraData : null,
        ];
    }

    private function getByMap(array $row, array $colMap, string $key): ?string
    {
        $colIdx = $colMap[$key] ?? null;
        if ($colIdx === null) return null;
        return $this->cell($row, $colIdx);
    }

    private function cell(array $row, int $index): ?string
    {
        $val = $row[$index] ?? null;
        if ($val === null || trim((string) $val) === '') return null;
        return trim((string) $val);
    }

    private function firstTextCell(array $row): ?string
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                $value = trim((string) $cell);
                if (preg_match('/\p{Arabic}/u', $value) && !$this->isNumericLike($value)) {
                    return mb_substr($value, 0, 255);
                }
            }
        }
        return null;
    }

    private function parseCoord(?string $value): ?float
    {
        if ($value === null) return null;
        $v = str_replace(',', '.', trim($value));
        if (preg_match('/^[-+]?\d+(\.\d+)?$/', $v)) {
            $f = (float) $v;
            if ($f >= -180 && $f <= 180) {
                return $f;
            }
        }
        return null;
    }

    public function centerFromText(?string $text): ?Center
    {
        if ($text === null || trim($text) === '') return null;
        $raw = trim($text);
        $norm = $this->normalizeText($raw);

        $this->loadCenters();
        $byName = $this->centers->get('LGOV');

        // 1) exact alias
        foreach (self::CENTER_ALIASES as $centerName => $aliases) {
            foreach ($aliases as $alias) {
                if ($norm === $this->normalizeText($alias)) {
                    return $this->centersByName[$this->normalizeText($this->stripCenterPrefixes($centerName))] ?? null;
                }
            }
        }

        // 2) exact centre name
        foreach ($byName as $center) {
            if ($this->normalizeText($center->name) === $norm) {
                return $center;
            }
        }

        // 3) strip common prefixes then compare (compact, no spaces)
        $compact = $this->normalizeText($this->stripCenterPrefixes($raw));
        foreach ($byName as $center) {
            $centerCompact = $this->normalizeText($this->stripCenterPrefixes($center->name));
            if ($compact === $centerCompact) {
                return $center;
            }
        }

        // 4) contains-match (longest alias wins) — used for sheet names / zone names
        $best = null;
        $bestLen = 0;
        foreach (self::CENTER_ALIASES as $centerName => $aliases) {
            foreach ($aliases as $alias) {
                $aliasNorm = $this->normalizeText($alias);
                if (mb_strlen($aliasNorm) >= 3 && str_contains($norm, $aliasNorm)) {
                    $len = mb_strlen($aliasNorm);
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $best = $this->centersByName[$this->normalizeText($this->stripCenterPrefixes($centerName))] ?? null;
                    }
                }
            }
        }
        if ($best) {
            return $best;
        }

        // 5) contains-match against centre names (longest)
        $best = null;
        $bestLen = 0;
        foreach ($byName as $center) {
            $centerNorm = $this->normalizeText($this->stripCenterPrefixes($center->name));
            if (mb_strlen($centerNorm) >= 3 && str_contains($norm, $centerNorm)) {
                $len = mb_strlen($centerNorm);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $best = $center;
                }
            }
        }
        return $best;
    }

    private function stripCenterPrefixes(string $raw): string
    {
        $value = trim($raw);
        $prefixes = ['مركز', 'بندر', 'مدينة', 'مدينه', 'ادارة تموين', 'إدارة تموين', 'ادارة التموين', 'قسم اول', 'قسم ثان', 'قسم أول', 'قسم ثان', 'قسم'];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($prefixes as $prefix) {
                if (mb_strpos($value, $prefix) === 0) {
                    $value = trim(mb_substr($value, mb_strlen($prefix)));
                    $changed = true;
                }
            }
        }
        return $value;
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) return '';
        $value = preg_replace('/[\x{0640}\x{064B}-\x{0652}]/u', '', $value);
        $value = str_replace(
            ['أ', 'إ', 'آ', 'ى', 'ة', 'ئ'],
            ['ا', 'ا', 'ا', 'ي', 'ه', 'ي'],
            $value
        );
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }

    private function loadCenters(): void
    {
        if ($this->centers !== null) return;
        $lgov = Center::where('code', 'LIKE', 'LGOV%')->get();
        $this->centers = $lgov->groupBy(fn($c) => 'LGOV');
        $this->centersByName = collect();
        foreach ($lgov as $center) {
            $this->centersByName[$this->normalizeText($center->name)] = $center;
            $this->centersByName[$this->normalizeText($this->stripCenterPrefixes($center->name))] = $center;
        }
    }

    public function deleteForFile(string $fileName, string $sector): int
    {
        return SectorData::where('file_name', $fileName)->where('sector', $sector)->delete();
    }
}
