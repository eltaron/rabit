<div>
    @if ($tooLarge)
        <div class="empty-state">
            <span class="eg-icon lg"><i class="fas fa-file-excel"></i></span>
            <h5>هذا الإرسال كبير ({{ number_format($totalRows) }} صف)</h5>
            <p>لضمان الأداء، تُحرَّر الإرسالات التي تتجاوز {{ \App\Livewire\FormSubmissionDataEntry::MAX_GRID_ROWS }} صف عبر Excel:
                صدّر الملف، عدّله، ثم ارفعه بوضع «تحديث».</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="{{ route('excel.submission.export', $submissionId) }}" class="btn btn-kfs-gold"><i class="fas fa-file-export ml-1"></i>تصدير للتعديل</a>
                <button type="button" class="btn btn-outline-navy" data-bs-toggle="modal" data-bs-target="#uploadExisting"><i class="fas fa-file-import ml-1"></i>رفع الملف المعدّل</button>
                <button type="button" wire:click="submitForReview" wire:confirm="تقديم البيانات الحالية للمراجعة؟" class="btn btn-kfs"><i class="fas fa-paper-plane ml-1"></i>تقديم للمراجعة</button>
            </div>
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="small text-muted">
                <i class="fas fa-circle-info ml-1 text-gold"></i>
                أدخل صفاً لكل سجل. الحقول المعلّمة بـ <span class="text-danger">*</span> إلزامية. احفظ قبل مغادرة الصفحة.
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width:190px">
                    <span class="input-group-text">إضافة</span>
                    <input type="number" wire:model="newRowCount" class="form-control" min="1" max="100" aria-label="عدد الصفوف">
                    <button type="button" class="btn btn-outline-navy" wire:click="addMultipleRows">صفوف</button>
                </div>
                <button type="button" class="btn btn-sm btn-kfs" wire:click="addRow"><i class="fas fa-plus ml-1"></i>صف جديد</button>
            </div>
        </div>

        @error('rows')
            <div class="alert alert-danger py-2 small"><i class="fas fa-triangle-exclamation ml-1"></i>{{ $message }}</div>
        @enderror

        <div class="table-responsive" style="max-height:560px;border:1px solid var(--line);border-radius:8px">
            <table class="table table-kfs table-sm mb-0 entry-grid" style="min-width: {{ max(600, count($fields) * 160) }}px">
                <thead style="position:sticky;top:0;z-index:2">
                    <tr>
                        <th style="width:50px">#</th>
                        @foreach ($fields as $field)
                            <th class="text-nowrap" title="{{ $field['help_text'] ?? '' }}">
                                {{ $field['label'] ?? $field['name'] }}
                                @if ($field['is_required'] ?? false)<span style="color:var(--egypt-gold-2)">*</span>@endif
                            </th>
                        @endforeach
                        <th style="width:84px" class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $rowIndex => $row)
                        <tr wire:key="row-{{ $rowIndex }}">
                            <td class="fw-bold text-gold text-center en">{{ $loop->iteration }}</td>
                            @foreach ($fields as $field)
                                @php $type = $field['type'] ?? 'text'; $model = "rows.{$rowIndex}.{$field['id']}"; $opts = (array) ($field['options'] ?? []); @endphp
                                <td class="p-1">
                                    @if ($type === 'textarea')
                                        <textarea wire:model.blur="{{ $model }}" class="form-control form-control-sm" rows="1" aria-label="{{ $field['label'] }}" placeholder="{{ $field['placeholder'] ?? '' }}"></textarea>
                                    @elseif (in_array($type, ['select', 'radio']) && $opts)
                                        <select wire:model.blur="{{ $model }}" class="form-select form-select-sm" aria-label="{{ $field['label'] }}">
                                            <option value="">—</option>
                                            @foreach ($opts as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                                        </select>
                                    @elseif (in_array($type, ['checkbox', 'boolean']))
                                        <select wire:model.blur="{{ $model }}" class="form-select form-select-sm" aria-label="{{ $field['label'] }}">
                                            <option value="">—</option>
                                            @foreach ($opts ?: ['نعم', 'لا'] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                                        </select>
                                    @elseif (in_array($type, ['number', 'latitude', 'longitude']))
                                        <input type="number" step="any" wire:model.blur="{{ $model }}" class="form-control form-control-sm" aria-label="{{ $field['label'] }}">
                                    @elseif ($type === 'date')
                                        <input type="date" wire:model.blur="{{ $model }}" class="form-control form-control-sm" aria-label="{{ $field['label'] }}">
                                    @elseif ($type === 'email')
                                        <input type="email" wire:model.blur="{{ $model }}" class="form-control form-control-sm en" aria-label="{{ $field['label'] }}">
                                    @else
                                        <input type="text" wire:model.blur="{{ $model }}" class="form-control form-control-sm" aria-label="{{ $field['label'] }}"
                                               placeholder="{{ $field['placeholder'] ?? '' }}" @if ($type === 'national_id') inputmode="numeric" maxlength="14" @endif>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center text-nowrap p-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" wire:click="duplicateRow({{ $rowIndex }})" title="تكرار الصف" aria-label="تكرار الصف"><i class="fas fa-copy"></i></button>
                                @if (count($rows) > 1)
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" wire:click="removeRow({{ $rowIndex }})" title="حذف الصف" aria-label="حذف الصف"><i class="fas fa-trash"></i></button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-3" style="border-top:1px solid var(--line)">
            <small class="text-muted"><i class="fas fa-table ml-1 text-gold"></i>{{ count($rows) }} صف · {{ count($fields) }} حقل</small>
            <div class="d-flex gap-2">
                <span wire:loading class="spinner-border spinner-border-sm text-success align-self-center" role="status"><span class="visually-hidden">جاري الحفظ…</span></span>
                <button type="button" wire:click="saveData" wire:loading.attr="disabled" class="btn btn-outline-navy"><i class="fas fa-floppy-disk ml-1"></i>حفظ</button>
                <button type="button" wire:click="submitForReview" wire:loading.attr="disabled" wire:confirm="حفظ البيانات وتقديمها للمراجعة؟" class="btn btn-kfs"><i class="fas fa-paper-plane ml-1"></i>حفظ وتقديم للمراجعة</button>
            </div>
        </div>
    @endif

    @script
    <script>
        $wire.on('dataSaved', () => {
            Swal.fire({ icon: 'success', title: 'تم الحفظ', text: 'تم حفظ البيانات بنجاح', timer: 1500, showConfirmButton: false });
        });
        $wire.on('submittedForReview', (e) => {
            Swal.fire({ icon: 'success', title: 'تم التقديم', text: 'تم تقديم البيانات للمراجعة', timer: 1500, showConfirmButton: false })
                .then(() => { window.location.href = e.url; });
        });
    </script>
    @endscript
</div>
