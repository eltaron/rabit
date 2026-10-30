@extends('layouts.app')

@section('title', 'خطط الصيانة')
@section('page-title', 'سجل خطة الصيانة الشهرية')
@section('page-subtitle', 'خطط الصيانة الشهرية للمركبات حسب المراكز')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('excel-upload.index') }}">قواعد البيانات القطاعية</a></li>
    <li class="breadcrumb-item active">خطط الصيانة</li>
@endsection

@section('content')
<div class="card-kfs">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-wrench"></i>
            <span>خطط الصيانة الشهرية</span>
            <span class="badge badge-gold">{{ $plans->total() }}</span>
        </div>
        <a href="{{ route('excel-upload.index') }}" class="btn btn-sm btn-kfs-gold">
            <i class="fas fa-file-arrow-up ml-1"></i>استيراد جديد
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="بحث برقم العربة أو النوع..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="center" class="form-select">
                    <option value="">جميع المراكز</option>
                    @foreach($centers as $c)
                        <option {{ request('center') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-kfs-primary w-100"><i class="fas fa-search ml-1"></i>بحث</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-kfs">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>المركز</th>
                        <th>رقم العربة</th>
                        <th>نوعها</th>
                        <th>مجموعة الاستخدام</th>
                        <th>الأسبوع الأول</th>
                        <th>الأسبوع الثاني</th>
                        <th>الأسبوع الثالث</th>
                        <th>الأسبوع الرابع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>{{ $plan->serial_number }}</td>
                            <td class="fw-semibold">{{ $plan->center_name ?? '—' }}</td>
                            <td>{{ $plan->vehicle_number ?? '—' }}</td>
                            <td>{{ $plan->vehicle_type ?? '—' }}</td>
                            <td>{{ $plan->usage_group ?? '—' }}</td>
                            @for($w = 1; $w <= 4; $w++)
                                <td class="text-center">
                                    @if($plan->{"week{$w}_planned"})
                                        <span class="badge-status badge-approved" title="مخطط">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    @endif
                                    @if($plan->{"week{$w}_executed"})
                                        <span class="badge-status badge-pending ml-1" title="منفذ">
                                            <i class="fas fa-check-double"></i>
                                        </span>
                                    @endif
                                    @if(!$plan->{"week{$w}_planned"} && !$plan->{"week{$w}_executed"})
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9"><div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-inbox"></i></span><p class="small mb-0">لا توجد خطط صيانة</p></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $plans->withQueryString()->links() }}
    </div>
</div>
@endsection
