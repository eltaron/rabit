{{-- جدول بيانات الإرسال. المتغيرات: $groupedData, $fields, $submission, $limit --}}
@php
    $limit = $limit ?? 300;
    $totalRows = $totalRows ?? $groupedData->count();
    $shown = $groupedData->take($limit);
@endphp
@if ($totalRows > 0)
    <div class="table-responsive data-grid" style="max-height:560px">
        <table class="table table-kfs table-sm mb-0">
            <thead style="position:sticky;top:0;z-index:2">
                <tr>
                    <th style="width:56px">#</th>
                    @foreach ($fields as $field)
                        <th class="text-nowrap">{{ $field->label ?? $field->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($shown as $rowIndex => $entries)
                    @php $byField = $entries->keyBy('form_field_id'); @endphp
                    <tr>
                        <td class="fw-bold text-gold en" title="رقم الصف في النظام: {{ $rowIndex }}">{{ $loop->iteration }}</td>
                        @foreach ($fields as $field)
                            <td class="text-nowrap">{{ $byField[$field->id]->value ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 small text-muted">
        <span><i class="fas fa-table ml-1 text-gold"></i>{{ number_format($totalRows) }} صف · {{ count($fields) }} حقل</span>
        @if ($totalRows > $limit)
            <span>يُعرض أول {{ number_format($limit) }} صف — صدّر الملف إلى Excel لعرض كل البيانات.</span>
        @endif
    </div>
@else
    <div class="empty-state">
        <span class="eg-icon lg"><i class="fas fa-keyboard"></i></span>
        <h5>لم تُدخل بيانات بعد</h5>
        <p class="mb-0">أدخل البيانات يدوياً أو ارفعها من قالب Excel.</p>
    </div>
@endif
