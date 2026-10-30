@extends('layouts.app')

@section('title', 'التقسيم القديم')
@section('page-title', 'القطاعات والمديريات والمراكز (التقسيم السابق)')
@section('page-subtitle', 'تم توحيد كل الجهات في شاشة الهيكل التنظيمي الجديدة')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.org-units.index') }}">الهيكل التنظيمي</a></li>
    <li class="breadcrumb-item active">التقسيم السابق</li>
@endsection
@section('page-actions')
    <a href="{{ route('admin.org-units.index') }}" class="btn btn-kfs-primary"><i class="fas fa-sitemap ml-1"></i>الانتقال إلى الهيكل التنظيمي</a>
@endsection

@section('content')
    <div class="card-kfs egypt-frame mb-4">
        <div class="card-body p-4 d-flex gap-3 align-items-start">
            <span class="eg-icon lg"><i class="fas fa-circle-info"></i></span>
            <div>
                <h5>تم نقل إدارة الجهات إلى الهيكل التنظيمي الموحد</h5>
                <p class="mb-2 text-muted">كل القطاعات والمديريات والمراكز والأقسام السابقة أصبحت جزءاً من شجرة واحدة تضم أيضاً المدن والقرى والعزب والوزارات والهيئات. البيانات القديمة مرتبطة تلقائياً بالجهات الجديدة، ولا يلزم أي إجراء.</p>
                <a href="{{ route('admin.org-units.index') }}" class="btn btn-kfs-gold btn-sm">فتح الهيكل التنظيمي</a>
            </div>
        </div>
    </div>

    @php
        $legacy = [
            'القطاعات' => \App\Models\Sector::withCount('departments')->get()->map(fn ($s) => [$s->name, $s->code, $s->departments_count . ' مديرية', 'legacy_sector_id', $s->id]),
            'المديريات' => \App\Models\Department::with('sector')->get()->map(fn ($d) => [$d->name, $d->code, $d->sector->name ?? '—', 'legacy_department_id', $d->id]),
            'المراكز والجهات' => \App\Models\Center::with('department')->get()->map(fn ($c) => [$c->name, $c->code, $c->department->name ?? '—', 'legacy_center_id', $c->id]),
        ];
        $mapped = \App\Models\OrgUnit::query()->get(['id', 'legacy_sector_id', 'legacy_department_id', 'legacy_center_id']);
    @endphp
    <div class="row g-4">
        @foreach ($legacy as $title => $rows)
            <div class="col-lg-4">
                <div class="card-kfs h-100">
                    <div class="card-header">{{ $title }} <span class="badge badge-gold">{{ $rows->count() }}</span></div>
                    <ul class="list-group list-group-flush">
                        @foreach ($rows as [$name, $code, $meta, $col, $id])
                            @php $unit = $mapped->firstWhere($col, $id); @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $name }}<small class="text-muted d-block">{{ $meta }} · <span class="en">{{ $code }}</span></small></span>
                                @if ($unit)<a href="{{ route('admin.org-units.show', $unit->id) }}" class="small">الجهة الموحدة</a>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
@endsection
