<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-kfs">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list-check ml-2"></i>الحقول</span>
                <span class="d-flex gap-2 small">
                    <button type="button" class="btn btn-link btn-sm p-0" wire:click="selectAllFields">الكل</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" wire:click="deselectAllFields">لا شيء</button>
                </span>
            </div>
            <div class="card-body" style="max-height:360px;overflow:auto">
                @foreach ($availableFields as $f)
                    <label class="form-check small">
                        <input type="checkbox" class="form-check-input" wire:click="toggleField({{ $f['id'] }})" @checked(in_array($f['id'], $selectedFields))>
                        <span class="form-check-label">{{ $f['label'] }}</span>
                    </label>
                @endforeach
            </div>
            <div class="card-footer small">
                <label class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" wire:model.live="reportConfig.show_header"> <span class="form-check-label">ترويسة رسمية</span></label>
                <label class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" wire:model.live="reportConfig.include_date"> <span class="form-check-label">إظهار التاريخ</span></label>
                <select class="form-select form-select-sm mt-2" wire:model.live="reportConfig.orientation" aria-label="اتجاه الصفحة">
                    <option value="portrait">طولي</option><option value="landscape">عرضي</option>
                </select>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-kfs egypt-frame" style="padding:1.5rem">
            @if ($reportConfig['show_header'] ?? true)
                <div class="text-center mb-3">
                    <div class="small text-muted">جمهورية مصر العربية — محافظة كفر الشيخ</div>
                    <h5 class="mb-1">{{ $report?->title }}</h5>
                    @if ($reportConfig['include_date'] ?? true)<small class="text-muted">{{ now()->format('Y/m/d') }}</small>@endif
                    <div class="egypt-cornice is-soft mt-2"></div>
                </div>
            @endif
            @php $preview = $this->getPreviewData(); @endphp
            @if (empty($preview))
                <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-eye-slash"></i></span><p class="small mb-0">اختر حقلاً واحداً على الأقل للمعاينة.</p></div>
            @else
                <dl class="row mb-0 small">
                    @foreach ($preview as $item)
                        <dt class="col-sm-4 text-muted fw-normal">{{ $item['label'] ?? '' }}</dt>
                        <dd class="col-sm-8">{{ $item['value'] ?? '—' }}</dd>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>
</div>
