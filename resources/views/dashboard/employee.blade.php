@extends('layouts.app')

@section('title', 'لوحتي')
@section('page-title', 'لوحة إدخال البيانات')
@section('page-subtitle', 'مهامك والنماذج المطلوبة من جهتك')
@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة التحكم</li>
@endsection
@section('page-actions')
    @can('create_submissions')
        <a href="{{ route('submissions.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>إرسال جديد</a>
    @endcan
    @can('import_export_excel')
        @if (Route::has('excel.center'))
            <a href="{{ route('excel.center') }}" class="btn btn-kfs-gold"><i class="fas fa-file-excel ml-1"></i>قوالب Excel</a>
        @endif
    @endcan
@endsection

@section('content')
    @include('dashboard.partials._styles')
    @include('dashboard.partials._greeting', ['subtitle' => 'ابدأ بالمهام المكلّف بها ثم النماذج الموجهة لجهتك'])

    @include('dashboard.partials._stats', ['items' => [
        ['fa-user-check', 'مكلّف بها', $assignedToMe->count(), route('submissions.index', ['tab' => 'assigned'])],
        ['fa-pen-to-square', 'مطلوب تعديلها', $myCounts['revision'], route('submissions.index', ['tab' => 'revision_required'])],
        ['fa-file-pen', 'مسودات', $myCounts['draft'], route('submissions.index', ['tab' => 'draft'])],
        ['fa-paper-plane', 'إرسالاتي', $myCounts['mine'], route('submissions.index')],
        ['fa-circle-check', 'معتمدة', $myCounts['approved']],
    ]])

    @if ($myCounts['revision'] > 0)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" data-reveal>
            <i class="fas fa-circle-exclamation"></i>
            <div>لديك {{ $myCounts['revision'] }} إرسال مطلوب تعديله. راجع ملاحظات المراجع ثم أعد الإرسال.</div>
            <a href="{{ route('submissions.index', ['tab' => 'revision_required']) }}" class="btn btn-sm btn-kfs-gold mr-auto">عرض</a>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            @include('dashboard.partials._submissions', ['rows' => $assignedToMe, 'title' => 'المهام المكلّف بها', 'icon' => 'fa-user-check', 'empty' => 'لا توجد مهام مكلّف بها حالياً.'])
        </div>
        <div class="col-xl-5">
            @include('dashboard.partials._submissions', ['rows' => $mySubmissions, 'title' => 'آخر إرسالاتي', 'icon' => 'fa-paper-plane', 'empty' => 'لم تنشئ أي إرسال بعد.', 'more' => route('submissions.index')])
        </div>
    </div>

    @include('dashboard.partials._actions', ['only' => ['submissions.index', 'excel.center', 'map.index', 'manual'], 'limit' => 3])

    @include('dashboard.partials._side')
@endsection
