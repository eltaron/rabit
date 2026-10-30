<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\MaintenancePlan;
use App\Models\StrategicData;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class ExcelImportService
{
    public function parseFile(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();

        $result = [];
        foreach ($sheetNames as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;

            $rows = [];
            $highestRow = $sheet->getHighestRow();
            $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestCol; $col++) {
                    $cell = $sheet->getCell([$col, $row]);
                    $value = $cell->getValue();
                    $rowData[] = $value;
                }
                $rows[] = $rowData;
            }

            $result[trim($sheetName)] = $rows;
        }

        $spreadsheet->disconnectWorksheets();
        $spreadsheet->garbageCollect();

        return $result;
    }

    public function detectFileType(array $sheetsData, ?string $fileName = null): string
    {
        $facility = (new FacilityDataImportService())->detectModule($sheetsData, $fileName);
        if ($facility !== null) {
            return $facility;
        }

        $sector = (new SectorDataImportService())->detectSector($fileName, $sheetsData);
        if ($sector !== null) {
            return $sector;
        }

        foreach ($sheetsData as $sheetName => $rows) {
            $allText = implode(' ', array_map(fn($row) => implode(' ', array_map(fn($c) => (string)($c ?? ''), $row)), $rows));

            if (str_contains($allText, 'خطة الصيانة') || str_contains($allText, 'الأسبوع')) {
                return 'maintenance';
            }
            if (str_contains($allText, 'رقم اللوحة') || str_contains($allText, 'الشاسيه') || str_contains($allText, 'المحرك') || str_contains($allText, 'الموديل')) {
                return 'vehicles';
            }
            if (str_contains($allText, 'الاستراتيجية') || str_contains($allText, 'الاجمالي') || str_contains($allText, 'الاطفاء') || str_contains($allText, 'المساجد')) {
                return 'strategic';
            }
        }

        return 'unknown';
    }

    public function importMaintenance(array $sheetsData, string $fileName): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($sheetsData as $sheetName => $rows) {
            $centerName = $sheetName;

            $headerRows = $this->findMaintenanceHeaders($rows);
            if (!$headerRows) {
                $skipped += count($rows);
                continue;
            }

            [$headerRow, $subHeaderRow] = $headerRows;
            $dataStartRow = $subHeaderRow + 1;

            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row[1]) && empty($row[2]) && empty($row[3])) {
                    continue;
                }

                $vehicleNumber = $this->cellValue($row, 2);
                if (empty($vehicleNumber)) {
                    $skipped++;
                    continue;
                }

                MaintenancePlan::create([
                    'serial_number'   => $this->cellValue($row, 1),
                    'center_name'     => $centerName,
                    'vehicle_number'  => $vehicleNumber,
                    'vehicle_type'    => $this->cellValue($row, 3),
                    'usage_group'     => $this->cellValue($row, 4),
                    'week1_planned'   => $this->parseCheckmark($this->cellValue($row, 5)),
                    'week1_executed'  => $this->parseCheckmark($this->cellValue($row, 6)),
                    'week2_planned'   => $this->parseCheckmark($this->cellValue($row, 7)),
                    'week2_executed'  => $this->parseCheckmark($this->cellValue($row, 8)),
                    'week3_planned'   => $this->parseCheckmark($this->cellValue($row, 9)),
                    'week3_executed'  => $this->parseCheckmark($this->cellValue($row, 10)),
                    'week4_planned'   => $this->parseCheckmark($this->cellValue($row, 11)),
                    'week4_executed'  => $this->parseCheckmark($this->cellValue($row, 12)),
                    'month'           => $this->extractMonth($rows),
                    'year'            => $this->extractYear($rows),
                    'file_name'       => $fileName,
                ]);
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function findMaintenanceHeaders(array $rows): ?array
    {
        for ($i = 0; $i < min(15, count($rows)); $i++) {
            $rowText = implode(' ', array_map(fn($c) => (string)($c ?? ''), $rows[$i]));
            if (str_contains($rowText, 'م') && (str_contains($rowText, 'رقم العربة') || str_contains($rowText, 'الأسبوع'))) {
                return [$i, $i + 1 < count($rows) ? $i + 1 : $i];
            }
        }
        return [6, 7];
    }

    public function importVehicles(array $sheetsData, string $fileName): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($sheetsData as $sheetName => $rows) {
            $status = $this->mapVehicleStatus($sheetName);
            $headerInfo = $this->findVehicleHeaders($rows);
            $headerRow = $headerInfo['row'] ?? 2;
            $colMap = $headerInfo['col_map'] ?? [];

            $dataStartRow = $headerRow + 1;
            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row[1]) && empty($row[2])) {
                    $skipped++;
                    continue;
                }

                $data = $this->mapVehicleRow($row, $colMap, $sheetName, $fileName, $status);
                if (empty($data['vehicle_type']) && empty($data['license_plate']) && empty($data['engine_number'])) {
                    $skipped++;
                    continue;
                }

                Vehicle::create($data);
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function findVehicleHeaders(array $rows): array
    {
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $rowText = implode(' ', array_map(fn($c) => (string)($c ?? ''), $rows[$i]));
            if (str_contains($rowText, 'م') || str_contains($rowText, 'رقم اللوحة')) {
                $colMap = [];
                foreach ($rows[$i] as $colIdx => $header) {
                    if ($header === null) continue;
                    $h = trim((string)$header);
                    if (str_contains($h, 'رقم اللوحة')) $colMap['license_plate'] = $colIdx;
                    elseif (str_contains($h, 'رقم الشاسيه') || str_contains($h, 'الشاسيه')) $colMap['chassis_number'] = $colIdx;
                    elseif (str_contains($h, 'رقم المحرك')) $colMap['engine_number'] = $colIdx;
                    elseif (str_contains($h, 'النوع') && !str_contains($h, 'الوقود') && !str_contains($h, 'التدبير')) $colMap['vehicle_type'] = $colIdx;
                    elseif (str_contains($h, 'اللون')) $colMap['color'] = $colIdx;
                    elseif (str_contains($h, 'الموديل')) $colMap['model_year'] = $colIdx;
                    elseif (str_contains($h, 'سنة الصنع')) $colMap['manufacture_year'] = $colIdx;
                    elseif (str_contains($h, 'الحالة الفنية')) $colMap['technical_condition'] = $colIdx;
                    elseif (str_contains($h, 'نوع الوقود')) $colMap['fuel_type'] = $colIdx;
                    elseif (str_contains($h, 'نوع التدبير')) $colMap['procurement_type'] = $colIdx;
                    elseif (str_contains($h, 'جهة التدبير')) $colMap['procurement_source'] = $colIdx;
                    elseif (str_contains($h, 'سنة التدبير')) $colMap['procurement_year'] = $colIdx;
                    elseif (str_contains($h, 'العداد') || str_contains($h, 'قراءة')) $colMap['odometer_reading'] = $colIdx;
                    elseif (str_contains($h, 'التجهيز') && !str_contains($h, 'جهة')) $colMap['equipment_level'] = $colIdx;
                    elseif (str_contains($h, 'مكان التواجد')) $colMap['location'] = $colIdx;
                    elseif (str_contains($h, 'عقد صيانة للمركبة') || str_contains($h, 'عقد صيانة')) $colMap['maintenance_contract'] = $colIdx;
                    elseif (str_contains($h, 'عقد صيانة للتجهيز')) $colMap['equipment_maintenance_contract'] = $colIdx;
                    elseif (str_contains($h, 'الاستخدام السنوي') || str_contains($h, 'مسافات')) $colMap['annual_usage_distance'] = $colIdx;
                    elseif (str_contains($h, 'نوع العطل')) $colMap['breakdown_type'] = $colIdx;
                    elseif (str_contains($h, 'تاريخ العطل')) $colMap['breakdown_date'] = $colIdx;
                    elseif (str_contains($h, 'الإجراء') || str_contains($h, 'المتخذ')) $colMap['action_taken'] = $colIdx;
                    elseif (str_contains($h, 'تعذر') || str_contains($h, 'الاصلاح')) $colMap['reason_repair_failed'] = $colIdx;
                    elseif (str_contains($h, 'الجهة') && str_contains($h, 'الوزارة')) $colMap['entity'] = $colIdx;
                    elseif (str_contains($h, 'الجهة التابعة')) $colMap['affiliated_entity'] = $colIdx;
                    elseif (str_contains($h, 'الحاجة') || str_contains($h, 'مدى')) $colMap['entity_need'] = $colIdx;
                    elseif (str_contains($h, 'الصورة')) $colMap['vehicle_photo'] = $colIdx;
                    elseif (str_contains($h, 'الاستغناء')) $colMap['reason_surplus'] = $colIdx;
                    elseif (str_contains($h, 'الصلاحية')) $colMap['technical_validity'] = $colIdx;
                }
                return ['row' => $i, 'col_map' => $colMap];
            }
        }
        return ['row' => 2, 'col_map' => []];
    }

    private function mapVehicleRow(array $row, array $colMap, string $sheetName, string $fileName, string $status): array
    {
        return [
            'serial_number'                => $this->cellValue($row, 1),
            'entity'                       => $this->getCellByMap($row, $colMap, 'entity') ?: $this->cellValue($row, 2),
            'affiliated_entity'            => $this->getCellByMap($row, $colMap, 'affiliated_entity'),
            'license_plate'                => $this->getCellByMap($row, $colMap, 'license_plate') ?: $this->cellValue($row, 6),
            'chassis_number'               => $this->getCellByMap($row, $colMap, 'chassis_number') ?: $this->cellValue($row, 7),
            'engine_number'                => $this->getCellByMap($row, $colMap, 'engine_number') ?: $this->cellValue($row, 8),
            'vehicle_type'                 => $this->getCellByMap($row, $colMap, 'vehicle_type') ?: $this->cellValue($row, 9),
            'color'                        => $this->getCellByMap($row, $colMap, 'color') ?: $this->cellValue($row, 10),
            'model_year'                   => $this->getCellByMap($row, $colMap, 'model_year') ?: $this->cellValue($row, 11),
            'manufacture_year'             => $this->getCellByMap($row, $colMap, 'manufacture_year'),
            'technical_condition'          => $this->getCellByMap($row, $colMap, 'technical_condition') ?: $this->cellValue($row, 12),
            'fuel_type'                    => $this->getCellByMap($row, $colMap, 'fuel_type'),
            'procurement_type'             => $this->getCellByMap($row, $colMap, 'procurement_type'),
            'procurement_source'           => $this->getCellByMap($row, $colMap, 'procurement_source'),
            'procurement_year'             => $this->getCellByMap($row, $colMap, 'procurement_year'),
            'odometer_reading'             => $this->getCellByMap($row, $colMap, 'odometer_reading'),
            'equipment_level'              => $this->getCellByMap($row, $colMap, 'equipment_level'),
            'location'                     => $this->getCellByMap($row, $colMap, 'location') ?: $this->cellValue($row, 18),
            'maintenance_contract'         => $this->getCellByMap($row, $colMap, 'maintenance_contract'),
            'equipment_maintenance_contract' => $this->getCellByMap($row, $colMap, 'equipment_maintenance_contract'),
            'annual_usage_distance'        => $this->getCellByMap($row, $colMap, 'annual_usage_distance'),
            'breakdown_type'               => $this->getCellByMap($row, $colMap, 'breakdown_type'),
            'breakdown_date'               => $this->getCellByMap($row, $colMap, 'breakdown_date'),
            'action_taken'                 => $this->getCellByMap($row, $colMap, 'action_taken'),
            'reason_repair_failed'         => $this->getCellByMap($row, $colMap, 'reason_repair_failed'),
            'entity_need'                  => $this->getCellByMap($row, $colMap, 'entity_need'),
            'reason_surplus'               => $this->getCellByMap($row, $colMap, 'reason_surplus'),
            'technical_validity'           => $this->getCellByMap($row, $colMap, 'technical_validity'),
            'vehicle_photo'                => $this->getCellByMap($row, $colMap, 'vehicle_photo'),
            'status'                       => $status,
            'sheet_name'                   => $sheetName,
            'file_name'                    => $fileName,
        ];
    }

    public function importStrategic(array $sheetsData, string $fileName): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($sheetsData as $sheetName => $rows) {
            if (empty($rows) || count($rows) < 2) {
                $skipped++;
                continue;
            }

            $headerInfo = $this->findStrategicHeaders($rows, $sheetName);
            $headerRow = $headerInfo['row'];
            $headers = $headerInfo['headers'];
            $colMap = $headerInfo['col_map'];

            $dataStartRow = $headerRow + 1;
            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row[0]) && empty($row[1])) {
                    $skipped++;
                    continue;
                }

                $additionalData = [];
                foreach ($row as $colIdx => $val) {
                    if ($val !== null && $val !== '' && !isset($colMap[$colIdx])) {
                        $additionalData[$headers[$colIdx] ?? "col_{$colIdx}"] = $val;
                    }
                }

                StrategicData::create([
                    'serial_number'    => $this->cellValue($row, 0) ?: $this->cellValue($row, 1),
                    'category'         => $sheetName,
                    'name'             => $this->getCellByMap($row, $colMap, 'name') ?: $this->getCellByMap($row, $colMap, 'headquarters'),
                    'headquarters'     => $this->getCellByMap($row, $colMap, 'headquarters'),
                    'address'          => $this->getCellByMap($row, $colMap, 'address'),
                    'phone'            => $this->getCellByMap($row, $colMap, 'phone'),
                    'center'           => $this->getCellByMap($row, $colMap, 'center'),
                    'city'             => $this->getCellByMap($row, $colMap, 'city'),
                    'neighborhood'     => $this->getCellByMap($row, $colMap, 'neighborhood'),
                    'governorate'      => $this->getCellByMap($row, $colMap, 'governorate'),
                    'type'             => $this->getCellByMap($row, $colMap, 'type'),
                    'governorate_code' => $this->getCellByMap($row, $colMap, 'governorate_code'),
                    'center_code'      => $this->getCellByMap($row, $colMap, 'center_code'),
                    'city_code'        => $this->getCellByMap($row, $colMap, 'city_code'),
                    'x_coord'          => $this->getCellByMap($row, $colMap, 'x_coord'),
                    'y_coord'          => $this->getCellByMap($row, $colMap, 'y_coord'),
                    'additional_data'  => !empty($additionalData) ? $additionalData : null,
                    'sheet_name'       => $sheetName,
                    'file_name'        => $fileName,
                ]);
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function findStrategicHeaders(array $rows, string $sheetName): array
    {
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $rowText = implode(' ', array_map(fn($c) => (string)($c ?? ''), $rows[$i]));
            if (str_contains($rowText, 'الاسم') || str_contains($rowText, 'المقر') || str_contains($rowText, 'م ') || str_contains($rowText, 'التليفون')) {
                $headers = [];
                $colMap = [];
                foreach ($rows[$i] as $colIdx => $header) {
                    if ($header === null) continue;
                    $h = trim((string)$header);
                    $headers[$colIdx] = $h;
                    if ($h === 'م' || preg_match('/^م\s/', $h)) $colMap[$colIdx] = 'serial';
                    elseif (str_contains($h, 'الاسم') || $h === 'المقر') $colMap[$colIdx] = 'name';
                    elseif (str_contains($h, 'المقر')) $colMap[$colIdx] = 'headquarters';
                    elseif (str_contains($h, 'العنوان')) $colMap[$colIdx] = 'address';
                    elseif (str_contains($h, 'التليفون') || str_contains($h, 'هاتف')) $colMap[$colIdx] = 'phone';
                    elseif (str_contains($h, 'المركز') && !str_contains($h, 'كود')) $colMap[$colIdx] = 'center';
                    elseif (str_contains($h, 'المدينة') && !str_contains($h, 'كود')) $colMap[$colIdx] = 'city';
                    elseif (str_contains($h, 'الحي')) $colMap[$colIdx] = 'neighborhood';
                    elseif (str_contains($h, 'المحافظة') && !str_contains($h, 'كود')) $colMap[$colIdx] = 'governorate';
                    elseif (str_contains($h, 'النوع')) $colMap[$colIdx] = 'type';
                    elseif (str_contains($h, 'كود المحافظة')) $colMap[$colIdx] = 'governorate_code';
                    elseif (str_contains($h, 'كود المركز')) $colMap[$colIdx] = 'center_code';
                    elseif (str_contains($h, 'كود المدينة')) $colMap[$colIdx] = 'city_code';
                    elseif ($h === 'X' || $h === 'x') $colMap[$colIdx] = 'x_coord';
                    elseif ($h === 'Y' || $h === 'y') $colMap[$colIdx] = 'y_coord';
                }
                return ['row' => $i, 'headers' => $headers, 'col_map' => $colMap];
            }
        }
        return ['row' => 2, 'headers' => [], 'col_map' => []];
    }

    private function mapVehicleStatus(string $sheetName): string
    {
        return match(true) {
            str_contains($sheetName, 'صالح') => 'صالح',
            str_contains($sheetName, 'عاطل') => 'عاطل',
            str_contains($sheetName, 'جسيم') => 'جسيم',
            str_contains($sheetName, 'مستغني') => 'مستغني',
            default => 'مجمع',
        };
    }

    private function cellValue(array $row, int $index): ?string
    {
        $val = $row[$index] ?? null;
        if ($val === null || $val === '') return null;
        return (string) $val;
    }

    private function getCellByMap(array $row, array $colMap, string $key): ?string
    {
        $colIdx = $colMap[$key] ?? null;
        if ($colIdx === null) return null;
        return $this->cellValue($row, $colIdx);
    }

    private function parseCheckmark($value): bool
    {
        if ($value === null || $value === '') return false;
        $s = strtolower(trim((string)$value));
        return in_array($s, ['√', 'ü', '✓', 'x', '1', 'true', 'نعم', '✔']);
    }

    private function extractMonth(array $rows): ?int
    {
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $rowText = implode(' ', array_map(fn($c) => (string)($c ?? ''), $rows[$i]));
            if (preg_match('/شهر\s*\(\s*(\d+)\s*\)/', $rowText, $m)) {
                return (int) $m[1];
            }
        }
        return (int) now()->month;
    }

    private function extractYear(array $rows): ?int
    {
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $rowText = implode(' ', array_map(fn($c) => (string)($c ?? ''), $rows[$i]));
            if (preg_match('/(\d{4})/', $rowText, $m)) {
                return (int) $m[1];
            }
        }
        return (int) now()->year;
    }
}
