<?php

namespace App\Livewire;

use App\Models\FormSubmission;
use App\Models\WorkflowLog;
use Livewire\Component;

class RevisionModal extends Component
{
    public ?int $submissionId = null;
    public string $notes = '';
    public bool $showModal = false;

    protected array $rules = [
        'submissionId' => 'required|integer|exists:form_submissions,id',
        'notes' => 'required|string|min:3|max:2000',
    ];

    protected array $validationAttributes = [
        'notes' => 'ملاحظات الإعادة',
    ];

    public function mount(?int $submissionId = null): void
    {
        $this->submissionId = $submissionId;
    }

    public function openModal(?int $submissionId = null): void
    {
        if ($submissionId) {
            $this->submissionId = $submissionId;
        }
        $this->notes = '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->notes = '';
        $this->resetValidation();
    }

    public function sendBack(): void
    {
        $this->validate();

        $submission = FormSubmission::findOrFail($this->submissionId);

        $previousStatus = $submission->status;

        $revisionCount = ($submission->revision_count ?? 0) + 1;

        $submission->update([
            'status' => 'revision_required',
            'revision_notes' => $this->notes,
            'revision_count' => $revisionCount,
        ]);

        WorkflowLog::create([
            'form_submission_id' => $this->submissionId,
            'user_id' => auth()->id(),
            'action' => 'send_back',
            'from_status' => $previousStatus,
            'to_status' => 'revision_required',
            'notes' => $this->notes,
            'metadata' => [
                'revision_number' => $revisionCount,
                'sent_back_by' => auth()->user()->name,
            ],
        ]);

        $this->closeModal();

        $this->dispatch('revisionSent', submissionId: $this->submissionId);
    }

    public function render()
    {
        return view('livewire.revision-modal');
    }
}
