@extends('layouts.app')

@section('title', 'إرسال جديد')
@section('page-title', 'إرسال بيانات جديد')
@section('page-subtitle', 'اختر النموذج ثم أدخل البيانات يدوياً أو ارفعها من ملف Excel')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('submissions.index') }}">الإرسالات</a></li>
    <li class="breadcrumb-item active">إرسال جديد</li>
@endsection

@section('content')
    @include('excel._import-errors')

    @if ($templates->isEmpty())
        <div class="empty-state card-kfs">
            <span class="eg-icon lg"><i class="fas fa-file-lines"></i></span>
            <h5>لا توجد نماذج متاحة لجهتك</h5>
            <p class="mb-0">لم يتم توجيه أي نموذج مفعّل لجهتك حتى الآن.</p>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-7">
                <form method="POST" action="{{ route('submissions.store') }}" class="card-kfs">
                    @csrf
                    <div class="card-header"><i class="fas fa-keyboard ml-2"></i>الإدخال اليدوي</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="form_template_id" class="form-label">النموذج <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg @error('form_template_id') is-invalid @enderror" id="form_template_id" name="form_template_id" required>
                                <option value="">— اختر النموذج —</option>
                                @foreach ($templates as $t)
                                    <option value="{{ $t->id }}"
                                            data-import="{{ route('excel.template.import', $t) }}"
                                            data-download="{{ route('excel.template.download', $t) }}"
                                            data-fields="{{ $t->formFields->count() }}"
                                            data-desc="{{ \Illuminate\Support\Str::limit($t->description, 160) }}"
                                            @selected(old('form_template_id', $selected) == $t->id)>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_template_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="tplInfo"></div>
                        </div>

                        @if (auth()->user()->can('approve_submissions') || auth()->user()->can('review_submissions'))
                            <div class="mb-3">
                                <label for="assigned_to" class="form-label">إسناد الإدخال إلى</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">— أنا —</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}" @selected(old('assigned_to') == $u->id)>{{ $u->name }}{{ $u->orgUnit ? ' — ' . $u->orgUnit->name : '' }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">عند الإسناد لموظف آخر سيظهر الإرسال في تبويب «مطلوب مني» لديه.</div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('submissions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-kfs"><i class="fas fa-arrow-left ml-1"></i>متابعة لإدخال البيانات</button>
                        </div>
                    </div>
                </form>
            </div>

            @can('import_export_excel')
                <div class="col-lg-5">
                    <div class="card-kfs egypt-frame h-100">
                        <div class="card-body p-4">
                            <span class="eg-icon lg mb-3"><i class="fas fa-file-excel"></i></span>
                            <h5>أو استخدم ملف Excel</h5>
                            <p class="text-muted small">مناسب للبيانات الكبيرة: حمّل قالب النموذج المختار، املأه، ثم ارفعه ليتم إنشاء الإرسال تلقائياً.</p>
                            <div class="d-grid gap-2">
                                <a href="#" class="btn btn-kfs-gold disabled" id="dlTpl" aria-disabled="true"><i class="fas fa-file-arrow-down ml-1"></i>تحميل قالب النموذج</a>
                                <button type="button" class="btn btn-outline-navy" id="upTpl" data-bs-toggle="modal" data-bs-target="#uploadNew" disabled>
                                    <i class="fas fa-file-arrow-up ml-1"></i>رفع الملف كإرسال جديد
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">اختر النموذج أولاً.</small>
                        </div>
                    </div>
                </div>
                @include('excel._upload-modal', ['id' => 'uploadNew', 'action' => '#', 'title' => 'رفع ملف Excel كإرسال جديد', 'subtitle' => 'سيتم إنشاء إرسال جديد باسم جهتك.', 'withMode' => false, 'modes' => $modes])
            @endcan
        </div>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('form_template_id');
    if (!sel) return;
    const dl = document.getElementById('dlTpl'), up = document.getElementById('upTpl'), info = document.getElementById('tplInfo');
    const sync = () => {
        const o = sel.options[sel.selectedIndex];
        const ok = !!sel.value;
        info.textContent = ok ? (o.dataset.fields + ' حقل' + (o.dataset.desc ? ' — ' + o.dataset.desc : '')) : '';
        if (dl) { dl.classList.toggle('disabled', !ok); dl.href = ok ? o.dataset.download : '#'; dl.setAttribute('aria-disabled', !ok); }
        if (up) { up.disabled = !ok; if (ok) { const f = document.querySelector('#uploadNew form'); if (f) f.action = o.dataset.import; } }
    };
    sel.addEventListener('change', sync); sync();
})();
</script>
@endpush
