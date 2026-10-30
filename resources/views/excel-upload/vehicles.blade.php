@extends('layouts.app')

@section('title', 'المركبات')
@section('page-title', 'بيانات المركبات')
@section('page-subtitle', 'سجل مركبات ومعدات المحافظة وحالتها الفنية')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('excel-upload.index') }}">قواعد البيانات القطاعية</a></li>
    <li class="breadcrumb-item active">المركبات</li>
@endsection

@section('content')
<div class="card-kfs">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-car"></i>
            <span>جميع المركبات</span>
            <span class="badge badge-gold">{{ $vehicles->total() }}</span>
        </div>
        <a href="{{ route('excel-upload.index') }}" class="btn btn-sm btn-kfs-gold">
            <i class="fas fa-file-arrow-up ml-1"></i>استيراد جديد
        </a>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="بحث باللوحة أو النوع أو المكان..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">جميع الحالات</option>
                    @foreach(['مجمع', 'صالح', 'عاطل', 'جسيم', 'مستغني'] as $s)
                        <option {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="sheet" class="form-select">
                    <option value="">جميع الأوراق</option>
                    @foreach($sheets as $s)
                        <option {{ request('sheet') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-kfs-primary w-100"><i class="fas fa-search ml-1"></i>بحث</button>
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-kfs">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>رقم اللوحة</th>
                        <th>نوع المركبة</th>
                        <th>الموديل</th>
                        <th>الحالة</th>
                        <th>المكان</th>
                        <th>الجهة التابعة</th>
                        <th>الحالة الفنية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $v)
                        <tr>
                            <td>{{ $v->serial_number }}</td>
                            <td class="fw-semibold">{{ $v->license_plate ?? '—' }}</td>
                            <td>{{ $v->vehicle_type ?? $v->vehicle_type_per_license ?? '—' }}</td>
                            <td>{{ $v->model_year ?? '—' }}</td>
                            <td>
                                @php
                                    $statusClass = match($v->status) {
                                        'صالح' => 'badge-approved',
                                        'عاطل' => 'badge-rejected',
                                        'جسيم' => 'badge-pending',
                                        'مستغني' => 'badge-draft',
                                        default => 'badge-draft',
                                    };
                                @endphp
                                <span class="badge-status {{ $statusClass }}">{{ $v->status ?? '—' }}</span>
                            </td>
                            <td>{{ $v->location ?? '—' }}</td>
                            <td>{{ $v->affiliated_entity ?? '—' }}</td>
                            <td>{{ $v->technical_condition ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-inbox"></i></span><p class="small mb-0">لا توجد بيانات مركبات</p></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $vehicles->withQueryString()->links() }}
    </div>
</div>
@endsection
