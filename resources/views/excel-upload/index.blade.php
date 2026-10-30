@extends('layouts.app')

@section('title', 'قواعد البيانات القطاعية')
@section('page-title', 'قواعد البيانات القطاعية')
@section('page-subtitle', 'استيراد ملفات Excel الرسمية للقطاعات ونوتة المعلومات — تظهر تلقائياً على الخريطة ولوحات القيادة')
@section('breadcrumb')
    <li class="breadcrumb-item active">قواعد البيانات القطاعية</li>
@endsection
@section('page-actions')
    <a href="#uploader" class="btn btn-kfs-primary"><i class="fas fa-file-arrow-up ml-1"></i>رفع ملف</a>
    @can('view_map')
        <a href="{{ route('map.index') }}" class="btn btn-kfs-gold"><i class="fas fa-map-location-dot ml-1"></i>عرض على الخريطة</a>
    @endcan
@endsection

@push('styles')
<style>
    .ds-tile { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; height:100%;
        background:var(--white-soft); border:1px solid var(--line); border-radius:8px; color:var(--charcoal);
        transition: border-color .4s var(--ease), box-shadow .4s var(--ease); }
    .ds-tile:hover { border-color:var(--line-gold); box-shadow:var(--shadow); color:var(--charcoal); }
    .ds-tile .n { font-size:1.2rem; font-weight:800; color:var(--egypt-green); line-height:1.1; font-variant-numeric:lining-nums; }
    .ds-tile .l { font-size:.76rem; color:var(--text-soft); }
    .ds-tile.is-empty .n { color:var(--muted); }
</style>
@endpush

@section('content')
    @include('dashboard.partials._stats', ['items' => [
        ['fa-layer-group', 'إجمالي السجلات', $stats['total']],
        ['fa-car-side', 'سجل المركبات', $stats['vehicles'], route('excel-upload.vehicles')],
        ['fa-screwdriver-wrench', 'سجل الصيانة', $stats['maintenance'], route('excel-upload.maintenance')],
        ['fa-landmark', 'البيان الاستراتيجي', $stats['strategic'], route('excel-upload.strategic')],
        ['fa-database', 'بيانات القطاعات', $stats['sectors']],
        ['fa-location-dot', 'نوتة المعلومات', $stats['facilities']],
    ]])

    <div class="section-head">
        <div class="sh-line"><span class="sh-num">01</span></div>
        <h3>بيانات القطاعات</h3>
        <p>قواعد بيانات الاستثمار والسكان والتعليم والتموين والصحة</p>
    </div>
    <div class="row g-3 mb-4">
        @foreach ([
            ['investment', 'الاستثمار', 'fa-industry'],
            ['population', 'السكان', 'fa-users'],
            ['education', 'التعليم', 'fa-graduation-cap'],
            ['supply', 'التموين', 'fa-store'],
            ['health', 'الصحة', 'fa-hospital'],
        ] as [$key, $name, $icon])
            <div class="col-xl col-md-4 col-6">
                <a href="{{ route('excel-upload.sector', $key) }}" class="ds-tile {{ ($stats['sectorCounts'][$key] ?? 0) ? '' : 'is-empty' }}">
                    <span class="eg-icon"><i class="fas {{ $icon }}"></i></span>
                    <span><span class="n d-block">{{ number_format($stats['sectorCounts'][$key] ?? 0) }}</span><span class="l">{{ $name }}</span></span>
                </a>
            </div>
        @endforeach
    </div>

    <div class="section-head">
        <div class="sh-line"><span class="sh-num">02</span></div>
        <h3>نوتة المعلومات</h3>
        <p>المنشآت والمواقع الحيوية بالمحافظة — مصدر طبقات الخريطة التفاعلية</p>
    </div>
    <div class="row g-3 mb-4">
        @foreach (\App\Services\FacilityDataImportService::MODULES as $fcKey)
            <div class="col-xl-2 col-md-3 col-6">
                <a href="{{ route('excel-upload.facilities', $fcKey) }}" class="ds-tile {{ ($stats['facilityCounts'][$fcKey] ?? 0) ? '' : 'is-empty' }}">
                    <span class="eg-icon sm"><i class="fas fa-location-dot"></i></span>
                    <span class="overflow-hidden"><span class="n d-block">{{ number_format($stats['facilityCounts'][$fcKey] ?? 0) }}</span>
                        <span class="l d-block text-truncate">{{ \App\Services\FacilityDataImportService::MODULE_LABELS[$fcKey] ?? $fcKey }}</span></span>
                </a>
            </div>
        @endforeach
    </div>

    <div class="section-head" id="uploader">
        <div class="sh-line"><span class="sh-num">03</span></div>
        <h3>رفع ملف جديد</h3>
        <p>اختر نوع البيانات ثم ارفع ملف Excel بالتنسيق الرسمي</p>
    </div>
    <livewire:excel-uploader />
@endsection
