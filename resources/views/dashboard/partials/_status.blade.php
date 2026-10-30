@php
    $map = [
        'draft' => ['مسودة', 'badge-draft'],
        'pending_assignment' => ['بانتظار التكليف', 'badge-pending'],
        'assigned' => ['مُكلّف', 'badge-assigned'],
        'data_entry' => ['قيد الإدخال', 'badge-data_entry'],
        'under_review' => ['قيد المراجعة', 'badge-under_review'],
        'revision_required' => ['مطلوب تعديل', 'badge-revision_required'],
        'approved' => ['معتمد', 'badge-approved'],
        'rejected' => ['مرفوض', 'badge-rejected'],
        'report_pending' => ['بانتظار التقرير', 'badge-report_pending'],
        'report_draft' => ['تقرير مسودة', 'badge-report_draft'],
        'report_approved' => ['تقرير معتمد', 'badge-report_approved'],
        'report_sent' => ['تقرير مرسل', 'badge-report_sent'],
        'pending_approval' => ['بانتظار الاعتماد', 'badge-pending_approval'],
        'sent' => ['مُرسل', 'badge-sent'],
    ];
    [$lbl, $cls] = $map[$status] ?? [$status, 'badge-draft'];
@endphp
<span class="badge-status {{ $cls }}">{{ $lbl }}</span>
