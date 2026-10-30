@extends('layouts.app')

@section('title', 'تعديل: ' . $template->name)
@section('page-title', 'تعديل النموذج')
@section('page-subtitle', $template->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('forms.templates.index') }}">النماذج</a></li>
    <li class="breadcrumb-item"><a href="{{ route('forms.templates.show', $template) }}">{{ \Illuminate\Support\Str::limit($template->name, 40) }}</a></li>
    <li class="breadcrumb-item active">تعديل</li>
@endsection

@section('content')
    @if ($template->formSubmissions()->exists())
        <div class="alert alert-warning mb-4">
            <i class="fas fa-circle-exclamation ml-1"></i>
            هذا النموذج به بيانات مُدخلة. الحقول التي تحذفها وبها بيانات ستُخفى فقط ولن تُحذف بياناتها. تعديل الحقول يغيّر أعمدة قالب Excel، فاطلب من الجهات تحميل القالب من جديد.
        </div>
    @endif
    <form method="POST" action="{{ route('forms.templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('forms.templates._form')
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('forms.templates.show', $template) }}" class="btn btn-outline-secondary">إلغاء</a>
            <button type="submit" class="btn btn-kfs"><i class="fas fa-floppy-disk ml-1"></i>حفظ التعديلات</button>
        </div>
    </form>
@endsection
