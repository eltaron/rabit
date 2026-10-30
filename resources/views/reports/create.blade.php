@extends('layouts.app')

@section('title', 'إنشاء تقرير جديد')
@section('page-title', 'إنشاء تقرير جديد')
@section('page-subtitle', 'اختر الإرسال المصدر وحدد الحقول التي يتضمنها التقرير')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active">إنشاء</li>
@endsection

@section('content')
    @php
        $fieldMap = $submissions->mapWithKeys(fn ($s) => [$s->id => [
            'ref' => $s->reference_number,
            'template' => $s->formTemplate->name ?? '',
            'fields' => ($s->formTemplate?->formFields ?? collect())->sortBy('sort_order')->map(fn ($f) => ['id' => $f->id, 'label' => $f->label])->values(),
        ]]);
        $selected = old('form_submission_id', request('form_submission_id'));
    @endphp

    <form method="POST" action="{{ route('reports.store') }}" class="row g-4">
        @csrf
        <div class="col-lg-8">
            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-file-circle-plus ml-2"></i>إعداد التقرير</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="form_submission_id" class="form-label">الإرسال المصدر <span class="text-danger">*</span></label>
                        <select class="form-select @error('form_submission_id') is-invalid @enderror" id="form_submission_id" name="form_submission_id" required>
                            <option value="">— اختر إرسالاً —</option>
                            @foreach ($submissions as $submission)
                                <option value="{{ $submission->id }}" @selected($selected == $submission->id)>
                                    {{ $submission->reference_number }} — {{ $submission->formTemplate->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('form_submission_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($submissions->isEmpty())
                            <div class="form-text">لا توجد إرسالات ضمن نطاق صلاحياتك يمكن بناء تقرير عليها.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">عنوان التقرير <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                               value="{{ old('title') }}" placeholder="مثال: تقرير موقف محطات مياه الشرب — أكتوبر" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <fieldset class="mb-3">
                        <legend class="form-label d-flex justify-content-between align-items-center w-100">
                            <span>الحقول المتضمنة في التقرير</span>
                            <span class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-link p-0" onclick="toggleAll(true)">تحديد الكل</button>
                                <button type="button" class="btn btn-sm btn-link p-0 text-muted" onclick="toggleAll(false)">إلغاء الكل</button>
                            </span>
                        </legend>
                        <div id="fieldsBox" class="p-3" style="background:var(--papyrus);border:1px solid var(--line);border-radius:var(--r);min-height:70px">
                            <small class="text-muted">اختر إرسالاً أولاً لعرض حقوله.</small>
                        </div>
                        @error('selected_fields')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </fieldset>

                    <div class="d-flex justify-content-between flex-wrap gap-2 pt-2">
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary"><i class="fas fa-xmark ml-1"></i>إلغاء</a>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="draft" class="btn btn-kfs-gold"><i class="fas fa-floppy-disk ml-1"></i>حفظ كمسودة</button>
                            <button type="submit" name="action" value="submit" class="btn btn-kfs-primary"><i class="fas fa-paper-plane ml-1"></i>حفظ وإرسال للاعتماد</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-eye ml-2"></i>ملخص</div>
                <div class="card-body" id="reportPreview">
                    <p class="text-muted text-center mb-0 small">اختر إرسالاً لعرض ملخصه</p>
                </div>
            </div>
            <div class="alert alert-info mt-3 small mb-0">
                <i class="fas fa-circle-info ml-1"></i>
                بعد الحفظ يمكنك تحرير المحتوى وتوليد ملخص بالذكاء الاصطناعي، وكل تعديل يُحفظ كإصدار جديد.
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const map = @json($fieldMap);
        const oldSel = @json(old('selected_fields'));
        const sel = document.getElementById('form_submission_id');
        const box = document.getElementById('fieldsBox');
        const prev = document.getElementById('reportPreview');
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        function render() {
            const d = map[sel.value];
            if (!d) {
                box.innerHTML = '<small class="text-muted">اختر إرسالاً أولاً لعرض حقوله.</small>';
                prev.innerHTML = '<p class="text-muted text-center mb-0 small">اختر إرسالاً لعرض ملخصه</p>';
                return;
            }
            box.innerHTML = d.fields.length ? '<div class="row g-1">' + d.fields.map(f =>
                '<div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="selected_fields[]" value="' + f.id + '"' +
                (!oldSel || oldSel.includes(String(f.id)) ? ' checked' : '') + '> <span class="form-check-label small">' + esc(f.label) + '</span></label></div>').join('') + '</div>'
                : '<small class="text-muted">لا توجد حقول في هذا النموذج.</small>';
            prev.innerHTML = '<dl class="mb-0 small"><dt class="text-muted">المرجع</dt><dd class="en fw-bold">' + esc(d.ref) + '</dd>' +
                '<dt class="text-muted">النموذج</dt><dd>' + esc(d.template) + '</dd>' +
                '<dt class="text-muted">عدد الحقول</dt><dd class="mb-0">' + d.fields.length + '</dd></dl>';
        }
        window.toggleAll = on => box.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = on);
        sel.addEventListener('change', render);
        render();
    })();
</script>
@endpush
