@extends('layouts.app')

@section('title', 'النماذج')
@section('page-title', 'النماذج')
@section('page-subtitle', 'نماذج جمع البيانات وتداولها بين جهات المحافظة')
@section('breadcrumb')
    <li class="breadcrumb-item active">النماذج</li>
@endsection

@section('page-actions')
    @can('import_export_excel')
        <a href="{{ route('excel.center') }}" class="btn btn-kfs-gold"><i class="fas fa-file-excel ml-1"></i>مركز Excel</a>
    @endcan
    <a href="{{ route('forms.templates.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>نموذج جديد</a>
@endsection

@push('styles')
<style>
    .tpl-grid .service-card h5 { line-height: 1.5; }
    .tpl-grid .tpl-meta { display: flex; flex-wrap: wrap; gap: .4rem .9rem; font-size: .76rem; color: var(--text-soft); }
    .tpl-grid .tpl-meta i { color: var(--egypt-gold); margin-left: .25rem; }
    .tpl-grid .tpl-foot { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line); padding-top: .75rem; margin-top: .25rem; }
    .view-toggle .btn.active { background: var(--egypt-green); color: var(--white-soft); border-color: var(--egypt-green); }
</style>
@endpush

@section('content')
    <div class="gov-stats mb-4" data-reveal>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-file-lines"></i></div><div class="gs-num" data-count="{{ $stats['total'] }}">{{ $stats['total'] }}</div><div class="gs-label">إجمالي النماذج</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-circle-check"></i></div><div class="gs-num" data-count="{{ $stats['active'] }}">{{ $stats['active'] }}</div><div class="gs-label">نماذج مفعّلة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-share-nodes"></i></div><div class="gs-num" data-count="{{ $stats['targeted'] }}">{{ $stats['targeted'] }}</div><div class="gs-label">موجهة لجهات محددة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-inbox"></i></div><div class="gs-num" data-count="{{ $stats['submissions'] }}">{{ $stats['submissions'] }}</div><div class="gs-label">إرسالات ضمن نطاقك</div></div>
    </div>

    <form method="GET" class="card-kfs p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label" for="search">بحث</label>
                <input type="search" id="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="اسم النموذج أو وصفه">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="org_unit_id">الجهة المالكة</label>
                <select id="org_unit_id" name="org_unit_id" class="form-select">
                    <option value="">كل الجهات</option>
                    @foreach ($units as $id => $name)
                        <option value="{{ $id }}" @selected(request('org_unit_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label" for="distribution">التداول</label>
                <select id="distribution" name="distribution" class="form-select">
                    <option value="">الكل</option>
                    @foreach ($distributions as $k => $v)
                        <option value="{{ $k }}" @selected(request('distribution') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-1 col-md-3">
                <label class="form-label" for="is_active">الحالة</label>
                <select id="is_active" name="is_active" class="form-select">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('is_active') === '1')>مفعّل</option>
                    <option value="0" @selected(request('is_active') === '0')>موقوف</option>
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <input type="hidden" name="view" value="{{ $view }}">
                <button class="btn btn-kfs flex-grow-1"><i class="fas fa-filter ml-1"></i>تصفية</button>
                <div class="btn-group view-toggle" role="group" aria-label="طريقة العرض">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="btn btn-outline-secondary {{ $view === 'grid' ? 'active' : '' }}" title="بطاقات" aria-label="عرض بطاقات"><i class="fas fa-grip"></i></a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="btn btn-outline-secondary {{ $view === 'table' ? 'active' : '' }}" title="جدول" aria-label="عرض جدول"><i class="fas fa-list"></i></a>
                </div>
            </div>
        </div>
    </form>

    @if ($templates->isEmpty())
        <div class="empty-state card-kfs">
            <span class="eg-icon lg"><i class="fas fa-file-lines"></i></span>
            <h5>لا توجد نماذج</h5>
            <p>ابدأ بإنشاء أول نموذج لجمع البيانات من الجهات.</p>
            <a href="{{ route('forms.templates.create') }}" class="btn btn-kfs"><i class="fas fa-plus ml-1"></i>إنشاء نموذج</a>
        </div>
    @elseif ($view === 'grid')
        <div class="row g-3 tpl-grid">
            @foreach ($templates as $t)
                <div class="col-xl-4 col-md-6">
                    <div class="service-card">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <span class="eg-icon"><i class="fas fa-file-lines"></i></span>
                            <div class="d-flex gap-1 flex-wrap justify-content-end">
                                @include('partials._status-badge', ['status' => $t->is_active ? 'active' : 'inactive'])
                                @if ($t->distribution === 'targeted')
                                    <span class="badge badge-gold">موجه · {{ $t->target_units_count }}</span>
                                @elseif ($t->distribution === 'owner')
                                    <span class="badge badge-green">داخلي</span>
                                @endif
                            </div>
                        </div>
                        <h5><a href="{{ route('forms.templates.show', $t) }}" class="text-green">{{ $t->name }}</a></h5>
                        <p>{{ \Illuminate\Support\Str::limit($t->description ?: 'بدون وصف', 100) }}</p>
                        <div class="tpl-meta">
                            <span><i class="fas fa-table-columns"></i>{{ $t->form_fields_count }} حقل</span>
                            <span><i class="fas fa-inbox"></i>{{ $t->form_submissions_count }} إرسال</span>
                            @if ($t->orgUnit)<span><i class="fas fa-building"></i>{{ $t->orgUnit->name }}</span>@endif
                            @if ($t->geo_lat_field && $t->geo_lng_field)<span><i class="fas fa-location-dot"></i>بيانات مكانية</span>@endif
                        </div>
                        <div class="tpl-foot">
                            <a href="{{ route('forms.templates.show', $t) }}" class="sc-link">عرض التفاصيل <i class="fas fa-arrow-left ml-1"></i></a>
                            <div class="btn-group btn-group-sm">
                                @can('import_export_excel')
                                    <a href="{{ route('excel.template.download', $t) }}" class="btn btn-outline-secondary" title="تحميل قالب Excel" aria-label="تحميل قالب Excel"><i class="fas fa-file-arrow-down"></i></a>
                                @endcan
                                <a href="{{ route('forms.templates.edit', $t) }}" class="btn btn-outline-secondary" title="تعديل" aria-label="تعديل"><i class="fas fa-pen"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card-kfs">
            <div class="table-responsive">
                <table class="table table-kfs mb-0">
                    <thead>
                        <tr>
                            <th>النموذج</th>
                            <th>الجهة المالكة</th>
                            <th>التداول</th>
                            <th>الحقول</th>
                            <th>الإرسالات</th>
                            <th>الحالة</th>
                            <th>آخر تحديث</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $t)
                            <tr>
                                <td><a href="{{ route('forms.templates.show', $t) }}" class="fw-bold text-green">{{ $t->name }}</a></td>
                                <td>{{ $t->orgUnit->name ?? '—' }}</td>
                                <td><small>{{ $distributions[$t->distribution] ?? $t->distribution }}</small></td>
                                <td>{{ $t->form_fields_count }}</td>
                                <td>{{ $t->form_submissions_count }}</td>
                                <td>@include('partials._status-badge', ['status' => $t->is_active ? 'active' : 'inactive'])</td>
                                <td class="text-muted small">{{ $t->updated_at?->diffForHumans() }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('forms.templates.show', $t) }}" class="btn btn-sm btn-outline-navy btn-icon" title="عرض" aria-label="عرض"><i class="fas fa-eye"></i></a>
                                    @can('import_export_excel')
                                        <a href="{{ route('excel.template.download', $t) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="تحميل قالب Excel" aria-label="تحميل قالب Excel"><i class="fas fa-file-arrow-down"></i></a>
                                    @endcan
                                    <a href="{{ route('forms.templates.edit', $t) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="تعديل" aria-label="تعديل"><i class="fas fa-pen"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="حذف" aria-label="حذف"
                                            onclick="confirmDelete(@js(route('forms.templates.destroy', $t)), @js($t->name))"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($templates->hasPages())
        <div class="mt-4">{{ $templates->links() }}</div>
    @endif
@endsection
