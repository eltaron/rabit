@extends('layouts.app')

@section('title', 'لوحة مدير النظام')
@section('page-title', 'لوحة مدير النظام')
@section('page-subtitle', 'نظرة شاملة على الحسابات والهيكل والبيانات على مستوى المحافظة')
@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة التحكم</li>
@endsection
@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-kfs-gold"><i class="fas fa-user-plus ml-1"></i>حساب جديد</a>
    @if (Route::has('forms.templates.create'))
        <a href="{{ route('forms.templates.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>نموذج جديد</a>
    @endif
@endsection

@section('content')
    @include('dashboard.partials._styles')
    @include('dashboard.partials._greeting', ['subtitle' => 'ملخص النظام بالكامل — الحسابات، الهيكل التنظيمي، وتداول البيانات'])

    @include('dashboard.partials._stats', ['items' => [
        ['fa-users', 'حساب مسجل', $admin['users'], route('admin.users.index'), number_format($admin['active_users']) . ' حساب نشط'],
        ['fa-sitemap', 'جهة في الهيكل', $admin['units'], Route::has('admin.org-units.index') ? route('admin.org-units.index') : null, 'مديريات ومراكز ومدن وقرى'],
        ['fa-people-group', 'وظيفة معتمدة', $admin['positions'], Route::has('admin.positions.index') ? route('admin.positions.index') : null, number_format($admin['vacant']) . ' وظيفة شاغرة'],
        ['fa-file-lines', 'نموذج', $admin['templates'], route('forms.templates.index'), number_format($admin['active_templates']) . ' نموذج مفعّل'],
        ['fa-inbox', 'إرسال', $counts['total'], route('submissions.index'), number_format($counts['review']) . ' قيد المراجعة'],
        ['fa-location-dot', 'سجل مكاني ووصفي', $spatial['facilities'] + $spatial['sector'], route('map.index'), number_format($spatial['facilities_geo']) . ' بإحداثيات'],
    ]])

    <div class="section-head" data-reveal>
        <div class="sh-line"><span class="sh-num">02</span></div>
        <h3>الإدارة السريعة</h3>
        <p>الوصول المباشر لأدوات إدارة المنصة</p>
    </div>
    @include('dashboard.partials._actions', ['only' => ['admin.users.index', 'admin.roles.index', 'admin.org-units.index', 'admin.positions.index', 'forms.templates.index', 'map.index'], 'limit' => 6])

    @include('dashboard.partials._charts')

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            @include('dashboard.partials._units', ['title' => 'أداء الجهات الرئيسية'])
        </div>
        <div class="col-xl-5">
            <div class="card-kfs h-100" data-reveal>
                <div class="card-header"><i class="fas fa-user-shield ml-2"></i>توزيع الحسابات حسب الدور</div>
                <div class="card-body">
                    @php $maxR = max(1, $roleDistribution->max('value')); @endphp
                    @forelse ($roleDistribution as $r)
                        <div class="hbar">
                            <span class="lbl">{{ $r['label'] }}</span>
                            <div class="track"><div class="fill" style="width: {{ $r['value'] / $maxR * 100 }}%"></div></div>
                            <span class="val">{{ number_format($r['value']) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">لا توجد أدوار مسندة.</p>
                    @endforelse
                    @can('manage_roles')
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-kfs-gold mt-3">إدارة الأدوار والصلاحيات</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            @include('dashboard.partials._submissions', ['rows' => $recentSubmissions, 'title' => 'أحدث الإرسالات', 'more' => route('submissions.index')])
        </div>
        <div class="col-xl-5">
            <div class="card-kfs h-100" data-reveal>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-plus ml-2"></i>أحدث الحسابات</span>
                    <a href="{{ route('admin.users.index') }}" class="small fw-bold">عرض الكل <i class="fas fa-arrow-left mr-1 text-gold"></i></a>
                </div>
                <ul class="dash-list">
                    @foreach ($recentUsers as $u)
                        <li>
                            <span class="eg-icon sm">{{ mb_substr($u->name, 0, 1) }}</span>
                            <div class="flex-grow-1">
                                <div class="t">{{ $u->name }}</div>
                                <div class="s">{{ \App\Support\RoleLabels::label($u->roles->first()->name ?? '') }} · {{ $u->orgUnit->name ?? 'بدون جهة' }}</div>
                            </div>
                            <span class="badge-status {{ $u->is_active ? 'status-success' : 'badge-draft' }}">{{ $u->is_active ? 'نشط' : 'موقوف' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card-kfs" data-reveal>
                <div class="card-header"><i class="fas fa-ranking-star ml-2"></i>النماذج الأكثر استخداماً</div>
                <div class="card-body">
                    @php $maxT = max(1, $topTemplates->max('form_submissions_count')); @endphp
                    @forelse ($topTemplates as $t)
                        <div class="hbar" style="grid-template-columns: minmax(140px, 40%) 1fr 56px">
                            <span class="lbl text-truncate" title="{{ $t->name }}">{{ $t->name }}</span>
                            <div class="track"><div class="fill gold" style="width: {{ $t->form_submissions_count / $maxT * 100 }}%"></div></div>
                            <span class="val">{{ number_format($t->form_submissions_count) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">لا توجد نماذج بعد.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @include('dashboard.partials._side')
@endsection
