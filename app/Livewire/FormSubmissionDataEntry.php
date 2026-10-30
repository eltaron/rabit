<?php

namespace App\Livewire;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionData;
use App\Models\WorkflowLog;
use Livewire\Component;

class FormSubmissionDataEntry extends Component
{
    public ?int $submissionId = null;
    public ?FormSubmission $submission = null;
    public array $fields = [];
    public array $rows = [];
    public bool $isSaving = false;
    public int $newRowCount = 1;
    public bool $saved = false;
    public int $totalRows = 0;
    public bool $tooLarge = false;

    /** أكبر عدد صفوف للتحرير داخل المتصفح؛ الأكبر يُحرَّر عبر Excel */
    public const MAX_GRID_ROWS = 300;

    public function mount(int $submissionId): void
    {
        $this->submissionId = $submissionId;

        $this->submission = FormSubmission::with('formTemplate.formFields')->findOrFail($submissionId);
        $this->totalRows = FormSubmissionData::where('form_submission_id', $submissionId)->distinct()->count('row_index');
        $this->tooLarge = $this->totalRows > self::MAX_GRID_ROWS;

        abort_unless(\App\Http\Controllers\Form\FormExcelController::canEditData(auth()->user(), $this->submission), 403);

        $this->fields = $this->submission->formTemplate->formFields
            ->where('is_visible', true)
            ->sortBy('sort_order')
            ->values()
            ->toArray();

        if (! $this->tooLarge) {
            $this->loadExistingData();
        }
    }

    private function loadExistingData(): void
    {
        $this->rows = [];
        $grouped = FormSubmissionData::where('form_submission_id', $this->submissionId)
            ->get(['row_index', 'form_field_id', 'value'])->groupBy('row_index');

        if ($grouped->isEmpty()) {
            $this->addRow();
            return;
        }

        foreach ($grouped as $rowIndex => $entries) {
            $row = [];
            foreach ($this->fields as $field) {
                $entry = $entries->firstWhere('form_field_id', $field['id']);
                $row[$field['id']] = $entry?->value ?? '';
            }
            $this->rows[$rowIndex] = $row;
        }

        ksort($this->rows);
        $this->rows = array_values($this->rows);
    }

    public function addRow(): void
    {
        $emptyRow = [];
        foreach ($this->fields as $field) {
            $emptyRow[$field['id']] = '';
        }
        $this->rows[] = $emptyRow;
    }

    public function removeRow(int $index): void
    {
        if (count($this->rows) > 1) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
        }
    }

    public function duplicateRow(int $index): void
    {
        if (isset($this->rows[$index])) {
            array_splice($this->rows, $index + 1, 0, [$this->rows[$index]]);
        }
    }

    public function addMultipleRows(?int $count = null): void
    {
        $count = max(1, min(100, $count ?? $this->newRowCount));
        for ($i = 0; $i < $count; $i++) {
            $this->addRow();
        }
    }

    public function saveData(): void
    {
        if ($this->tooLarge) {
            return;
        }
        $this->isSaving = true;

        $this->validate([
            'rows' => 'required|array|min:1',
        ]);

        $submission = FormSubmission::findOrFail($this->submissionId);
        abort_unless(\App\Http\Controllers\Form\FormExcelController::canEditData(auth()->user(), $submission), 403);

        // التحقق من الحقول الإلزامية قبل الحفظ
        $missing = [];
        foreach ($this->rows as $i => $row) {
            if (! array_filter($row, fn ($v) => $v !== '' && $v !== null)) continue;
            foreach ($this->fields as $f) {
                if (! empty($f['is_required']) && (($row[$f['id']] ?? '') === '' || ($row[$f['id']] ?? null) === null)) {
                    $missing[] = 'الصف ' . ($i + 1) . ': ' . ($f['label'] ?? $f['name']);
                }
            }
        }
        if ($missing) {
            $this->isSaving = false;
            $this->addError('rows', 'حقول إلزامية فارغة — ' . implode('، ', array_slice($missing, 0, 8)) . (count($missing) > 8 ? '…' : ''));
            return;
        }

        $visibleIds = collect($this->fields)->pluck('id')->all();
        \Illuminate\Support\Facades\DB::transaction(function () use ($visibleIds) {
        FormSubmissionData::where('form_submission_id', $this->submissionId)->whereIn('form_field_id', $visibleIds)->delete();

        foreach ($this->rows as $rowIndex => $rowData) {
            foreach ($rowData as $fieldId => $value) {
                if ($value === '' || $value === null) continue;

                $field = collect($this->fields)->firstWhere('id', (int) $fieldId);
                if (!$field) continue;

                FormSubmissionData::create([
                    'form_submission_id' => $this->submissionId,
                    'form_field_id' => $fieldId,
                    'row_index' => $rowIndex,
                    'value' => $value,
                    'raw_value' => ['original' => $value, 'row' => $rowIndex],
                    'entered_by' => auth()->id(),
                ]);
            }
        }
        });

        if (in_array($submission->status, ['draft', 'pending_assignment', 'assigned'], true)) {
            $submission->update(['status' => 'data_entry']);
        }
        $this->saved = true;

        $this->isSaving = false;
        $this->resetErrorBag();
        $this->dispatch('dataSaved', submissionId: $this->submissionId);
    }

    public function submitForReview(): void
    {
        $this->saved = false;
        if ($this->tooLarge) {
            abort_unless(\App\Http\Controllers\Form\FormExcelController::canEditData(auth()->user(), $this->submission), 403);
            $this->saved = true;
        } else {
            $this->saveData();
        }
        if (! $this->saved) return;
        $this->submission->refresh();

        $previousStatus = $this->submission->status;

        $this->submission->update([
            'status' => 'under_review',
            'submitted_at' => now(),
        ]);

        WorkflowLog::create([
            'form_submission_id' => $this->submissionId,
            'user_id' => auth()->id(),
            'action' => 'submit_for_review',
            'from_status' => $previousStatus,
            'to_status' => 'under_review',
            'notes' => 'تم التقديم للمراجعة',
            'metadata' => ['submitted_by' => auth()->user()->name],
        ]);

        $this->dispatch('submittedForReview', url: route('submissions.show', $this->submissionId));
    }

    public function render()
    {
        return view('livewire.form-submission-data-entry');
    }
}
