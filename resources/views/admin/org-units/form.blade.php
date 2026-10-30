@extends('layouts.app')

@php $editing = $unit->exists; @endphp
@section('title', $editing ? 'تعديل جهة' : 'جهة جديدة')
@section('page-title', $editing ? 'تعديل: ' . $unit->name : 'إضافة جهة إلى الهيكل التنظيمي')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.org-units.index') }}">الهيكل التنظيمي</a></li>
    @if ($editing)<li class="breadcrumb-item"><a href="{{ route('admin.org-units.show', $unit) }}">{{ $unit->name }}</a></li>@endif
    <li class="breadcrumb-item active">{{ $editing ? 'تعديل' : 'إضافة' }}</li>
@endsection

@section('content')
<form method="POST" action="{{ $editing ? route('admin.org-units.update', $unit) : route('admin.org-units.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-building ml-2"></i>البيانات الأساسية</div>
                <div class="card-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">اسم الجهة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $unit->name) }}" required maxlength="255">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="type">النوع <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            @foreach (\App\Models\OrgUnit::TYPES as $k => $label)
                                <option value="{{ $k }}" @selected(old('type', $unit->type) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (! $editing || $unit->parent_id)
                        <div class="col-md-8">
                            <label class="form-label" for="parent_id">تتبع الجهة <span class="text-danger">*</span></label>
                            <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id" required>
                                @foreach ($parents as $id => $label)
                                    <option value="{{ $id }}" @selected((int) old('parent_id', $unit->parent_id) === (int) $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label" for="code">الكود</label>
                        <input type="text" class="form-control en @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $unit->code) }}" maxlength="50">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="name_en">الاسم بالإنجليزية</label>
                        <input type="text" class="form-control en" id="name_en" name="name_en" value="{{ old('name_en', $unit->name_en) }}" dir="ltr">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="sort_order">الترتيب</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $unit->sort_order ?? 0) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $unit->is_active ?? true))>
                            <label class="form-check-label" for="is_active">جهة نشطة</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">وصف / اختصاصات</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $unit->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-user-tie ml-2"></i>القيادة والتواصل</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label" for="head_name">اسم رئيس الجهة</label><input class="form-control" id="head_name" name="head_name" value="{{ old('head_name', $unit->head_name) }}"></div>
                    <div class="col-md-6"><label class="form-label" for="head_title">الصفة</label><input class="form-control" id="head_title" name="head_title" value="{{ old('head_title', $unit->head_title) }}" placeholder="مثال: رئيس مجلس المدينة"></div>
                    <div class="col-md-4"><label class="form-label" for="phone">الهاتف</label><input class="form-control en" id="phone" name="phone" value="{{ old('phone', $unit->phone) }}" dir="ltr"></div>
                    <div class="col-md-8"><label class="form-label" for="email">البريد الإلكتروني</label><input type="email" class="form-control en" id="email" name="email" value="{{ old('email', $unit->email) }}" dir="ltr"></div>
                    <div class="col-12"><label class="form-label" for="address">العنوان</label><input class="form-control" id="address" name="address" value="{{ old('address', $unit->address) }}"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-location-dot ml-2"></i>البيانات المكانية</div>
                <div class="card-body row g-3">
                    <div class="col-6"><label class="form-label" for="latitude">خط العرض</label><input type="number" step="0.0000001" class="form-control en @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $unit->latitude) }}" placeholder="31.11"></div>
                    <div class="col-6"><label class="form-label" for="longitude">خط الطول</label><input type="number" step="0.0000001" class="form-control en @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $unit->longitude) }}" placeholder="30.94"></div>
                    <div class="col-12"><small class="text-muted">تظهر الجهة على الخريطة التفاعلية عند إدخال الإحداثيات.</small></div>
                    <div class="col-6"><label class="form-label" for="population">عدد السكان</label><input type="number" min="0" class="form-control" id="population" name="population" value="{{ old('population', $unit->population) }}"></div>
                    <div class="col-6"><label class="form-label" for="area_km2">المساحة (كم²)</label><input type="number" step="0.001" min="0" class="form-control" id="area_km2" name="area_km2" value="{{ old('area_km2', $unit->area_km2) }}"></div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-kfs-primary btn-lg"><i class="fas fa-check ml-1"></i>{{ $editing ? 'حفظ التعديلات' : 'إضافة الجهة' }}</button>
                <a href="{{ $editing ? route('admin.org-units.show', $unit) : route('admin.org-units.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </div>
    </div>
</form>
@endsection
