@extends('layouts.app')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')
@section('page-subtitle', 'ملخص البيانات المتاحة لك')
@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة التحكم</li>
@endsection

@section('content')
    @include('dashboard.partials._styles')
    @include('dashboard.partials._greeting')

    @include('dashboard.partials._stats', ['items' => [
        ['fa-inbox', 'إرسالات ضمن نطاقك', $counts['total'], route('submissions.index')],
        ['fa-circle-check', 'معتمدة', $counts['done'], null, $completionRate . '% نسبة الإنجاز'],
        ['fa-hourglass-half', 'قيد المراجعة', $counts['review']],
        ['fa-pen', 'قيد الإدخال', $counts['open']],
    ]])

    @include('dashboard.partials._actions')

    @include('dashboard.partials._charts')

    <div class="mb-4">
        @include('dashboard.partials._submissions', ['rows' => $recentSubmissions, 'title' => 'أحدث الإرسالات ضمن نطاقك', 'more' => route('submissions.index')])
    </div>

    @include('dashboard.partials._side')
@endsection
