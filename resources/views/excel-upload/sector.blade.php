@extends('layouts.app')

@section('title', 'قطاع ' . $label)
@section('page-title', 'بيانات قطاع ' . $label)
@section('page-subtitle', 'السجلات المستوردة من قاعدة بيانات القطاع')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('excel-upload.index') }}">قواعد البيانات القطاعية</a></li>
    <li class="breadcrumb-item active">{{ $label }}</li>
@endsection
@section('page-actions')
    <a href="{{ route('excel-upload.index') }}#uploader" class="btn btn-kfs-gold"><i class="fas fa-file-arrow-up ml-1"></i>استيراد جديد</a>
@endsection

@section('content')
    <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
        @foreach (\App\Services\SectorDataImportService::SECTORS as $other)
            <li class="nav-item"><a class="nav-link {{ $other === $sector ? 'active' : '' }}" href="{{ route('excel-upload.sector', $other) }}">{{ \App\Services\SectorDataImportService::SECTOR_LABELS[$other] }}</a></li>
        @endforeach
    </ul>

    @include('excel-upload._records', ['data' => $data, 'centers' => $centers, 'sheets' => $sheets, 'typeCol' => 'الورقة / التصنيف', 'typeOf' => fn ($r) => $r->subcategory ?? $r->sheet_name])
@endsection
