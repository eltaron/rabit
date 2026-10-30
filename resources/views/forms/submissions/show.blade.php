@extends('layouts.app')

@section('title', 'الإرسال ' . $submission->reference_number)
@section('page-title', $submission->formTemplate->name ?? 'تفاصيل الإرسال')
@section('page-subtitle')
    <span class="en">{{ $submission->reference_number }}</span> · {{ $submission->orgUnit->name ?? 'بدون جهة' }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('submissions.index') }}">الإرسالات</a></li>
    <li class="breadcrumb-item active en">{{ $submission->reference_number }}</li>
@endsection

@section('page-actions')
    @if ($canEdit)
        <a href="{{ route('submissions.data-entry', $submission) }}" class="btn btn-kfs-primary"><i class="fas fa-keyboard ml-1"></i>إدخال / تعديل البيانات</a>
    @endif
    @can('import_export_excel')
        <a href="{{ route('excel.submission.export', $submission) }}" class="btn btn-kfs-gold"><i class="fas fa-file-export ml-1"></i>تصدير Excel</a>
        @if ($canEdit)
            <button type="button" class="btn btn-kfs-gold" data-bs-toggle="modal" data-bs-target="#uploadExisting"><i class="fas fa-file-import ml-1"></i>رفع Excel</button>
        @endif
    @endcan
@endsection

@php
    $canReview = auth()->user()->can('review_submissions');
    $canApprove = auth()->user()->can('approve_submissions');
    $canAssign = $canReview || $canApprove;
    $closed = in_array($submission->status, ['approved', 'rejected', 'report_pending', 'report_draft', 'report_approved', 'report_sent']);
@endphp

@section('content')
    @include('excel._import-errors')

    @if ($submission->status === 'revision_required' && $submission->revision_notes)
        <div class="alert alert-warning mb-4">
            <div class="fw-bold mb-1"><i class="fas fa-rotate-left ml-1"></i>مطلوب تعديل البيانات</div>
            {{ $submission->revision_notes }}
        </div>
    @elseif ($submission->status === 'rejected' && $submission->revision_notes)
        <div class="alert alert-danger mb-4">
            <div class="fw-bold mb-1"><i class="fas fa-circle-xmark ml-1"></i>سبب الرفض</div>
            {{ $submission->revision_notes }}
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card-kfs h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-lines ml-2"></i>بيانات الإرسال</span>
                    @include('partials._status-badge', ['status' => $submission->status])
                </div>
                <div class="card-body">
                    <dl class="row g-3 mb-0 small">
                        <div class="col-md-4"><dt class="text-muted fw-normal">الرقم المرجعي</dt><dd class="fw-bold en mb-0">{{ $submission->reference_number }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">النموذج</dt><dd class="fw-bold mb-0">
                            @can('manage_form_templates')<a href="{{ route('forms.templates.show', $submission->form_template_id) }}">{{ $submission->formTemplate->name ?? '—' }}</a>@else{{ $submission->formTemplate->name ?? '—' }}@endcan
                        </dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">الجهة</dt><dd class="mb-0">{{ $submission->orgUnit->name ?? '—' }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">أنشأه</dt><dd class="mb-0">{{ $submission->createdBy->name ?? '—' }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">المسؤول عن الإدخال</dt><dd class="mb-0">{{ $submission->assignedTo->name ?? '—' }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">عدد الصفوف</dt><dd class="fw-bold mb-0">{{ number_format($totalRows) }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">تاريخ الإنشاء</dt><dd class="mb-0">{{ $submission->created_at->format('Y-m-d H:i') }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">تاريخ التقديم</dt><dd class="mb-0">{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                        <div class="col-md-4"><dt class="text-muted fw-normal">الاعتماد</dt><dd class="mb-0">{{ $submission->approvedBy->name ?? '—' }} {{ $submission->approved_at?->format('Y-m-d') }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-kfs h-100">
                <div class="card-header"><i class="fas fa-gavel ml-2"></i>الإجراءات</div>
                <div class="card-body d-grid gap-2 align-content-start">
                    @if ($canEdit && in_array($submission->status, \App\Services\FormExcelService::EDITABLE_STATUSES))
                        <a href="{{ route('submissions.data-entry', $submission) }}" class="btn btn-kfs"><i class="fas fa-keyboard ml-1"></i>إدخال البيانات</a>
                    @endif

                    @if ($canAssign && ! $closed)
                        <form method="POST" action="{{ route('submissions.assign', $submission) }}">
                            @csrf
                            <label class="form-label small" for="assigned_to">إسناد إلى</label>
                            <div class="input-group">
                                <select name="assigned_to" id="assigned_to" class="form-select form-select-sm" required>
                                    <option value="">— اختر —</option>
                                    @foreach ($reviewers as $r)
                                        <option value="{{ $r->id }}" @selected($submission->assigned_to === $r->id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-navy"><i class="fas fa-user-check ml-1"></i>إسناد</button>
                            </div>
                        </form>
                    @endif

                    @if ($submission->status === 'under_review')
                        @if ($canApprove)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"><i class="fas fa-circle-check ml-1"></i>اعتماد</button>
                            <form method="POST" action="{{ route('submissions.approve-report', $submission) }}">
                                @csrf
                                <button class="btn btn-outline-success w-100"><i class="fas fa-file-circle-check ml-1"></i>اعتماد وإنشاء تقرير</button>
                            </form>
                        @endif
                        @if ($canAssign)
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#revisionModal"><i class="fas fa-rotate-left ml-1"></i>إعادة للتعديل</button>
                        @endif
                        @if ($canApprove)
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="fas fa-circle-xmark ml-1"></i>رفض</button>
                        @endif
                    @endif

                    @can('manage_reports')
                        <a href="{{ route('reports.create', ['submission_id' => $submission->id]) }}" class="btn btn-outline-navy"><i class="fas fa-file-medical ml-1"></i>إنشاء تقرير</a>
                    @endcan
                    <a href="{{ route('submissions.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-right ml-1"></i>العودة للقائمة</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card-kfs mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-table ml-2"></i>البيانات</span>
            @can('import_export_excel')
                @if ($totalRows)
                    <a href="{{ route('excel.submission.export', $submission) }}" class="btn btn-sm btn-outline-navy"><i class="fas fa-file-export ml-1"></i>تصدير كل الصفوف</a>
                @endif
            @endcan
        </div>
        <div class="card-body">
            @include('forms.submissions._form-data-table')
        </div>
    </div>

    <div class="card-kfs">
        <div class="card-header"><i class="fas fa-timeline ml-2"></i>مسار العمل</div>
        <div class="card-body">
            @include('partials._workflow-timeline', ['currentStatus' => $submission->status])
            @livewire('workflow-timeline', ['submissionId' => $submission->id])
        </div>
    </div>

    {{-- نوافذ الإجراءات --}}
    @if ($canApprove)
        <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('submissions.approve', $submission) }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title" id="approveModalLabel">اعتماد البيانات</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                    <div class="modal-body">
                        <label class="form-label" for="approveNotes">ملاحظات (اختياري)</label>
                        <textarea class="form-control" id="approveNotes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-success">اعتماد</button></div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('submissions.reject', $submission) }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title" id="rejectModalLabel">رفض الإرسال</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                    <div class="modal-body">
                        <label class="form-label" for="rejectReason">سبب الرفض <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" name="reason" rows="3" required maxlength="1000"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-danger">رفض</button></div>
                </form>
            </div>
        </div>
    @endif
    @if ($canAssign)
        <div class="modal fade" id="revisionModal" tabindex="-1" aria-labelledby="revisionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('submissions.revision', $submission) }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title" id="revisionModalLabel">إعادة للتعديل</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                    <div class="modal-body">
                        <label class="form-label" for="revisionNotes">ما المطلوب تعديله؟ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="revisionNotes" name="revision_notes" rows="3" required maxlength="1000"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-warning">إعادة للتعديل</button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($canEdit)
        @can('import_export_excel')
            @include('excel._upload-modal', [
                'id' => 'uploadExisting',
                'action' => route('excel.submission.import', $submission),
                'title' => 'رفع ملف Excel للإرسال ' . $submission->reference_number,
                'subtitle' => 'للتعديل: صدّر البيانات أولاً، عدّل الملف، ثم ارفعه بوضع «تحديث». الصفوف الجديدة بلا رقم صف تُضاف.',
                'withMode' => true,
                'modes' => $modes,
            ])
        @endcan
    @endif
@endsection
