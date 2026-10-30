@extends('layouts.app')

@section('title', 'الإرسالات والبيانات')
@section('page-title', 'الإرسالات والبيانات')
@section('page-subtitle', 'البيانات المرسلة من الجهات ضمن نطاق صلاحياتك')
@section('breadcrumb')
    <li class="breadcrumb-item active">الإرسالات</li>
@endsection

@section('page-actions')
    @can('import_export_excel')
        <a href="{{ route('excel.center') }}" class="btn btn-kfs-gold"><i class="fas fa-file-excel ml-1"></i>مركز Excel</a>
    @endcan
    @can('create_submissions')
        <a href="{{ route('submissions.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>إرسال جديد</a>
    @endcan
@endsection

@section('content')
    @php
        $tabs = [
            'all' => ['الكل', 'fa-layer-group'],
            'mine' => ['إرسالاتي', 'fa-user'],
            'assigned' => ['مطلوب مني', 'fa-keyboard'],
            'in_review' => ['قيد المراجعة', 'fa-hourglass-half'],
            'revision' => ['مطلوب تعديل', 'fa-rotate-left'],
            'approved' => ['معتمدة', 'fa-circle-check'],
            'rejected' => ['مرفوضة', 'fa-circle-xmark'],
        ];
    @endphp

    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist" style="white-space:nowrap">
        @foreach ($tabs as $key => [$label, $icon])
            <li class="nav-item">
                <a class="nav-link {{ $currentTab === $key ? 'active' : '' }}" href="{{ route('submissions.index', array_merge(request()->except('page', 'tab'), ['tab' => $key])) }}"
                   @if ($currentTab === $key) aria-current="page" @endif>
                    <i class="fas {{ $icon }} ml-1 text-gold"></i>{{ $label }}
                    <span class="badge badge-green ml-1">{{ number_format($counts[$key] ?? 0) }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <form method="GET" class="card-kfs p-3 mb-4">
        <input type="hidden" name="tab" value="{{ $currentTab }}">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="search">بحث</label>
                <input type="search" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="الرقم المرجعي أو اسم النموذج">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="form_template_id">النموذج</label>
                <select class="form-select" id="form_template_id" name="form_template_id">
                    <option value="">كل النماذج</option>
                    @foreach ($templates as $id => $name)
                        <option value="{{ $id }}" @selected(request('form_template_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label" for="org_unit_id">الجهة</label>
                <select class="form-select" id="org_unit_id" name="org_unit_id">
                    <option value="">كل الجهات</option>
                    @foreach ($units as $id => $name)
                        <option value="{{ $id }}" @selected(request('org_unit_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-1 col-md-3 col-6">
                <label class="form-label" for="from">من</label>
                <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-lg-1 col-md-3 col-6">
                <label class="form-label" for="to">إلى</label>
                <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-lg-2 col-md-2 d-flex gap-2">
                <button class="btn btn-kfs flex-grow-1"><i class="fas fa-filter ml-1"></i>تصفية</button>
                @if (request()->hasAny(['search', 'form_template_id', 'org_unit_id', 'from', 'to']))
                    <a href="{{ route('submissions.index', ['tab' => $currentTab]) }}" class="btn btn-outline-secondary" title="مسح" aria-label="مسح التصفية"><i class="fas fa-xmark"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="card-kfs">
        <div class="table-responsive">
            <table class="table table-kfs mb-0">
                <thead>
                    <tr>
                        <th>المرجع</th>
                        <th>النموذج</th>
                        <th>الجهة</th>
                        <th>الحالة</th>
                        <th>المسؤول</th>
                        <th>التاريخ</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $s)
                        @php $editable = \App\Http\Controllers\Form\FormExcelController::canEditData(auth()->user(), $s); @endphp
                        <tr>
                            <td><a href="{{ route('submissions.show', $s) }}" class="fw-bold en">{{ $s->reference_number }}</a></td>
                            <td>{{ \Illuminate\Support\Str::limit($s->formTemplate->name ?? '—', 50) }}</td>
                            <td class="small">{{ $s->orgUnit->name ?? '—' }}</td>
                            <td>@include('partials._status-badge', ['status' => $s->status])</td>
                            <td class="small">{{ $s->assignedTo->name ?? $s->createdBy->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $s->created_at->format('Y-m-d') }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('submissions.show', $s) }}" class="btn btn-sm btn-outline-navy btn-icon" title="عرض" aria-label="عرض"><i class="fas fa-eye"></i></a>
                                @if ($editable && in_array($s->status, \App\Services\FormExcelService::EDITABLE_STATUSES))
                                    <a href="{{ route('submissions.data-entry', $s) }}" class="btn btn-sm btn-kfs-gold btn-icon" title="إدخال البيانات" aria-label="إدخال البيانات"><i class="fas fa-keyboard"></i></a>
                                @endif
                                @can('import_export_excel')
                                    <a href="{{ route('excel.submission.export', $s) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="تصدير Excel" aria-label="تصدير Excel"><i class="fas fa-file-export"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="eg-icon lg"><i class="fas fa-inbox"></i></span>
                                    <h5>لا توجد إرسالات</h5>
                                    <p class="mb-0">لا توجد بيانات مطابقة لهذا التبويب أو التصفية.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($submissions->hasPages())
            <div class="card-footer">{{ $submissions->links() }}</div>
        @endif
    </div>
@endsection
