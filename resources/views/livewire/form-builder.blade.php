@php
    $types = \App\Http\Controllers\Form\FormTemplateController::FIELD_TYPES;
    $typeIcons = [
        'text' => 'fa-font', 'textarea' => 'fa-align-right', 'number' => 'fa-hashtag', 'email' => 'fa-envelope',
        'phone' => 'fa-phone', 'national_id' => 'fa-id-card', 'date' => 'fa-calendar', 'select' => 'fa-list',
        'multiselect' => 'fa-list-check', 'radio' => 'fa-circle-dot', 'checkbox' => 'fa-square-check',
        'file' => 'fa-paperclip', 'image' => 'fa-image', 'latitude' => 'fa-location-crosshairs', 'longitude' => 'fa-location-crosshairs',
    ];
@endphp
<div>
    <div class="row g-4">
        <div class="col-lg-8">
            @if (empty($fields))
                <div class="empty-state" style="border:1px dashed var(--egypt-gold-line);border-radius:8px;background:var(--papyrus)">
                    <span class="eg-icon lg"><i class="fas fa-table-columns"></i></span>
                    <h5>لا توجد حقول بعد</h5>
                    <p class="mb-0">أضف الحقول من القائمة الجانبية. كل حقل يصبح عموداً في جدول البيانات وفي قالب Excel.</p>
                </div>
            @endif

            <div class="d-grid gap-3">
                @foreach ($fields as $index => $field)
                    @php $ft = $field['type'] ?? 'text'; @endphp
                    <div class="card-kfs p-3" wire:key="field-{{ $field['id'] ?? 'n' . $index }}">
                        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="eg-icon sm"><i class="fas {{ $typeIcons[$ft] ?? 'fa-font' }}"></i></span>
                                <span class="fw-bold text-green">حقل {{ $index + 1 }}</span>
                                <span class="badge badge-gold">{{ $types[$ft] ?? $ft }}</span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" wire:click="moveFieldUp({{ $index }})" title="تحريك لأعلى" aria-label="تحريك لأعلى" @disabled($index === 0)><i class="fas fa-arrow-up"></i></button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="moveFieldDown({{ $index }})" title="تحريك لأسفل" aria-label="تحريك لأسفل" @disabled($index === count($fields) - 1)><i class="fas fa-arrow-down"></i></button>
                                <button type="button" class="btn btn-outline-danger" wire:click="removeField({{ $index }})" wire:confirm="حذف هذا الحقل؟ (إن كانت به بيانات سيُخفى فقط)" title="حذف" aria-label="حذف الحقل"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small" for="fl{{ $index }}">عنوان الحقل <span class="text-danger">*</span></label>
                                <input type="text" id="fl{{ $index }}" class="form-control form-control-sm" wire:model.blur="fields.{{ $index }}.label" placeholder="مثال: اسم المدرسة">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small" for="fn{{ $index }}">المفتاح (إنجليزي)</label>
                                <input type="text" id="fn{{ $index }}" class="form-control form-control-sm en" dir="ltr" wire:model.blur="fields.{{ $index }}.name" placeholder="school_name">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small" for="ft{{ $index }}">النوع</label>
                                <select id="ft{{ $index }}" class="form-select form-select-sm" wire:change="changeFieldType({{ $index }}, $event.target.value)">
                                    @foreach ($types as $k => $l)
                                        <option value="{{ $k }}" @selected($ft === $k)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" wire:model.live="fields.{{ $index }}.is_required" id="required_{{ $index }}">
                                    <label class="form-check-label small" for="required_{{ $index }}">إلزامي</label>
                                </div>
                            </div>
                            @if (in_array($ft, ['select', 'multiselect', 'checkbox', 'radio']))
                                <div class="col-12">
                                    <label class="form-label small" for="fo{{ $index }}">الخيارات (خيار في كل سطر){{ $ft === 'checkbox' ? ' — اتركها فارغة لنعم/لا' : '' }}</label>
                                    <textarea id="fo{{ $index }}" class="form-control form-control-sm" rows="2" wire:model.blur="fields.{{ $index }}.options_text" placeholder="الخيار الأول&#10;الخيار الثاني"></textarea>
                                </div>
                            @endif
                            @if (in_array($ft, ['file', 'image']))
                                <div class="col-md-6">
                                    <label class="form-label small" for="fa{{ $index }}">أنواع الملفات المسموحة</label>
                                    <input type="text" id="fa{{ $index }}" class="form-control form-control-sm en" dir="ltr" wire:model.blur="fields.{{ $index }}.accepted_types" placeholder="pdf,jpg,png">
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label small" for="fh{{ $index }}">تعليمات للمُدخل (اختياري)</label>
                                <input type="text" id="fh{{ $index }}" class="form-control form-control-sm" wire:model.blur="fields.{{ $index }}.help_text" placeholder="تظهر كتلميح في الجدول وقالب Excel">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <input type="hidden" name="fields" value="{{ json_encode($fields, JSON_UNESCAPED_UNICODE) }}">
        </div>

        <div class="col-lg-4">
            <div class="card-kfs mb-3" style="position:sticky;top:calc(var(--idbar-height) + var(--navbar-height) + 1rem)">
                <div class="card-header"><i class="fas fa-plus ml-2"></i>إضافة حقل</div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach ($types as $k => $l)
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start" wire:click="addField('{{ $k }}')">
                                    <i class="fas {{ $typeIcons[$k] ?? 'fa-font' }} ml-1 text-gold"></i>{{ $l }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        <i class="fas fa-location-dot ml-1 text-gold"></i>
                        لعرض السجلات على الخريطة أضف حقلي «خط العرض» و«خط الطول» ثم اربطهما في قسم البيانات المكانية بعد الحفظ.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
