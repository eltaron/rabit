{{-- الحقول المشتركة بين إنشاء وتعديل النموذج --}}
@php
    $dist = old('distribution', $template->distribution ?? 'all');
    $oldTargets = old('targets');
    $targetRows = $oldTargets !== null
        ? collect($oldTargets)->mapWithKeys(fn ($id) => [$id => [
            'include_children' => (bool) (old('target_children')[$id] ?? true),
            'due_date' => old('target_due')[$id] ?? null,
        ]])->all()
        : $targets;
@endphp

@push('styles')
<style>
    .form-step { border: 1px solid var(--line); border-radius: 8px; background: var(--white-soft); margin-bottom: 1.25rem; }
    .form-step > header { display: flex; align-items: center; gap: .75rem; padding: .9rem 1.25rem; border-bottom: 1px solid var(--line); }
    .form-step > header .num { font-family: var(--ff-num); color: var(--egypt-gold); font-weight: 700; font-size: 1.15rem; }
    .form-step > header h3 { font-size: 1rem; margin: 0; }
    .form-step > header small { color: var(--text-soft); }
    .form-step > .body { padding: 1.25rem; }
    .dist-option { border: 1px solid var(--line); border-radius: var(--r); padding: .85rem 1rem; height: 100%; cursor: pointer; display: block; transition: border-color .4s var(--ease), background .4s var(--ease); }
    .dist-option:hover { border-color: var(--egypt-gold-line); }
    .dist-option:has(input:checked) { border-color: var(--egypt-gold); background: var(--egypt-gold-soft); }
    .dist-option .t { font-weight: 700; color: var(--egypt-green); font-family: var(--ff-ar); }
    .target-row { display: grid; grid-template-columns: 1fr auto 170px auto; gap: .6rem; align-items: center; padding: .55rem .75rem; border-bottom: 1px solid var(--line); }
    .target-row:last-child { border-bottom: 0; }
    @media (max-width: 767.98px) { .target-row { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

<section class="form-step">
    <header><span class="num">01</span><div><h3>بيانات النموذج</h3><small>الاسم والوصف والجهة المالكة</small></div></header>
    <div class="body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">اسم النموذج <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $template->name) }}" placeholder="مثال: حصر المدارس بالمركز" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="org_unit_id" class="form-label">الجهة المالكة للنموذج</label>
                <select class="form-select @error('org_unit_id') is-invalid @enderror" id="org_unit_id" name="org_unit_id">
                    <option value="">— ديوان عام المحافظة —</option>
                    @foreach ($units as $id => $label)
                        <option value="{{ $id }}" @selected(old('org_unit_id', $template->org_unit_id) == $id)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('org_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="description" class="form-label">الوصف</label>
                <textarea class="form-control" id="description" name="description" rows="2" placeholder="الغرض من النموذج وتعليمات تعبئته">{{ old('description', $template->description) }}</textarea>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true))>
                    <label class="form-check-label" for="is_active">النموذج مفعّل ومتاح لإدخال البيانات</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input type="hidden" name="requires_approval" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="requires_approval" name="requires_approval" value="1" @checked(old('requires_approval', $template->requires_approval ?? true))>
                    <label class="form-check-label" for="requires_approval">تتطلب البيانات المراجعة والاعتماد</label>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="form-step">
    <header><span class="num">02</span><div><h3>تداول النموذج بين الجهات</h3><small>من يستطيع إدخال البيانات في هذا النموذج</small></div></header>
    <div class="body">
        <div class="row g-3 mb-3">
            @foreach ($distributions as $key => $label)
                <div class="col-md-4">
                    <label class="dist-option">
                        <input class="form-check-input ml-2" type="radio" name="distribution" value="{{ $key }}" @checked($dist === $key) data-dist>
                        <span class="t">{{ $label }}</span>
                        <small class="d-block text-muted mt-1">
                            @switch($key)
                                @case('all') كل جهات المحافظة ترى النموذج وتُدخل بياناتها. @break
                                @case('targeted') حدد المراكز أو المديريات أو الجهات المطلوب منها البيانات، مع موعد التسليم. @break
                                @case('owner') يقتصر على الجهة المالكة والجهات التابعة لها. @break
                            @endswitch
                        </small>
                    </label>
                </div>
            @endforeach
        </div>

        <div id="targetsBox" class="{{ $dist === 'targeted' ? '' : 'd-none' }}">
            <label class="form-label" for="targetPicker">الجهات الموجه إليها النموذج</label>
            <div class="d-flex gap-2 mb-2">
                <select id="targetPicker" class="form-select">
                    <option value="">— اختر جهة —</option>
                    @foreach ($units as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-kfs-gold text-nowrap" id="addTarget"><i class="fas fa-plus ml-1"></i>إضافة</button>
            </div>
            @error('targets')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            <div class="card-kfs" id="targetList">
                @foreach ($targetRows as $id => $t)
                    <div class="target-row" data-id="{{ $id }}">
                        <span><i class="fas fa-building ml-2 text-gold"></i>{{ trim(str_replace('—', '', $units[$id] ?? ('#' . $id))) }}</span>
                        <input type="hidden" name="targets[]" value="{{ $id }}">
                        <label class="form-check m-0 small">
                            <input type="hidden" name="target_children[{{ $id }}]" value="0">
                            <input class="form-check-input" type="checkbox" name="target_children[{{ $id }}]" value="1" @checked($t['include_children'])>
                            <span class="form-check-label">تشمل الجهات التابعة</span>
                        </label>
                        <input type="date" class="form-control form-control-sm" name="target_due[{{ $id }}]" value="{{ $t['due_date'] ? \Illuminate\Support\Carbon::parse($t['due_date'])->format('Y-m-d') : '' }}" aria-label="موعد التسليم">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-icon" data-remove aria-label="إزالة"><i class="fas fa-xmark"></i></button>
                    </div>
                @endforeach
                <div class="p-3 text-muted small {{ count($targetRows) ? 'd-none' : '' }}" id="targetEmpty">لم تُحدد جهات بعد.</div>
            </div>
        </div>
    </div>
</section>

<section class="form-step">
    <header><span class="num">03</span><div><h3>البيانات المكانية</h3><small>اربط حقلي خط العرض وخط الطول لعرض السجلات على الخريطة التفاعلية</small></div></header>
    <div class="body">
        @if (empty($fieldNames))
            <p class="text-muted small mb-0"><i class="fas fa-circle-info ml-1 text-gold"></i>أضف حقلين من نوع «خط العرض» و«خط الطول» في منشئ الحقول أدناه، ثم احفظ النموذج لتظهر هنا للربط.</p>
        @else
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="geo_lat_field" class="form-label">حقل خط العرض (Latitude)</label>
                    <select class="form-select" id="geo_lat_field" name="geo_lat_field">
                        <option value="">— بدون —</option>
                        @foreach ($fieldNames as $n => $l)
                            <option value="{{ $n }}" @selected(old('geo_lat_field', $template->geo_lat_field) === $n)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="geo_lng_field" class="form-label">حقل خط الطول (Longitude)</label>
                    <select class="form-select" id="geo_lng_field" name="geo_lng_field">
                        <option value="">— بدون —</option>
                        @foreach ($fieldNames as $n => $l)
                            <option value="{{ $n }}" @selected(old('geo_lng_field', $template->geo_lng_field) === $n)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
    </div>
</section>

<section class="form-step">
    <header><span class="num">04</span><div><h3>حقول النموذج</h3><small>أعمدة البيانات — تصبح أعمدة قالب Excel بنفس الترتيب</small></div></header>
    <div class="body">
        @if ($template->exists)
            @livewire('form-builder', ['templateId' => $template->id])
        @else
            @livewire('form-builder')
        @endif
    </div>
</section>

@push('scripts')
<script>
(function () {
    const box = document.getElementById('targetsBox');
    document.querySelectorAll('[data-dist]').forEach(r => r.addEventListener('change', () => box.classList.toggle('d-none', r.value !== 'targeted' || !r.checked)));
    const list = document.getElementById('targetList');
    const empty = document.getElementById('targetEmpty');
    const picker = document.getElementById('targetPicker');
    const esc = s => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    document.getElementById('addTarget').addEventListener('click', () => {
        const id = picker.value;
        if (!id || list.querySelector('[data-id="' + id + '"]')) return;
        const name = esc(picker.options[picker.selectedIndex].text.replace(/—/g, '').trim());
        const row = document.createElement('div');
        row.className = 'target-row'; row.dataset.id = id;
        row.innerHTML = '<span><i class="fas fa-building ml-2 text-gold"></i>' + name + '</span>'
            + '<input type="hidden" name="targets[]" value="' + id + '">'
            + '<label class="form-check m-0 small"><input type="hidden" name="target_children[' + id + ']" value="0"><input class="form-check-input" type="checkbox" name="target_children[' + id + ']" value="1" checked> <span class="form-check-label">تشمل الجهات التابعة</span></label>'
            + '<input type="date" class="form-control form-control-sm" name="target_due[' + id + ']" aria-label="موعد التسليم">'
            + '<button type="button" class="btn btn-sm btn-outline-danger btn-icon" data-remove aria-label="إزالة"><i class="fas fa-xmark"></i></button>';
        list.insertBefore(row, empty);
        empty.classList.add('d-none');
        picker.value = '';
    });
    list.addEventListener('click', e => {
        const b = e.target.closest('[data-remove]');
        if (!b) return;
        b.closest('.target-row').remove();
        empty.classList.toggle('d-none', !!list.querySelector('.target-row'));
    });
})();
</script>
@endpush
