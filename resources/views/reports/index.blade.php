@extends('layouts.app')

@section('title', 'التقارير')
@section('page-title', 'التقارير')
@section('page-subtitle', 'التقارير المبنية على البيانات المعتمدة — إنشاء ومراجعة واعتماد وإرسال')
@section('breadcrumb')
    <li class="breadcrumb-item active">التقارير</li>
@endsection

@section('page-actions')
    <a href="{{ route('reports.export-excel', request()->query()) }}" class="btn btn-kfs-gold"><i class="fas fa-file-excel ml-1"></i>تصدير Excel</a>
    <a href="{{ route('reports.export-pdf', request()->query()) }}" class="btn btn-kfs-gold"><i class="fas fa-file-pdf ml-1"></i>تصدير PDF</a>
    @can('manage_reports')
        <a href="{{ route('reports.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>تقرير جديد</a>
    @endcan
@endsection

@section('content')
    @php
        $stats = $stats ?? ['total' => $reports->total()];
        $statusOptions = ['draft' => 'مسودة', 'pending_approval' => 'بانتظار الاعتماد', 'approved' => 'معتمد', 'sent' => 'مُرسل', 'rejected' => 'مرفوض'];
    @endphp

    <div class="gov-stats mb-4" data-reveal>
        @foreach ([
            ['fa-chart-column', 'إجمالي التقارير', $stats['total'] ?? 0, null],
            ['fa-file-pen', 'مسودات', $stats['draft'] ?? 0, 'draft'],
            ['fa-hourglass-half', 'بانتظار الاعتماد', $stats['pending_approval'] ?? 0, 'pending_approval'],
            ['fa-circle-check', 'معتمدة', $stats['approved'] ?? 0, 'approved'],
            ['fa-paper-plane', 'مُرسلة', $stats['sent'] ?? 0, 'sent'],
        ] as [$ic, $lb, $val, $st])
            <a class="gs-item" href="{{ route('reports.index', $st ? ['status' => $st] : []) }}" style="color:inherit">
                <div class="gs-icon"><i class="fas {{ $ic }}"></i></div>
                <div class="gs-num" data-count="{{ (int) $val }}">{{ number_format($val) }}</div>
                <div class="gs-label">{{ $lb }}</div>
            </a>
        @endforeach
    </div>

    <div class="card-kfs mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-filter ml-2"></i>البحث والتصفية</span>
            @if (request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-xmark ml-1"></i>مسح التصفية</a>
            @endif
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="r-search">بحث</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-magnifying-glass"></i></span>
                        <input type="text" id="r-search" name="search" class="form-control" placeholder="عنوان التقرير…" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label" for="r-status">الحالة</label>
                    <select id="r-status" name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        @foreach ($statusOptions as $k => $v)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="r-from">من تاريخ</label>
                    <input type="date" id="r-from" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="r-to">إلى تاريخ</label>
                    <input type="date" id="r-to" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-kfs-primary w-100"><i class="fas fa-magnifying-glass ml-1"></i>بحث</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-kfs">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list ml-2"></i>قائمة التقارير</span>
            <span class="badge badge-gold">{{ number_format($reports->total()) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-kfs mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>عنوان التقرير</th>
                        <th class="d-none d-lg-table-cell">النموذج</th>
                        <th>الحالة</th>
                        <th class="d-none d-md-table-cell">الإصدار</th>
                        <th class="d-none d-md-table-cell">أنشأه</th>
                        <th class="d-none d-sm-table-cell">التاريخ</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td class="text-muted en">{{ $report->id }}</td>
                            <td><a href="{{ route('reports.show', $report) }}" class="fw-bold">{{ $report->title }}</a></td>
                            <td class="d-none d-lg-table-cell small text-muted">{{ $report->formSubmission->formTemplate->name ?? '—' }}</td>
                            <td>@include('dashboard.partials._status', ['status' => $report->status])</td>
                            <td class="d-none d-md-table-cell"><span class="badge badge-green en">v{{ $report->current_version ?? 1 }}</span></td>
                            <td class="d-none d-md-table-cell small">{{ $report->createdBy->name ?? '—' }}</td>
                            <td class="d-none d-sm-table-cell small text-muted">{{ $report->created_at->format('Y/m/d') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="عرض" aria-label="عرض"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('reports.pdf', $report) }}" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon" title="طباعة" aria-label="طباعة"><i class="fas fa-print"></i></a>
                                    <a href="{{ route('reports.versions.index', $report) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="الإصدارات" aria-label="الإصدارات"><i class="fas fa-code-branch"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="eg-icon lg"><i class="fas fa-chart-column"></i></span>
                                    <h5>لا توجد تقارير</h5>
                                    <p class="mb-0">لا توجد تقارير مطابقة ضمن نطاق صلاحياتك.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())
            <div class="card-footer">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection
