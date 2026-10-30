@extends('layouts.app')

@section('title', 'البيان الاستراتيجي')
@section('page-title', 'البيان الاستراتيجي')
@section('page-subtitle', 'بيان القيادة الاستراتيجية للمواقع الحيوية')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('excel-upload.index') }}">قواعد البيانات القطاعية</a></li>
    <li class="breadcrumb-item active">البيان الاستراتيجي</li>
@endsection

@section('content')
<div class="card-kfs">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-landmark"></i>
            <span>البيان الاستراتيجي</span>
            <span class="badge badge-gold">{{ $data->total() }}</span>
        </div>
        <a href="{{ route('excel-upload.index') }}" class="btn btn-sm btn-kfs-gold">
            <i class="fas fa-file-arrow-up ml-1"></i>استيراد جديد
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو العنوان أو المركز..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="sheet" class="form-select">
                    <option value="">جميع الفئات</option>
                    @foreach($sheets as $s)
                        <option {{ request('sheet') === $s ? 'selected' : '' }}>{{ $s }}</option>
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
                        <th>الفئة</th>
                        <th>الاسم</th>
                        <th>العنوان</th>
                        <th>المدينة</th>
                        <th>المركز</th>
                        <th>التليفون</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        <tr>
                            <td>{{ $item->serial_number }}</td>
                            <td>
                                <span class="badge badge-green">
                                    {{ $item->sheet_name ?? '—' }}
                                </span>
                            </td>
                            <td class="fw-semibold">{{ $item->name ?? '—' }}</td>
                            <td>{{ $item->address ?? '—' }}</td>
                            <td>{{ $item->city ?? '—' }}</td>
                            <td>{{ $item->center ?? '—' }}</td>
                            <td>{{ $item->phone ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-inbox"></i></span><p class="small mb-0">لا توجد بيانات استراتيجية</p></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $data->withQueryString()->links() }}
    </div>
</div>
@endsection
