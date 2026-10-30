@extends('layouts.app')

@section('title', 'نموذج جديد')
@section('page-title', 'إنشاء نموذج جديد')
@section('page-subtitle', 'صمّم نموذج جمع البيانات وحدد الجهات المطلوب منها تعبئته')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('forms.templates.index') }}">النماذج</a></li>
    <li class="breadcrumb-item active">نموذج جديد</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('forms.templates.store') }}">
        @csrf
        @include('forms.templates._form')
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('forms.templates.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            <button type="submit" class="btn btn-kfs"><i class="fas fa-floppy-disk ml-1"></i>حفظ النموذج</button>
        </div>
    </form>
@endsection
