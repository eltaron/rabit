<?php

namespace App\Livewire;

use App\Services\ExcelImportService;
use App\Services\FacilityDataImportService;
use App\Services\SectorDataImportService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ExcelUploader extends Component
{
    use WithFileUploads;

    public $file;
    public string $status = '';
    public array $sheets = [];
    public array $previewData = [];
    public string $selectedSheet = '';
    public string $fileType = '';
    public bool $importing = false;
    public ?string $resultMessage = null;
    public ?string $resultType = null;
    public int $totalRows = 0;
    public int $totalSheets = 0;

    protected $rules = [
        'file' => 'required|file|mimes:xlsx,xls|max:20480',
    ];

    public function updatedFile(): void
    {
        $this->resetState();
        $this->validate();

        try {
            $this->status = 'processing';
            $filePath = $this->file->getRealPath();
            $originalName = $this->file->getClientOriginalName();

            $service = new ExcelImportService();
            $this->sheets = $service->parseFile($filePath);
            $this->totalSheets = count($this->sheets);
            $this->totalRows = array_sum(array_map(fn($rows) => count($rows), $this->sheets));

            $this->fileType = $service->detectFileType($this->sheets, $originalName);

            $this->selectedSheet = array_key_first($this->sheets);
            $this->loadPreview();

            $this->status = 'ready';
        } catch (\Exception $e) {
            $this->status = 'error';
            $this->resultMessage = 'خطأ في قراءة الملف: ' . $e->getMessage();
            $this->resultType = 'danger';
        }
    }

    public function loadPreview(): void
    {
        if (empty($this->selectedSheet) || !isset($this->sheets[$this->selectedSheet])) {
            $this->previewData = [];
            return;
        }

        $rows = $this->sheets[$this->selectedSheet];
        $this->previewData = array_slice($rows, 0, 15);
    }

    public function import(): void
    {
        $this->importing = true;
        $this->resultMessage = null;

        try {
            $filePath = $this->file->getRealPath();
            $fileName = $this->file->getClientOriginalName();
            $service = new ExcelImportService();

            $result = match(true) {
                in_array($this->fileType, FacilityDataImportService::MODULES, true)
                    => (new FacilityDataImportService())->import($this->sheets, $fileName, $this->fileType),
                in_array($this->fileType, SectorDataImportService::SECTORS, true)
                    => (new SectorDataImportService())->import($this->sheets, $fileName, $this->fileType),
                $this->fileType === 'maintenance' => $service->importMaintenance($this->sheets, $fileName),
                $this->fileType === 'vehicles'    => $service->importVehicles($this->sheets, $fileName),
                $this->fileType === 'strategic'   => $service->importStrategic($this->sheets, $fileName),
                default                           => throw new \Exception('نوع الملف غير معروف'),
            };

            $this->resultMessage = "تم الاستيراد بنجاح! {$result['imported']} سجل تم إدخاله" .
                ($result['skipped'] > 0 ? " و {$result['skipped']} سجل تم تخطيه" : '');
            $this->resultType = 'success';
            $this->status = 'done';
        } catch (\Exception $e) {
            $this->resultMessage = 'خطأ أثناء الاستيراد: ' . $e->getMessage();
            $this->resultType = 'danger';
        } finally {
            $this->importing = false;
        }
    }

    public function resetState(): void
    {
        $this->sheets = [];
        $this->previewData = [];
        $this->selectedSheet = '';
        $this->fileType = '';
        $this->totalRows = 0;
        $this->totalSheets = 0;
        $this->resultMessage = null;
        $this->resultType = null;
        $this->status = '';
    }

    public function getFileTypeName(): string
    {
        if (in_array($this->fileType, FacilityDataImportService::MODULES, true)) {
            return 'نموذج ' . (FacilityDataImportService::MODULE_LABELS[$this->fileType] ?? $this->fileType);
        }
        if (in_array($this->fileType, SectorDataImportService::SECTORS, true)) {
            return 'قاعدة بيانات ' . (SectorDataImportService::SECTOR_LABELS[$this->fileType] ?? $this->fileType);
        }
        return match($this->fileType) {
            'maintenance' => 'سجل خطة الصيانة الشهرية',
            'vehicles'    => 'بيان المركبات',
            'strategic'   => 'البيان الاستراتيجي',
            default       => 'غير معروف',
        };
    }

    public function render()
    {
        return view('livewire.excel-uploader');
    }
}
