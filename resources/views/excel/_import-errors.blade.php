@if (session('import_errors'))
    @php $importErrors = session('import_errors'); @endphp
    <div class="card-kfs mb-4" style="border-color:rgba(169,50,50,.3)">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-triangle-exclamation ml-2" style="color:var(--danger)"></i>أخطاء ملف Excel</span>
            <span class="badge-status status-error">{{ count($importErrors) }} ملاحظة</span>
        </div>
        <div class="table-responsive" style="max-height:340px">
            <table class="table table-kfs table-sm mb-0">
                <thead>
                    <tr><th style="width:90px">صف Excel</th><th>العمود</th><th>المشكلة</th><th>القيمة</th></tr>
                </thead>
                <tbody>
                    @foreach ($importErrors as $err)
                        <tr>
                            <td class="en fw-bold">{{ $err['row'] ?? '—' }}</td>
                            <td>{{ $err['field'] ?? '' }}</td>
                            <td class="text-danger">{{ $err['message'] ?? '' }}</td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($err['value'] ?? '', 40) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            رقم الصف هو رقم الصف الظاهر في برنامج Excel. صحّح القيم ثم أعد رفع الملف نفسه.
        </div>
    </div>
@endif
