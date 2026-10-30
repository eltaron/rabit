@extends('layouts.app')

@section('title', $unit->name)
@section('page-title', $unit->name)
@section('page-subtitle', $unit->type_label . ($unit->code ? ' · ' . $unit->code : ''))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.org-units.index') }}">الهيكل التنظيمي</a></li>
    @foreach ($ancestors as $a)
        <li class="breadcrumb-item"><a href="{{ route('admin.org-units.show', $a) }}">{{ $a->name }}</a></li>
    @endforeach
    <li class="breadcrumb-item active">{{ $unit->name }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('admin.org-units.create', ['parent' => $unit->id]) }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>جهة تابعة</a>
    <a href="{{ route('admin.org-units.edit', $unit) }}" class="btn btn-kfs-gold"><i class="fas fa-pen ml-1"></i>تعديل</a>
    @if ($unit->parent_id)
        <button type="button" class="btn btn-kfs-gold" onclick="confirmDelete('{{ route('admin.org-units.destroy', $unit) }}', @js($unit->name))"><i class="fas fa-trash ml-1"></i>حذف</button>
    @endif
@endsection

@section('content')
    <div class="gov-stats mb-4">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-sitemap"></i></div><div class="gs-num">{{ number_format($stats['descendants']) }}</div><div class="gs-label">جهات تابعة (كل المستويات)</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-users"></i></div><div class="gs-num">{{ number_format($stats['users']) }}</div><div class="gs-label">مستخدمون</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-people-group"></i></div><div class="gs-num">{{ number_format($stats['positions']) }}</div><div class="gs-label">وظائف ({{ $stats['vacant'] }} شاغرة)</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-inbox"></i></div><div class="gs-num">{{ number_format($stats['submissions']) }}</div><div class="gs-label">إرسالات بيانات</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-location-dot"></i></div><div class="gs-num">{{ number_format($stats['facilities'] + $stats['sector_data']) }}</div><div class="gs-label">سجلات مكانية/قطاعية</div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-kfs egypt-frame mb-4">
                <div class="card-body p-4">
                    <div class="d-flex gap-3 align-items-center mb-3">
                        <span class="eg-icon lg"><i class="fas {{ $unit->icon }}"></i></span>
                        <div>
                            <span class="badge badge-gold">{{ $unit->type_label }}</span>
                            @unless ($unit->is_active)<span class="status-pill status-error">غير نشطة</span>@endunless
                            <h5 class="mb-0 mt-1">{{ $unit->name }}</h5>
                            @if ($unit->name_en)<small class="text-muted en" dir="ltr">{{ $unit->name_en }}</small>@endif
                        </div>
                    </div>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">الجهة الأم</dt><dd class="col-7">{!! $unit->parent ? '<a href="' . route('admin.org-units.show', $unit->parent) . '">' . e($unit->parent->name) . '</a>' : '—' !!}</dd>
                        <dt class="col-5 text-muted">رئيس الجهة</dt><dd class="col-7">{{ $unit->head_name ?: '—' }} @if ($unit->head_title)<small class="text-muted d-block">{{ $unit->head_title }}</small>@endif</dd>
                        <dt class="col-5 text-muted">الهاتف</dt><dd class="col-7 en" dir="ltr" style="text-align:right">{{ $unit->phone ?: '—' }}</dd>
                        <dt class="col-5 text-muted">البريد</dt><dd class="col-7 en">{{ $unit->email ?: '—' }}</dd>
                        <dt class="col-5 text-muted">العنوان</dt><dd class="col-7">{{ $unit->address ?: '—' }}</dd>
                        <dt class="col-5 text-muted">السكان</dt><dd class="col-7">{{ $unit->population ? number_format($unit->population) : '—' }}</dd>
                        <dt class="col-5 text-muted">المساحة</dt><dd class="col-7">{{ $unit->area_km2 ? number_format($unit->area_km2, 1) . ' كم²' : '—' }}</dd>
                        <dt class="col-5 text-muted">الإحداثيات</dt>
                        <dd class="col-7 en">
                            @if ($unit->latitude && $unit->longitude)
                                {{ number_format($unit->latitude, 5) }}, {{ number_format($unit->longitude, 5) }}
                                @can('view_map')<a href="{{ route('map.index', ['unit' => $unit->id]) }}" class="d-block" style="font-family:var(--ff-ar)"><i class="fas fa-map-location-dot ml-1"></i>عرض على الخريطة</a>@endcan
                            @else — @endif
                        </dd>
                    </dl>
                    @if ($unit->description)
                        <hr><p class="small mb-0">{{ $unit->description }}</p>
                    @endif
                </div>
            </div>

            @if ($typeBreakdown->isNotEmpty())
                <div class="card-kfs">
                    <div class="card-header"><i class="fas fa-layer-group ml-2"></i>تكوين الجهات التابعة</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($typeBreakdown->sortDesc() as $t => $c)
                            <li class="list-group-item d-flex justify-content-between"><span><i class="fas {{ \App\Models\OrgUnit::ICONS[$t] ?? 'fa-circle' }} ml-2 text-gold"></i>{{ \App\Models\OrgUnit::TYPES[$t] ?? $t }}</span><b class="text-green">{{ number_format($c) }}</b></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card-kfs mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-diagram-project ml-2"></i>الجهات التابعة مباشرة ({{ $children->total() }})</span>
                    <a href="{{ route('admin.org-units.export', ['unit' => $unit->id]) }}" class="btn btn-sm btn-kfs-gold"><i class="fas fa-file-excel ml-1"></i>تصدير الفرع</a>
                </div>
                @if ($children->isEmpty())
                    <div class="empty-state"><p class="mb-2">لا توجد جهات تابعة.</p><a href="{{ route('admin.org-units.create', ['parent' => $unit->id]) }}" class="btn btn-sm btn-kfs-primary">إضافة جهة تابعة</a></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-kfs mb-0">
                            <thead><tr><th>الجهة</th><th>النوع</th><th>تابعة لها</th><th>مستخدمون</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($children as $c)
                                    <tr>
                                        <td><i class="fas {{ $c->icon }} ml-2 text-gold"></i><a href="{{ route('admin.org-units.show', $c) }}">{{ $c->name }}</a> @unless ($c->is_active)<small class="text-muted">(غير نشطة)</small>@endunless</td>
                                        <td><small>{{ $c->type_label }}</small></td>
                                        <td>{{ number_format($c->children_count) }}</td>
                                        <td>{{ number_format($c->users_count) }}</td>
                                        <td class="text-end"><a href="{{ route('admin.org-units.edit', $c) }}" class="btn btn-sm btn-outline-secondary btn-icon" aria-label="تعديل"><i class="fas fa-pen"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($children->hasPages())<div class="p-3">{{ $children->links() }}</div>@endif
                @endif
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card-kfs h-100">
                        <div class="card-header d-flex justify-content-between"><span><i class="fas fa-people-group ml-2"></i>الوظائف</span><a href="{{ route('admin.positions.index', ['unit' => $unit->id]) }}" class="small">الهيكل الوظيفي</a></div>
                        <ul class="list-group list-group-flush">
                            @forelse ($positions as $p)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $p->title }} <small class="text-muted d-block">{{ $p->holder->name ?? 'شاغرة' }}</small></span>
                                    <span class="badge {{ $p->user_id ? 'badge-green' : 'badge-gold' }}">{{ $p->category_label }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted small">لا توجد وظائف معرفة لهذه الجهة.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-kfs h-100">
                        <div class="card-header d-flex justify-content-between"><span><i class="fas fa-users ml-2"></i>الحسابات</span>@can('manage_users')<a href="{{ route('admin.users.index', ['unit' => $unit->id, 'subtree' => 1]) }}" class="small">الكل</a>@endcan</div>
                        <ul class="list-group list-group-flush">
                            @forelse ($users as $u)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $u->name }} <small class="text-muted d-block">{{ $u->jobPosition->title ?? $u->job_title }}</small></span>
                                    <small class="text-muted">{{ \App\Support\RoleLabels::label($u->roles->first()->name ?? '') }}</small>
                                </li>
                            @empty
                                <li class="list-group-item text-muted small">لا يوجد مستخدمون مرتبطون مباشرة.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
