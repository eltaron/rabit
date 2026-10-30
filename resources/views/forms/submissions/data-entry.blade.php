@extends('layouts.app')

@section('title', 'إدخال البيانات')
@section('page-title', 'إدخال البيانات')
@section('page-subtitle')
    {{ $submission->formTemplate->name }} · <span class="en">{{ $submission->reference_number }}</span>
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('submissions.index') }}">الإرسالات</a></li>
    <li class="breadcrumb-item"><a href="{{ route('submissions.show', $submission) }}" class="en">{{ $submission->reference_number }}</a></li>
    <li class="breadcrumb-item active">إدخال البيانات</li>
@endsection

@section('page-actions')
    @can('import_export_excel')
        <a href="{{ route('excel.submission.export', $submission) }}" class="btn btn-kfs-gold"><i class="fas fa-file-export ml-1"></i>تصدير للتعديل في Excel</a>
        <button type="button" class="btn btn-kfs-gold" data-bs-toggle="modal" data-bs-target="#uploadExisting"><i class="fas fa-file-import ml-1"></i>رفع من Excel</button>
    @endcan
@endsection

@section('content')
    @include('excel._import-errors')

    @if ($submission->status === 'revision_required' && $submission->revision_notes)
        <div class="alert alert-warning mb-4">
            <div class="fw-bold mb-1"><i class="fas fa-rotate-left ml-1"></i>ملاحظات المراجع</div>
            {{ $submission->revision_notes }}
        </div>
    @endif

    <div class="card-kfs">
        <div class="card-body">
            @livewire('form-submission-data-entry', ['submissionId' => $submission->id])
        </div>
    </div>

    @can('import_export_excel')
        @include('excel._upload-modal', [
            'id' => 'uploadExisting',
            'action' => route('excel.submission.import', $submission),
            'title' => 'رفع ملف Excel',
            'subtitle' => 'احفظ أي تعديلات يدوية أولاً، فالرفع يُحدّث البيانات المحفوظة مباشرة.',
            'withMode' => true,
            'modes' => $modes,
        ])
    @endcan
@endsection
