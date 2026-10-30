@extends('layouts.app')

@section('title', 'لوحة القيادة العليا')
@section('page-title', $profile === 'leader' ? 'لوحة القيادات التنفيذية' : 'لوحة السيد المحافظ')
@section('page-subtitle', 'مؤشرات تنفيذية مختصرة لدعم اتخاذ القرار')
@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة القيادة</li>
@endsection
@section('page-actions')
    @can('use_decision_support')
        <a href="{{ route('decision.index') }}" class="btn btn-kfs-primary"><i class="fas fa-chess-king ml-1"></i>مساعد دعم القرار</a>
    @endcan
    @can('view_map')
        <a href="{{ route('map.index') }}" class="btn btn-kfs-gold"><i class="fas fa-map-location-dot ml-1"></i>الخريطة</a>
    @endcan
@endsection

@section('content')
    @include('dashboard.partials._styles')
    @include('dashboard.partials._greeting', ['subtitle' => 'موقف تداول البيانات وأداء الجهات ضمن نطاقك'])

    @include('dashboard.partials._stats', ['items' => [
        ['fa-inbox', 'إجمالي الإرسالات', $counts['total'], route('submissions.index')],
        ['fa-circle-check', 'معتمدة', $counts['done'], null, $completionRate . '% نسبة الإنجاز'],
        ['fa-hourglass-half', 'قيد المراجعة', $counts['review'], route('submissions.index', ['tab' => 'in_review'])],
        ['fa-file-signature', 'تقارير بانتظار الاعتماد', $reportsCount['awaiting'], route('reports.index')],
        ['fa-location-dot', 'سجل مكاني ووصفي', $spatial['facilities'] + $spatial['sector'], Route::has('map.index') ? route('map.index') : null],
    ]])

    {{-- شريط تنفيذي --}}
    <div class="exec-band mb-4" data-reveal>
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="gold-rule mb-2"></span>
                <h3>محافظة كفر الشيخ في أرقام — موقف اليوم</h3>
                <p>
                    {{ number_format($activeUsers) }} حساب نشط · {{ number_format($templatesCount) }} نموذج مفعّل ·
                    {{ number_format($counts['open']) }} إرسال قيد الإدخال · {{ number_format($counts['rejected']) }} مرفوض
                </p>
                @can('use_decision_support')
                    <a href="{{ route('decision.index') }}" class="btn btn-kfs-gold mt-3"><i class="fas fa-lightbulb ml-1"></i>اطلب تحليلاً وتوصيات</a>
                @endcan
            </div>
            <div class="col-lg-4 d-flex justify-content-lg-end">
                <div class="kpi-ring" style="--p: {{ $completionRate }}" role="img" aria-label="نسبة الإنجاز {{ $completionRate }}%">
                    <div><div><strong>{{ $completionRate }}%</strong><br><small>نسبة الإنجاز</small></div></div>
                </div>
            </div>
        </div>
    </div>

    @include('dashboard.partials._charts', ['monthly' => $approvedMonthly, 'trendTitle' => 'الإرسالات المعتمدة خلال آخر 6 أشهر'])

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            @include('dashboard.partials._units', ['title' => 'أداء المراكز والجهات'])
        </div>
        <div class="col-xl-5">
            <div class="card-kfs h-100" data-reveal>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-signature ml-2"></i>تقارير بانتظار اعتمادكم</span>
                    <a href="{{ route('reports.index') }}" class="small fw-bold">كل التقارير <i class="fas fa-arrow-left mr-1 text-gold"></i></a>
                </div>
                @if ($reportsAwaiting->isEmpty())
                    <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-check-double"></i></span><p class="mb-0 small">لا توجد تقارير معلقة.</p></div>
                @else
                    <ul class="dash-list">
                        @foreach ($reportsAwaiting as $r)
                            <li>
                                <span class="eg-icon sm"><i class="fas fa-file-lines"></i></span>
                                <div class="flex-grow-1">
                                    <a href="{{ route('reports.show', $r) }}" class="t d-block">{{ $r->title }}</a>
                                    <div class="s">{{ $r->createdBy->name ?? '' }} · {{ $r->created_at?->diffForHumans() }}</div>
                                </div>
                                @include('dashboard.partials._status', ['status' => $r->status])
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="section-head" data-reveal>
        <div class="sh-line"><span class="sh-num">02</span></div>
        <h3>أدوات القيادة</h3>
    </div>
    @include('dashboard.partials._actions', ['only' => ['decision.index', 'map.index', 'reports.index', 'submissions.index', 'manual'], 'limit' => 3])

    @include('dashboard.partials._side')
@endsection
