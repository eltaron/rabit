@extends('layouts.app')

@section('title', $label)
@section('page-title', $label)
@section('page-subtitle', 'نوتة المعلومات — سجلات المنشآت والمواقع')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('excel-upload.index') }}">قواعد البيانات القطاعية</a></li>
    <li class="breadcrumb-item active">{{ $label }}</li>
@endsection
@section('page-actions')
    <a href="{{ route('excel-upload.index') }}#uploader" class="btn btn-kfs-gold"><i class="fas fa-file-arrow-up ml-1"></i>استيراد جديد</a>
@endsection

@section('content')
    <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
        <label for="moduleSwitch" class="form-label mb-0">التصنيف:</label>
        <select id="moduleSwitch" class="form-select w-auto" onchange="location.href=this.value">
            @foreach (\App\Services\FacilityDataImportService::MODULES as $other)
                <option value="{{ route('excel-upload.facilities', $other) }}" @selected($other === $module)>{{ \App\Services\FacilityDataImportService::MODULE_LABELS[$other] ?? $other }}</option>
            @endforeach
        </select>
    </div>

    @include('excel-upload._records', ['data' => $data, 'centers' => $centers, 'sheets' => [], 'typeCol' => 'النوع', 'typeOf' => fn ($r) => $r->type])
@endsection
