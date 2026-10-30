<?php

namespace App\Services;

use App\Models\Center;
use App\Models\FacilityData;

class FacilityDataImportService
{
    public const MODULES = [
        'ARCHAEOLOGICAL_ATTRACTIONS',
        'RECREATIONAL_ATTRACTIONS',
        'TUNNELS',
        'STRATEGIC_SITES',
        'INFORMAL_AREAS',
        'HOTELS',
        'BRIDGES',
        'CHURCHES',
        'COURTS',
        'SCHOOLS',
        'MOSQUES',
        'HOSPITALS',
        'FLOUR_MILLS',
        'AIRPORTS',
        'CULTURAL_ATTRACTIONS',
        'MINISTRIES',
        'EMBASSIES',
        'POWER_STATIONS',
        'WATER_STATIONS',
        'SHELTER_CENTERS',
        'YOUTH_CENTERS',
    ];

    public const MODULE_LABELS = [
        'ARCHAEOLOGICAL_ATTRACTIONS' => 'المزارات الأثرية',
        'RECREATIONAL_ATTRACTIONS'   => 'المزارات الترفيهية',
        'TUNNELS'                    => 'الأنفاق',
        'STRATEGIC_SITES'            => 'المواقع الاستراتيجية',
        'INFORMAL_AREAS'             => 'العشوائيات',
        'HOTELS'                     => 'الفنادق',
        'BRIDGES'                    => 'الكباري',
        'CHURCHES'                   => 'الكنائس',
        'COURTS'                     => 'المحاكم',
        'SCHOOLS'                    => 'المدارس',
        'MOSQUES'                    => 'المساجد',
        'HOSPITALS'                  => 'المستشفيات',
        'FLOUR_MILLS'                => 'المطاحن',
        'AIRPORTS'                   => 'المطارات',
        'CULTURAL_ATTRACTIONS'       => 'المزارات الثقافية',
        'MINISTRIES'                 => 'الوزارات',
        'EMBASSIES'                  => 'السفارات',
        'POWER_STATIONS'             => 'محطات الكهرباء',
        'WATER_STATIONS'             => 'محطات مياه الشرب',
        'SHELTER_CENTERS'            => 'مراكز الإيواء',
        'YOUTH_CENTERS'              => 'مراكز الشباب',
    ];

    /** Fallback keywords (Arabic) used when the _System sheet is missing. */
    private const MODULE_KEYWORDS = [
        'ARCHAEOLOGICAL_ATTRACTIONS' => ['المزارات الأثرية', 'الاماكن الاثرية', 'الاثرية'],
        'RECREATIONAL_ATTRACTIONS'   => ['المزارات الترفيهية', 'الاماكن الترفيهية', 'الترفيهية'],
        'TUNNELS'                    => ['الأنفاق', 'الانفاق'],
        'STRATEGIC_SITES'            => ['المواقع الاستراتيجية', 'الاهداف الحيوية'],
        'INFORMAL_AREAS'             => ['العشوائيات', 'المناطق العشوائية'],
        'HOTELS'                     => ['الفنادق'],
        'BRIDGES'                    => ['الكباري', 'الكبارى'],
        'CHURCHES'                   => ['الكنائس'],
        'COURTS'                     => ['المحاكم'],
        'SCHOOLS'                    => ['المدارس'],
        'MOSQUES'                    => ['المساجد'],
        'HOSPITALS'                  => ['المستشفيات'],
        'FLOUR_MILLS'                => ['المطاحن'],
        'AIRPORTS'                   => ['المطارات'],
        'CULTURAL_ATTRACTIONS'       => ['المزارات الثقافية', 'المواقع الثقافية'],
        'MINISTRIES'                 => ['الوزارات'],
        'EMBASSIES'                  => ['السفارات'],
        'POWER_STATIONS'             => ['محطات الكهرباء'],
        'WATER_STATIONS'             => ['محطات المياه', 'محطات مياه', 'مياه الشرب'],
        'SHELTER_CENTERS'            => ['مراكز الإيواء', 'مراكز الايواء'],
        'YOUTH_CENTERS'              => ['مراكز الشباب', 'مراكز شباب'],
    ];

    private const HEADER_KEYWORDS = [
        'نوع الملكية', 'الاسم', 'اسم المدرسة', 'اسم المركز', 'اسم المستشفى', 'اسم المنطقة',
        'العنوان', 'التليفون', 'رقم الهاتف', 'تليفون', 'هاتف', 'موبايل',
        'المحافظة', 'المديرية', 'مركز', 'المدينة', 'الحي', 'النوع', 'تصنيف',
    ];

    private const LOOKUP_SHEETS = [
        'OwnerScope', 'Governorates', 'Directorates', 'Centers',
        'Cities', 'Districts', 'Neighborhoods',
    ];

    private ?SectorDataImportService $sectorService = null;

    /**
     * Detect a facilities module (MOSQUES, SCHOOLS, ...) from the _System sheet.
     * Returns null when the file is not a "نوتة المعلومات" file.
     */
    public function detectModule(array $sheetsData, ?string $fileName = null): ?string
    {
        foreach ($sheetsData as $sheetName => $rows) {
            if (mb_strtolower(trim((string) $sheetName)) !== '_system') {
                continue;
            }
            foreach ($rows as $row) {
                $key = mb_strtolower(trim((string) ($row[0] ?? '')));
                $val = trim((string) ($row[1] ?? ''));
                if ($key === 'modulecode' && $val !== '') {
                    return strtoupper(preg_replace('/\s+/', '', $val));
                }
            }
        }

        // Fallback only when a البيانات sheet exists (sector files never have one).
        if (!$this->hasDataSheet($sheetsData)) {
            return null;
        }

        $text = $this->normalizeText((string) $fileName . ' ' . $this->allText($sheetsData));
        $best = null;
        $bestLen = 0;
        foreach (self::MODULE_KEYWORDS as $code => $needles) {
            foreach ($needles as $needle) {
                $n = $this->normalizeText($needle);
                if (mb_strlen($n) >= 3 && str_contains($text, $n) && mb_strlen($n) > $bestLen) {
                    $bestLen = mb_strlen($n);
                    $best = $code;
                }
            }
        }
        return $best;
    }

    /**
     * Import all data sheets of one file into facilities_data.
     * Re-importing the same file replaces its previous rows (update semantics).
     */
    public function import(array $sheetsData, string $fileName, string $moduleCode): array
    {
        if (!in_array($moduleCode, self::MODULES, true)) {
            throw new \InvalidArgumentException("نموذج غير معروف: {$moduleCode}");
        }

        $this->deleteForFile($fileName, $moduleCode);

        $moduleName = self::MODULE_LABELS[$moduleCode] ?? $moduleCode;
        $imported = 0;
        $skipped = 0;
        $details = [];

        foreach ($this->dataSheets($sheetsData) as $sheetName => $rows) {
            $result = $this->importSheet($rows, $sheetName, $fileName, $moduleCode, $moduleName);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
            $details[$sheetName] = $result;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'sheets' => $details];
    }

    public function deleteForFile(string $fileName, string $moduleCode): int
    {
        return FacilityData::where('file_name', $fileName)
            ->where('module_code', $moduleCode)
            ->delete();
    }

    private function importSheet(array $rows, string $sheetName, string $fileName, string $moduleCode, string $moduleName): array
    {
        $headerInfo = $this->findHeader($rows);
        if ($headerInfo['header_row'] === null) {
            return ['imported' => 0, 'skipped' => count($rows), 'header_row' => null];
        }

        $imported = 0;
        $skipped = 0;
        $centerService = $this->centerService();
        $lastCenter = null;

        foreach ($this->dataRows($rows, $headerInfo['header_row']) as $row) {
            $data = $this->mapRow($row, $headerInfo, $sheetName, $fileName, $moduleCode, $moduleName, $centerService, $lastCenter);

            if ($data === null) {
                $skipped++;
                continue;
            }

            FacilityData::create($data);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'header_row' => $headerInfo['header_row']];
    }

    private function dataSheets(array $sheetsData): array
    {
        $result = [];
        foreach ($sheetsData as $sheetName => $rows) {
            if ($this->normalizeText((string) $sheetName) === 'البيانات') {
                $result[$sheetName] = $rows;
            }
        }
        if (empty($result)) {
            foreach ($sheetsData as $sheetName => $rows) {
                if ($this->isLookupSheet($sheetName)) {
                    continue;
                }
                $headerInfo = $this->findHeader($rows);
                if ($headerInfo['header_row'] !== null) {
                    $result[$sheetName] = $rows;
                    break;
                }
            }
        }
        return $result;
    }

    private function isLookupSheet(string $sheetName): bool
    {
        $norm = $this->normalizeText($sheetName);
        if ($norm === '' || str_starts_with($norm, '_')) {
            return true;
        }
        if (str_starts_with($norm, 'lookup')) {
            return true;
        }
        foreach (self::LOOKUP_SHEETS as $lookup) {
            if ($norm === $this->normalizeText($lookup)) {
                return true;
            }
        }
        return false;
    }

    private function hasDataSheet(array $sheetsData): bool
    {
        foreach (array_keys($sheetsData) as $sheetName) {
            if ($this->normalizeText((string) $sheetName) === 'البيانات') {
                return true;
            }
        }
        return false;
    }

    private function allText(array $sheetsData): string
    {
        $text = '';
        foreach ($sheetsData as $sheetName => $rows) {
            $text .= ' ' . $sheetName . ' ';
            foreach ($rows as $row) {
                foreach ($row as $cell) {
                    $text .= (string) ($cell ?? '') . ' ';
                }
            }
        }
        return $text;
    }

    private function findHeader(array $rows): array
    {
        $limit = min(count($rows), 6);
        $bestRow = null;
        $bestScore = -999;
        for ($i = 0; $i < $limit; $i++) {
            $score = $this->headerScore($rows[$i]);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $i;
            }
        }
        if ($bestRow === null || $bestScore < 2) {
            return ['header_row' => null, 'headers' => [], 'col_map' => []];
        }

        $headers = [];
        foreach ($rows[$bestRow] as $colIdx => $cell) {
            $headers[$colIdx] = $cell !== null ? trim((string) $cell) : '';
        }

        return ['header_row' => $bestRow, 'headers' => $headers, 'col_map' => $this->buildColMap($headers)];
    }

    private function headerScore(array $row): float
    {
        $score = 0;
        $nonEmpty = 0;
        $numeric = 0;
        foreach ($row as $cell) {
            if ($cell === null || trim((string) $cell) === '') {
                continue;
            }
            $nonEmpty++;
            $value = trim((string) $cell);
            if ($this->isNumericLike($value)) {
                $numeric++;
                continue;
            }
            $norm = $this->normalizeText($value);
            if ($norm === 'لايوجد' || $norm === 'لا يوجد') {
                continue;
            }
            if (preg_match('/^[xyXY]$/', $value)) {
                $score += 1.5;
                continue;
            }
            foreach (self::HEADER_KEYWORDS as $kw) {
                if (str_contains($norm, $this->normalizeText($kw))) {
                    $score += 1.5;
                    break;
                }
            }
        }
        if ($nonEmpty < 2) {
            return -999;
        }
        return $score - $numeric * 0.5;
    }

    private function buildColMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $colIdx => $header) {
            if ($header === '') {
                continue;
            }
            $norm = $this->normalizeText($header);
            $key = null;

            if (preg_match('/^[xX]$/', $header)) {
                $key = 'x';
            } elseif (preg_match('/^[yY]$/', $header)) {
                $key = 'y';
            } elseif ($this->containsAny($norm, ['نوع الملكية', 'الملكية'])) {
                $key = 'ownership';
            } elseif ($this->containsAny($norm, ['نوع', 'تصنيف'])) {
                $key = 'type';
            } elseif ($this->containsAny($norm, ['الاسم', 'اسم'])) {
                $key = 'name';
            } elseif ($this->containsAny($norm, ['العنوان', 'الموقع', 'بالتفصيل'])) {
                $key = 'address';
            } elseif ($this->containsAny($norm, ['تليفون', 'هاتف', 'موبايل', 'الاتصال'])) {
                $key = 'phone';
            } elseif ($this->containsAny($norm, ['المديرية', 'مديرية'])) {
                $key = 'directorate';
            } elseif ($this->containsAny($norm, ['المحافظة', 'محافظة'])) {
                $key = 'governorate';
            } elseif ($this->containsAny($norm, ['المركز', 'مركز'])) {
                $key = 'center';
            } elseif ($this->containsAny($norm, ['المدينة', 'مدينة'])) {
                $key = 'city';
            } elseif ($this->containsAny($norm, ['الحي', 'حي'])) {
                $key = 'district';
            }

            if ($key && (!isset($map[$key]) || $key === 'center')) {
                $map[$key] = $colIdx;
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

    private function dataRows(array $rows, int $headerRow): \Generator
    {
        $n = count($rows);
        for ($i = $headerRow + 1; $i < $n; $i++) {
            $row = $rows[$i];
            if ($this->isEmptyRow($row)) {
                continue;
            }
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

    private function mapRow(array $row, array $headerInfo, string $sheetName, string $fileName, string $moduleCode, string $moduleName, SectorDataImportService $centerService, ?Center &$lastCenter): ?array
    {
        $colMap = $headerInfo['col_map'] ?? [];
        $headers = $headerInfo['headers'] ?? [];

        $mapped = [];
        $extraData = [];
        foreach ($row as $colIdx => $cell) {
            $value = $this->clean($cell);
            if ($value === null) {
                continue;
            }
            $header = $headers[$colIdx] ?? '';
            $key = array_search($colIdx, $colMap, true);
            if ($key !== false) {
                $mapped[$key] = $value;
            } else {
                $extraData[$header !== '' ? $header : 'col_' . ($colIdx + 1)] = $value;
            }
        }

        $name = $this->normalizeName($mapped['name'] ?? $this->firstTextCell($row));
        $type = $mapped['ownership'] ?? $mapped['type'] ?? null;
        $address = $mapped['address'] ?? null;
        $phone = $mapped['phone'] ?? null;
        $governorate = $mapped['governorate'] ?? null;
        $directorate = $mapped['directorate'] ?? null;
        $city = $mapped['city'] ?? null;
        $district = $mapped['district'] ?? null;

        $centerRaw = $mapped['center'] ?? null;
        $center = $centerRaw ? $centerService->centerFromText($centerRaw) : null;
        if (!$center && $lastCenter) {
            $center = $lastCenter;
        } elseif ($center) {
            $lastCenter = $center;
        }
        if (!$center) {
            $center = $centerService->centerFromText($city)
                ?? $centerService->centerFromText($address)
                ?? $centerService->centerFromText($name);
        }

        [$x, $y] = $this->parseCoords($mapped['x'] ?? null, $mapped['y'] ?? null);

        if ($name === null && empty($extraData) && $address === null && $phone === null && $governorate === null) {
            return null;
        }

        return [
            'module_code'  => $moduleCode,
            'module_name'  => $moduleName,
            'file_name'    => $fileName,
            'sheet_name'   => $sheetName,
            'center_id'    => $center?->id,
            'center_name'  => $centerRaw ?: $center?->name,
            'name'         => $name,
            'type'         => $type,
            'address'      => $address,
            'phone'        => $phone,
            'governorate'  => $governorate,
            'directorate'  => $directorate,
            'city'         => $city,
            'district'     => $district,
            'x_coord'      => $x,
            'y_coord'      => $y,
            'extra_data'   => !empty($extraData) ? $extraData : null,
        ];
    }

    private function clean($cell): ?string
    {
        if ($cell === null) {
            return null;
        }
        $value = trim((string) $cell);
        if ($value === '' || $value === '0' || $value === '0.0' || $value === '0,0') {
            return null;
        }
        $norm = $this->normalizeText($value);
        if ($norm === 'لايوجد' || $norm === 'لا يوجد') {
            return null;
        }
        return $value;
    }

    private function normalizeName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }
        $value = trim($name);
        if ($value === '' || $value === '0') {
            return null;
        }
        return mb_substr($value, 0, 255);
    }

    private function firstTextCell(array $row): ?string
    {
        foreach ($row as $cell) {
            $value = $this->clean($cell);
            if ($value === null) {
                continue;
            }
            if (preg_match('/\p{Arabic}/u', $value) && !$this->isNumericLike($value)) {
                return mb_substr($value, 0, 255);
            }
        }
        return null;
    }

    private function isNumericLike(string $value): bool
    {
        $v = trim($value);
        if ($v === '') {
            return false;
        }
        if (is_numeric(str_replace(',', '.', $v))) {
            return true;
        }
        return (bool) preg_match('/^[-+]?[0-9][0-9.,]*$/', $v);
    }

    private function parseCoords(?string $xRaw, ?string $yRaw): array
    {
        $x = $this->parseCoord($xRaw);
        $y = $this->parseCoord($yRaw);
        if ($x !== null || $y !== null) {
            return [$x, $y];
        }

        // UTM pair detection (Zone 36N used across Kafr el Sheikh data).
        $xv = $this->toNumeric($xRaw);
        $yv = $this->toNumeric($yRaw);
        if ($xv !== null && $yv !== null) {
            $absX = abs($xv);
            $absY = abs($yv);
            if ($absX >= 100000 && $absX < 800000 && $absY >= 3000000 && $absY < 10000000) {
                [$lat, $lng] = $this->utmToLatLng($xv, $yv, 36);
                return [$this->round8($lng), $this->round8($lat)];
            }
            if ($absY >= 100000 && $absY < 800000 && $absX >= 3000000 && $absX < 10000000) {
                [$lat, $lng] = $this->utmToLatLng($yv, $xv, 36);
                return [$this->round8($lng), $this->round8($lat)];
            }
        }

        return [null, null];
    }

    private function parseCoord(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $v = trim($value);
        if ($v === '' || $v === '0' || $v === '0.0' || $v === '0,0') {
            return null;
        }

        if (preg_match('/^\s*(\d{1,3})\s*[°º]\s*(\d{1,2}(?:[.,]\d+)?)\s*[\'′`]\s*(\d{1,2}(?:[.,]\d+)?)\s*["″"]?\s*([NSEWnsew])/', $v, $m)) {
            $deg = (float) str_replace(',', '.', $m[1])
                + (float) str_replace(',', '.', $m[2]) / 60
                + (float) str_replace(',', '.', $m[3]) / 3600;
            $hemi = strtoupper($m[4]);
            if ($hemi === 'S' || $hemi === 'W') {
                $deg = -$deg;
            }
            return $this->round8($deg);
        }

        $num = $this->toNumeric($v);
        if ($num === null) {
            return null;
        }
        $abs = abs($num);
        if ($abs == 0) {
            return null;
        }
        if ($abs <= 180) {
            return $this->round8($num);
        }
        // Lat/lng without decimal separator (e.g. 31548410359 => 31.548410359).
        if ($abs >= 1e8 && $abs < 1e13) {
            for ($p = 8; $p <= 12; $p++) {
                $val = $num / (10 ** $p);
                if ($val >= 20 && $val <= 37) {
                    return $this->round8($val);
                }
            }
        }
        return null;
    }

    private function toNumeric(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $v = trim(str_replace(',', '.', $value));
        if ($v === '' || !is_numeric($v)) {
            return null;
        }
        return (float) $v;
    }

    private function utmToLatLng(float $easting, float $northing, int $zone): array
    {
        $a = 6378137.0;
        $f = 1.0 / 298.257223563;
        $k0 = 0.9996;
        $e2 = 2 * $f - $f * $f;
        $ep2 = $e2 / (1 - $e2);
        $lon0 = deg2rad(6 * $zone - 183);
        $x = $easting - 500000.0;
        $y = $northing;

        $m = $y / $k0;
        $e4 = $e2 * $e2;
        $e6 = $e4 * $e2;
        $mu = $m / ($a * (1 - $e2 / 4 - 3 * $e4 / 64 - 5 * $e6 / 256));

        $e1 = (1 - sqrt(1 - $e2)) / (1 + sqrt(1 - $e2));
        $j1 = 3 * $e1 / 2 - 27 * $e1 ** 3 / 32;
        $j2 = 21 * $e1 ** 2 / 16 - 55 * $e1 ** 4 / 32;
        $j3 = 151 * $e1 ** 3 / 96;
        $j4 = 1097 * $e1 ** 4 / 512;
        $phi = $mu + $j1 * sin(2 * $mu) + $j2 * sin(4 * $mu) + $j3 * sin(6 * $mu) + $j4 * sin(8 * $mu);

        $sinp = sin($phi);
        $cosp = cos($phi);
        $tanp = tan($phi);
        $c = $ep2 * $cosp * $cosp;
        $t = $tanp * $tanp;
        $n = $a / sqrt(1 - $e2 * $sinp * $sinp);
        $rho = $a * (1 - $e2) / (1 - $e2 * $sinp * $sinp) ** 1.5;
        $aa = $x / $k0 / $n;

        $lat = $phi - ($n * $tanp / $rho) * (
            $aa * $aa / 2
            - (5 + 3 * $t + 10 * $c - 4 * $c * $c - 9 * $ep2) * $aa ** 4 / 24
            + (61 + 90 * $t + 298 * $c + 45 * $t * $t - 252 * $ep2 - 3 * $c * $c) * $aa ** 6 / 720
        );
        $lon = $lon0 + ($aa - (1 + 2 * $t + $c) * $aa ** 3 / 6 + (5 - 2 * $c + 28 * $t - 3 * $c * $c + 8 * $ep2 + 24 * $t * $t) * $aa ** 5 / 120) / $cosp;

        return [rad2deg($lat), rad2deg($lon)];
    }

    private function round8(float $value): float
    {
        return round($value, 8);
    }

    private function centerService(): SectorDataImportService
    {
        if ($this->sectorService === null) {
            $this->sectorService = new SectorDataImportService();
        }
        return $this->sectorService;
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $value = preg_replace('/[\x{0640}\x{064B}-\x{0652}]/u', '', $value);
        $value = str_replace(
            ['أ', 'إ', 'آ', 'ى', 'ة', 'ئ'],
            ['ا', 'ا', 'ا', 'ي', 'ه', 'ي'],
            $value
        );
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }
}
