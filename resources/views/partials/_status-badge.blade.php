@php
    $statusMap = [
        'draft' => ['مسودة', 'badge-draft'],
        'pending' => ['قيد الانتظار', 'status-pending'],
        'pending_assignment' => ['بانتظار الإسناد', 'status-pending'],
        'assigned' => ['مُسند', 'status-pending'],
        'data_entry' => ['جاري الإدخال', 'status-pending'],
        'in_review' => ['قيد المراجعة', 'status-info'],
        'under_review' => ['قيد المراجعة', 'status-info'],
        'revision_required' => ['مطلوب تعديل', 'status-error'],
        'sent_back' => ['مرتجع', 'status-pending'],
        'approved' => ['معتمد', 'status-success'],
        'rejected' => ['مرفوض', 'status-error'],
        'report_pending' => ['بانتظار التقرير', 'status-info'],
        'report_draft' => ['تقرير مسودة', 'status-info'],
        'report_approved' => ['تقرير معتمد', 'status-success'],
        'report_sent' => ['تقرير مُرسل', 'status-success'],
        'pending_approval' => ['بانتظار الاعتماد', 'status-pending'],
        'sent' => ['مُرسل', 'status-success'],
        'active' => ['مفعّل', 'status-success'],
        'inactive' => ['غير مفعّل', 'badge-draft'],
        'completed' => ['مكتمل', 'status-success'],
        'in_progress' => ['قيد التنفيذ', 'status-pending'],
    ];
    [$label, $cls] = $statusMap[$status ?? ''] ?? [$status ?? '—', 'badge-draft'];
@endphp
<span class="badge-status {{ $cls }}">{{ $label }}</span>
