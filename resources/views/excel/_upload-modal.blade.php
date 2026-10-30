{{--
    نافذة رفع ملف Excel.
    المتغيرات: $id, $action (يمكن تغييره من JS عبر data-action), $title, $withMode (bool), $modes, $subtitle (اختياري)
--}}
@php $withMode = $withMode ?? false; @endphp
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="POST" action="{{ $action }}" enctype="multipart/form-data" data-excel-upload>
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label"><i class="fas fa-file-arrow-up ml-2"></i><span data-modal-title>{{ $title }}</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                @if (! empty($subtitle))
                    <p class="text-muted small mb-3" data-modal-subtitle>{{ $subtitle }}</p>
                @else
                    <p class="text-muted small mb-3" data-modal-subtitle></p>
                @endif

                <div class="drop-zone d-block mb-3" role="button" tabindex="0" aria-label="اختيار ملف Excel" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.querySelector('input').click();}">
                    <span class="eg-icon lg mb-2"><i class="fas fa-file-excel"></i></span>
                    <div class="fw-bold text-green">اسحب ملف Excel هنا أو اضغط للاختيار</div>
                    <small class="text-muted d-block">الصيغ المقبولة: xlsx, xls, csv — حتى 20 ميجابايت</small>
                    <div class="dz-name fw-bold mt-2 en" style="color:var(--egypt-gold)"></div>
                    <input type="file" name="file" id="{{ $id }}File" accept=".xlsx,.xls,.csv" class="visually-hidden">
                </div>

                @if ($withMode)
                    <fieldset class="mb-3">
                        <legend class="form-label mb-2">طريقة الاستيراد</legend>
                        @foreach ($modes as $key => $label)
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="mode" id="{{ $id }}Mode{{ $key }}" value="{{ $key }}" {{ $key === 'update' ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $id }}Mode{{ $key }}">
                                    {{ $label }}
                                    @if ($key === 'replace')<small class="text-danger">(يحذف البيانات الحالية)</small>@endif
                                </label>
                            </div>
                        @endforeach
                    </fieldset>
                @endif

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="skip_invalid" value="1" id="{{ $id }}Skip">
                    <label class="form-check-label" for="{{ $id }}Skip">تجاهل الصفوف غير الصحيحة وحفظ الباقي</label>
                </div>
                <div class="alert alert-info small mb-0">
                    <i class="fas fa-circle-info ml-1"></i>
                    افتراضياً لا يُحفظ أي شيء إذا وُجد خطأ في أي صف، وستظهر لك قائمة الأخطاء برقم الصف والعمود لتصحيحها.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-kfs">
                    <span class="spinner-border spinner-border-sm ml-1 d-none" data-spinner></span>
                    <i class="fas fa-upload ml-1"></i>رفع واستيراد
                </button>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('submit', function (e) {
                const f = e.target.closest('[data-excel-upload]');
                if (!f) return;
                const b = f.querySelector('button[type=submit]');
                b.disabled = true;
                f.querySelector('[data-spinner]')?.classList.remove('d-none');
            });
            // فتح النافذة بإجراء مختلف (مثلاً من بطاقة نموذج محدد)
            document.addEventListener('click', function (e) {
                const t = e.target.closest('[data-upload-action]');
                if (!t) return;
                const m = document.querySelector(t.dataset.bsTarget);
                if (!m) return;
                m.querySelector('form').action = t.dataset.uploadAction;
                if (t.dataset.uploadTitle) m.querySelector('[data-modal-title]').textContent = t.dataset.uploadTitle;
                if (t.dataset.uploadSubtitle !== undefined) m.querySelector('[data-modal-subtitle]').textContent = t.dataset.uploadSubtitle;
            });
        </script>
    @endpush
@endonce
