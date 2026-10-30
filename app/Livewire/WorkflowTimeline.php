<?php

namespace App\Livewire;

use App\Models\WorkflowLog;
use Livewire\Component;

class WorkflowTimeline extends Component
{
    public ?int $submissionId = null;
    public array $logs = [];

    public string $statusColorMap = '';

    private const STATUS_COLORS = [
        'draft' => 'secondary',
        'submitted' => 'info',
        'under_review' => 'warning',
        'revision_required' => 'danger',
        'approved' => 'success',
        'rejected' => 'danger',
        'sent' => 'primary',
        'assigned' => 'info',
        'data_entry' => 'warning',
        'pending_assignment' => 'secondary',
    ];

    private const ACTION_ICONS = [
        'submit_for_review' => 'fa-paper-plane',
        'approve' => 'fa-check-circle',
        'reject' => 'fa-times-circle',
        'send_back' => 'fa-undo',
        'send_revision' => 'fa-reply',
        'comment' => 'fa-comment',
        'assign' => 'fa-user-check',
        'update' => 'fa-edit',
        'created' => 'fa-plus',
        'assigned' => 'fa-user-check',
        'submitted' => 'fa-paper-plane',
        'reviewed' => 'fa-magnifying-glass',
        'approved' => 'fa-circle-check',
        'rejected' => 'fa-circle-xmark',
        'revision_required' => 'fa-rotate-left',
        'excel_import' => 'fa-file-excel',
    ];

    public function mount(int $submissionId): void
    {
        $this->submissionId = $submissionId;
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $this->logs = WorkflowLog::with('user')
            ->where('form_submission_id', $this->submissionId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getStatusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? 'secondary';
    }

    public function getActionIcon(string $action): string
    {
        return self::ACTION_ICONS[$action] ?? 'fa-circle';
    }

    public function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'مسودة',
            'submitted' => 'مقدم',
            'under_review' => 'قيد المراجعة',
            'revision_required' => 'يحتاج مراجعة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'sent' => 'تم الإرسال',
            'assigned' => 'مُسند',
            'data_entry' => 'جاري الإدخال',
            'pending_assignment' => 'بانتظار الإسناد',
            'report_pending' => 'بانتظار التقرير',
        ];

        return $labels[$status] ?? $status;
    }

    public function getActionLabel(string $action): string
    {
        $labels = [
            'submit_for_review' => 'تقديم للمراجعة',
            'approve' => 'موافقة',
            'reject' => 'رفض',
            'send_back' => 'إعادة للمراجعة',
            'send_revision' => 'إرسال ملاحظات',
            'comment' => 'تعليق',
            'assign' => 'تعيين',
            'update' => 'تحديث',
            'created' => 'إنشاء الإرسال',
            'assigned' => 'إسناد',
            'submitted' => 'تقديم البيانات',
            'reviewed' => 'مراجعة',
            'approved' => 'اعتماد',
            'rejected' => 'رفض',
            'revision_required' => 'إعادة للتعديل',
            'excel_import' => 'استيراد من Excel',
        ];

        return $labels[$action] ?? $action;
    }

    public function render()
    {
        return view('livewire.workflow-timeline');
    }
}
