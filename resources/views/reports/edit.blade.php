@extends('layouts.app')

@section('title', 'تعديل التقرير')
@section('page-title', 'تعديل التقرير')
@section('page-subtitle', $report->title)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.show', $report) }}">{{ \Illuminate\Support\Str::limit($report->title, 40) }}</a></li>
    <li class="breadcrumb-item active">تعديل</li>
@endsection

@section('content')
    {{-- نموذج التوليد بالذكاء الاصطناعي خارج نموذج الحفظ (لا تداخل نماذج) --}}
    <form id="aiForm" method="POST" action="{{ route('reports.generate-ai', $report) }}">@csrf</form>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('reports.update', $report) }}" class="card-kfs">
                @csrf
                @method('PUT')
                <div class="card-header"><i class="fas fa-pen-to-square ml-2"></i>تحرير التقرير</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">الإرسال المصدر</label>
                        <input type="text" class="form-control" disabled
                               value="{{ $report->formSubmission->reference_number ?? '—' }} — {{ $report->formSubmission->formTemplate->name ?? '' }}">
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">عنوان التقرير <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $report->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <fieldset class="mb-3">
                        <legend class="form-label">الحقول المتضمنة</legend>
                        <div class="p-3 row g-1 mx-0" style="background:var(--papyrus);border:1px solid var(--line);border-radius:var(--r)">
                            @forelse ($report->formSubmission->formTemplate->formFields ?? [] as $field)
                                <div class="col-md-6">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="selected_fields[]" value="{{ $field->id }}" @checked(in_array($field->id, $report->selected_fields ?? []))>
                                        <span class="form-check-label small">{{ $field->label }}</span>
                                    </label>
                                </div>
                            @empty
                                <small class="text-muted">لا توجد حقول.</small>
                            @endforelse
                        </div>
                    </fieldset>

                    <div class="mb-3">
                        <label for="content" class="form-label">محتوى التقرير</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="10" style="line-height:2">{{ old('content', $report->content) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">الملخص المُولّد بالذكاء الاصطناعي</label>
                            <button type="submit" form="aiForm" class="btn btn-sm btn-kfs-gold"><i class="fas fa-wand-magic-sparkles ml-1"></i>توليد / تحديث</button>
                        </div>
                        <div class="p-3 small" style="background:var(--white-soft);border:1px solid var(--line-gold);border-radius:var(--r);min-height:60px;line-height:2">
                            {!! $report->ai_generated_content ? nl2br(e($report->ai_generated_content)) : '<span class="text-muted">لم يتم التوليد بعد.</span>' !!}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="change_notes" class="form-label">ملاحظات هذا الإصدار</label>
                        <input type="text" class="form-control" id="change_notes" name="change_notes" placeholder="وصف مختصر للتغييرات…" value="{{ old('change_notes') }}">
                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <a href="{{ route('reports.show', $report) }}" class="btn btn-outline-secondary"><i class="fas fa-xmark ml-1"></i>إلغاء</a>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="save" class="btn btn-kfs-gold"><i class="fas fa-floppy-disk ml-1"></i>حفظ كإصدار جديد</button>
                            @if (in_array($report->status, ['draft', 'rejected']))
                                <button type="submit" name="action" value="submit" class="btn btn-kfs-primary"><i class="fas fa-paper-plane ml-1"></i>حفظ وإرسال للاعتماد</button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-circle-info ml-2"></i>معلومات التقرير</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">الحالة</span>@include('dashboard.partials._status', ['status' => $report->status])</li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">الإصدار الحالي</span><span class="badge badge-green en">v{{ $report->current_version }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">تاريخ الإنشاء</span><span>{{ $report->created_at->format('Y/m/d H:i') }}</span></li>
                </ul>
                @if ($report->status === 'rejected' && $report->rejection_notes)
                    <div class="card-body">
                        <div class="alert alert-danger small mb-0"><strong>سبب الرفض:</strong> {{ $report->rejection_notes }}</div>
                    </div>
                @endif
            </div>
            <a href="{{ route('reports.versions.index', $report) }}" class="btn btn-outline-secondary w-100 mt-3"><i class="fas fa-code-branch ml-1"></i>سجل الإصدارات</a>
        </div>
    </div>
@endsection
