@extends('layouts.app')

@section('title', 'لوحة المدير')
@section('page-title', 'لوحة المتابعة والمراجعة')
@section('page-subtitle', 'متابعة إدخال البيانات ومراجعتها واعتمادها في جهتك والجهات التابعة')
@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة التحكم</li>
@endsection
@section('page-actions')
    @can('create_submissions')
        <a href="{{ route('submissions.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>إرسال جديد</a>
    @endcan
    @can('manage_form_templates')
        <a href="{{ route('forms.templates.index') }}" class="btn btn-kfs-gold"><i class="fas fa-file-lines ml-1"></i>النماذج</a>
    @endcan
@endsection

@section('content')
    @include('dashboard.partials._styles')
    @include('dashboard.partials._greeting')

    @include('dashboard.partials._stats', ['items' => [
        ['fa-inbox', 'إرسالات ضمن نطاقك', $counts['total'], route('submissions.index')],
        ['fa-magnifying-glass', 'بانتظار مراجعتك', $counts['review'], route('submissions.index', ['tab' => 'in_review'])],
        ['fa-user-check', 'مكلّف بها', $assignedToMe->count(), route('submissions.index', ['tab' => 'assigned'])],
        ['fa-circle-check', 'معتمدة', $counts['done'], null, $completionRate . '% نسبة الإنجاز'],
        ['fa-file-signature', 'تقارير بانتظار الاعتماد', $reportsAwaiting, route('reports.index')],
    ]])

    @include('dashboard.partials._actions', ['limit' => 3])

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            @include('dashboard.partials._submissions', ['rows' => $pendingReview, 'title' => 'بانتظار المراجعة (الأقدم أولاً)', 'icon' => 'fa-magnifying-glass', 'empty' => 'لا توجد إرسالات بانتظار المراجعة.', 'more' => route('submissions.index', ['tab' => 'in_review'])])
        </div>
        <div class="col-xl-5">
            @include('dashboard.partials._units')
        </div>
    </div>

    @include('dashboard.partials._charts')

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            @include('dashboard.partials._submissions', ['rows' => $assignedToMe, 'title' => 'المهام المكلّف بها', 'icon' => 'fa-user-check', 'empty' => 'لا توجد مهام مكلّف بها حالياً.'])
        </div>
        <div class="col-xl-5">
            <div class="card-kfs h-100" data-reveal>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-column ml-2"></i>أحدث التقارير</span>
                    <a href="{{ route('reports.index') }}" class="small fw-bold">عرض الكل <i class="fas fa-arrow-left mr-1 text-gold"></i></a>
                </div>
                @if ($recentReports->isEmpty())
                    <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-chart-column"></i></span><p class="mb-0 small">لا توجد تقارير بعد.</p></div>
                @else
                    <ul class="dash-list">
                        @foreach ($recentReports as $r)
                            <li>
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

    @include('dashboard.partials._side')
@endsection
