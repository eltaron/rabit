@php
    $typeLabels = array_merge(
        ['maintenance' => 'خطة الصيانة الشهرية', 'vehicles' => 'بيان المركبات', 'strategic' => 'بيان القيادة الاستراتيجية'],
        collect(\App\Services\SectorDataImportService::SECTOR_LABELS)->map(fn ($l) => 'قطاع ' . $l)->all(),
        \App\Services\FacilityDataImportService::MODULE_LABELS,
    );
    $typeName = $typeLabels[$fileType] ?? ($fileType ? $fileType : 'نوع غير معروف');
@endphp
<div>
    @if ($resultMessage)
        <div class="alert alert-{{ $resultType === 'success' ? 'success' : 'danger' }} d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="fas fa-{{ $resultType === 'success' ? 'circle-check' : 'triangle-exclamation' }} mt-1"></i>
            <div class="flex-grow-1">{{ $resultMessage }}</div>
            <button type="button" class="btn-close" wire:click="resetState" aria-label="إغلاق"></button>
        </div>
    @endif

    @if ($status !== 'done' || $resultType === 'danger')
        <div class="card-kfs mb-4">
            <div class="card-header"><i class="fas fa-file-excel ml-2"></i>رفع ملف Excel <small class="text-muted fw-normal mr-2">xlsx / xls — حتى 20 ميجابايت</small></div>
            <div class="card-body">
                <label for="excelFileInput" class="drop-zone d-block mb-0">
                    <input type="file" accept=".xlsx,.xls" wire:model.live="file" id="excelFileInput" class="d-none">
                    <span class="eg-icon lg mb-2"><i class="fas fa-file-arrow-up"></i></span>
                    <span class="d-block fw-bold" style="color:var(--egypt-green)">
                        @if ($file && method_exists($file, 'getClientOriginalName')) {{ $file->getClientOriginalName() }} @else اضغط لاختيار ملف أو اسحبه هنا @endif
                    </span>
                    <small class="text-muted">يُتعرّف على النوع تلقائياً: صيانة، مركبات، بيان استراتيجي، قطاعات (استثمار/سكان/تعليم/تموين/صحة)، ونوتة المعلومات</small>
                </label>

                <div wire:loading wire:target="file" class="mt-3 w-100">
                    <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div>
                    <small class="text-muted d-block mt-1">جاري تحليل الملف…</small>
                </div>

                @error('file')
                    <div class="text-danger mt-2 small"><i class="fas fa-circle-exclamation ml-1"></i>{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif

    @if ($status === 'ready' || $status === 'importing')
        <div class="card-kfs mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-circle-info ml-2"></i>معاينة الملف قبل الاستيراد</span>
                <span class="badge {{ isset($typeLabels[$fileType]) ? 'badge-gold' : 'badge-status status-error' }}">{{ $typeName }}</span>
            </div>
            <div class="card-body">
                <div class="gov-stats mb-3" style="box-shadow:none">
                    <div class="gs-item"><div class="gs-icon"><i class="fas fa-file"></i></div><div class="fw-bold text-truncate" style="color:var(--egypt-green)">{{ $file?->getClientOriginalName() }}</div><div class="gs-label">الملف</div></div>
                    <div class="gs-item"><div class="gs-icon"><i class="fas fa-layer-group"></i></div><div class="gs-num" style="font-size:1.4rem">{{ $totalSheets }}</div><div class="gs-label">أوراق عمل</div></div>
                    <div class="gs-item"><div class="gs-icon"><i class="fas fa-table"></i></div><div class="gs-num" style="font-size:1.4rem">{{ number_format($totalRows) }}</div><div class="gs-label">إجمالي الصفوف</div></div>
                </div>

                @unless (isset($typeLabels[$fileType]))
                    <div class="alert alert-warning small"><i class="fas fa-triangle-exclamation ml-1"></i>لم يُتعرّف على نوع الملف. تأكد من أنه أحد القوالب الرسمية قبل الاستيراد.</div>
                @endunless

                <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
                    @foreach ($sheets as $sheetName => $rows)
                        <li class="nav-item">
                            <button type="button" class="nav-link text-nowrap {{ $selectedSheet === $sheetName ? 'active' : '' }}"
                                    wire:click="$set('selectedSheet', '{{ addslashes($sheetName) }}'); loadPreview()">
                                {{ $sheetName }} <small class="text-muted">({{ count($rows) }})</small>
                            </button>
                        </li>
                    @endforeach
                </ul>

                @if (! empty($previewData))
                    <div class="table-responsive" style="max-height:400px">
                        <table class="table table-sm table-kfs mb-0" style="font-size:.78rem">
                            <thead>
                                <tr>
                                    @foreach ($previewData[0] ?? [] as $header)
                                        <th style="position:sticky;top:0;z-index:1">{{ $header ?? '—' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @for ($i = 1; $i < count($previewData); $i++)
                                    @php $row = $previewData[$i]; @endphp
                                    @continue(implode('', array_map(fn ($c) => (string) ($c ?? ''), $row)) === '')
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td class="text-nowrap" style="max-width:220px;overflow:hidden;text-overflow:ellipsis">{{ $cell ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-inbox"></i></span><p class="small mb-0">لا توجد بيانات في هذه الورقة.</p></div>
                @endif

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid var(--line)">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetState"><i class="fas fa-xmark ml-1"></i>إلغاء</button>
                    <button type="button" class="btn btn-kfs-primary" wire:click="import" wire:loading.attr="disabled" @disabled($importing)>
                        <span wire:loading.remove wire:target="import"><i class="fas fa-file-import ml-1"></i>استيراد البيانات</span>
                        <span wire:loading wire:target="import"><span class="spinner-border spinner-border-sm ml-1"></span>جاري الاستيراد…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
