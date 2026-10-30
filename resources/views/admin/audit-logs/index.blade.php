@extends('layouts.app')
@use('App\Http\Controllers\Audit\AuditLogController', 'Audit')

@section('title', 'سجل التدقيق')
@section('page-title', 'سجل التدقيق')
@section('page-subtitle', 'كل العمليات الحساسة على المنصة: من نفّذها، ومتى، ومن أين')
@section('breadcrumb')
    <li class="breadcrumb-item active">سجل التدقيق</li>
@endsection

@section('content')
    <div class="gov-stats mb-4">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-clock-rotate-left"></i></div><div class="gs-num">{{ number_format($total) }}</div><div class="gs-label">إجمالي العمليات المسجلة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-calendar-day"></i></div><div class="gs-num">{{ number_format($today) }}</div><div class="gs-label">عمليات اليوم</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-filter"></i></div><div class="gs-num">{{ number_format($logs->total()) }}</div><div class="gs-label">نتائج التصفية الحالية</div></div>
    </div>

    <form method="GET" class="card-kfs mb-4">
        <div class="card-body row g-2 align-items-end">
            <div class="col-lg-3 col-md-6"><label class="form-label" for="a_search">بحث في الوصف / IP</label><input class="form-control" id="a_search" name="search" value="{{ request('search') }}"></div>
            <div class="col-lg-2 col-md-6"><label class="form-label" for="a_user">المستخدم</label>
                <select class="form-select" id="a_user" name="user_id"><option value="">الكل</option>@foreach ($users as $id => $n)<option value="{{ $id }}" @selected((int) request('user_id') === $id)>{{ $n }}</option>@endforeach</select></div>
            <div class="col-lg-2 col-md-4"><label class="form-label" for="a_event">العملية</label>
                <select class="form-select" id="a_event" name="event"><option value="">الكل</option>@foreach ($events as $e)<option value="{{ $e }}" @selected(request('event') === $e)>{{ Audit::eventLabel($e) }}</option>@endforeach</select></div>
            <div class="col-lg-2 col-md-4"><label class="form-label" for="a_type">العنصر</label>
                <select class="form-select" id="a_type" name="auditable_type"><option value="">الكل</option>@foreach ($types as $t)<option value="{{ $t }}" @selected(request('auditable_type') === $t)>{{ Audit::typeLabel($t) }}</option>@endforeach</select></div>
            <div class="col-lg-1 col-md-2"><label class="form-label" for="a_from">من</label><input type="date" class="form-control" id="a_from" name="from_date" value="{{ request('from_date') }}"></div>
            <div class="col-lg-1 col-md-2"><label class="form-label" for="a_to">إلى</label><input type="date" class="form-control" id="a_to" name="to_date" value="{{ request('to_date') }}"></div>
            <div class="col-lg-1 col-md-12 d-flex gap-1"><button class="btn btn-kfs-primary flex-fill" title="تصفية"><i class="fas fa-filter"></i></button><a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary" title="مسح"><i class="fas fa-xmark"></i></a></div>
        </div>
    </form>

    <div class="card-kfs">
        <div class="card-header"><i class="fas fa-list ml-2"></i>العمليات</div>
        @if ($logs->isEmpty())
            <div class="empty-state"><span class="eg-icon lg"><i class="fas fa-clock-rotate-left"></i></span><h5>لا توجد عمليات مطابقة</h5></div>
        @else
            <div class="table-responsive">
                <table class="table table-kfs mb-0">
                    <thead><tr><th>الوقت</th><th>المستخدم</th><th>العملية</th><th>العنصر</th><th>الوصف</th><th>IP</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $cls = match (true) {
                                    in_array($log->event, ['deleted', 'rejected', 'deactivated']) => 'status-error',
                                    in_array($log->event, ['created', 'approved', 'activated', 'imported']) => 'status-success',
                                    in_array($log->event, ['password_reset', 'role_assigned', 'revision_required']) => 'status-pending',
                                    default => 'status-info',
                                };
                            @endphp
                            <tr>
                                <td class="text-nowrap"><small>{{ $log->created_at->format('Y-m-d') }}</small><small class="d-block text-muted en">{{ $log->created_at->format('H:i:s') }}</small></td>
                                <td>{{ $log->user->name ?? 'النظام' }}</td>
                                <td><span class="status-pill {{ $cls }}">{{ Audit::eventLabel($log->event) }}</span></td>
                                <td><small>{{ Audit::typeLabel($log->auditable_type) }}@if ($log->auditable_id) <span class="en text-muted">#{{ $log->auditable_id }}</span>@endif</small></td>
                                <td style="max-width:380px"><small>{{ \Illuminate\Support\Str::limit($log->description, 120) }}</small></td>
                                <td><small class="en text-muted">{{ $log->ip_address }}</small></td>
                                <td><a href="{{ route('admin.audit-logs.show', $log) }}" class="btn btn-sm btn-outline-secondary btn-icon" aria-label="التفاصيل"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())<div class="p-3">{{ $logs->links() }}</div>@endif
        @endif
    </div>
@endsection
