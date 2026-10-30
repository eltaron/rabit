<?php

namespace App\Livewire;

use App\Models\FormField;
use App\Models\Report;
use Livewire\Component;

class ReportPreview extends Component
{
    public ?int $reportId = null;
    public ?Report $report = null;
    public array $selectedFields = [];
    public array $reportConfig = [
        'orientation' => 'portrait',
        'show_header' => true,
        'show_footer' => true,
        'show_logo' => true,
        'include_date' => true,
        'font_size' => 'medium',
        'rtl' => true,
    ];
    public array $availableFields = [];
    public array $submissionData = [];
    public bool $isUpdating = false;

    protected array $rules = [
        'selectedFields' => 'array',
        'reportConfig.orientation' => 'in:portrait,landscape',
        'reportConfig.show_header' => 'boolean',
        'reportConfig.show_footer' => 'boolean',
        'reportConfig.show_logo' => 'boolean',
        'reportConfig.include_date' => 'boolean',
        'reportConfig.font_size' => 'in:small,medium,large',
        'reportConfig.rtl' => 'boolean',
    ];

    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;

        $this->report = Report::with([
            'formSubmission.formTemplate.formFields',
            'formSubmission.submissionData.formField',
        ])->findOrFail($reportId);

        $this->selectedFields = $this->report->selected_fields ?? [];
        $this->reportConfig = array_merge($this->reportConfig, $this->report->report_config ?? []);

        $this->loadFields();
        $this->loadSubmissionData();
    }

    public function loadFields(): void
    {
        if (!$this->report) {
            return;
        }

        $this->availableFields = ($this->report->formSubmission?->formTemplate?->formFields ?? collect())
            ->sortBy('sort_order')
            ->values()
            ->map(fn (FormField $field) => [
                'id' => $field->id,
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
            ])
            ->toArray();
    }

    public function loadSubmissionData(): void
    {
        if (!$this->report) {
            return;
        }

        foreach ($this->report->formSubmission?->submissionData ?? [] as $item) {
            $this->submissionData[$item->form_field_id] = [
                'label' => $item->formField?->label,
                'value' => $item->value,
                'type' => $item->formField?->type,
            ];
        }
    }

    public function updatePreview(): void
    {
        $this->isUpdating = true;

        $this->report->update([
            'selected_fields' => $this->selectedFields,
            'report_config' => $this->reportConfig,
        ]);

        $this->isUpdating = false;

        $this->dispatch('previewUpdated');
    }

    public function updateConfig(string $key, mixed $value): void
    {
        $this->reportConfig[$key] = $value;
        $this->updatePreview();
    }

    public function toggleField(int $fieldId): void
    {
        $key = array_search($fieldId, $this->selectedFields);
        if ($key !== false) {
            unset($this->selectedFields[$key]);
            $this->selectedFields = array_values($this->selectedFields);
        } else {
            $this->selectedFields[] = $fieldId;
        }

        $this->updatePreview();
    }

    public function selectAllFields(): void
    {
        $this->selectedFields = array_column($this->availableFields, 'id');
        $this->updatePreview();
    }

    public function deselectAllFields(): void
    {
        $this->selectedFields = [];
        $this->updatePreview();
    }

    public function getPreviewData(): array
    {
        $preview = [];
        foreach ($this->selectedFields as $fieldId) {
            if (isset($this->submissionData[$fieldId])) {
                $preview[] = $this->submissionData[$fieldId];
            }
        }
        return $preview;
    }

    public function render()
    {
        return view('livewire.report-preview');
    }
}
