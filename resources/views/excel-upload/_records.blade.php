{{-- جدول سجلات قاعدة بيانات (قطاعية / نوتة معلومات) مع التصفية والتفاصيل --}}
<div class="card-kfs">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-database ml-2"></i>السجلات</span>
        <span class="badge badge-gold">{{ number_format($data->total()) }}</span>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3" role="search">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو العنوان أو المركز…" value="{{ request('search') }}" aria-label="بحث">
                </div>
            </div>
            @if (! empty($sheets) && count($sheets))
                <div class="col-md-2">
                    <select name="sheet" class="form-select" aria-label="الورقة">
                        <option value="">كل الأوراق</option>
                        @foreach ($sheets as $s)<option @selected(request('sheet') === $s)>{{ $s }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <select name="center" class="form-select" aria-label="المركز">
                    <option value="">كل المراكز</option>
                    @foreach ($centers as $c)<option value="{{ $c }}" @selected(request('center') === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-kfs-primary flex-grow-1"><i class="fas fa-filter ml-1"></i>تصفية</button>
                @if (request()->hasAny(['search', 'sheet', 'center']))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-icon" title="مسح" aria-label="مسح التصفية"><i class="fas fa-xmark"></i></a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-kfs mb-0">
                <thead>
                    <tr>
                        <th>م</th><th>الاسم</th><th>المركز</th><th class="d-none d-md-table-cell">{{ $typeCol }}</th>
                        <th class="d-none d-lg-table-cell">العنوان</th><th>الموقع</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $row)
                        @php
                            $extra = $row->extra_data ?? [];
                            $lat = $row->latitude ?? null; $lng = $row->longitude ?? null;
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $data->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $row->name ?? '—' }}</td>
                            <td>
                                @if ($row->center_name)
                                    <span class="badge-status {{ $row->center_id ? 'status-success' : 'badge-draft' }}" title="{{ $row->center_id ? 'مرتبط بمركز في الهيكل' : 'غير مرتبط' }}">{{ $row->center_name }}</span>
                                @else — @endif
                            </td>
                            <td class="d-none d-md-table-cell small">{{ $typeOf($row) ?? '—' }}</td>
                            <td class="d-none d-lg-table-cell small">{{ \Illuminate\Support\Str::limit($row->address ?? '—', 60) }}</td>
                            <td>
                                @if ($lat && $lng)
                                    @can('view_map')
                                        <a href="{{ route('map.index', ['lat' => $lat, 'lng' => $lng, 'zoom' => 16]) }}" class="small" title="{{ $lat }}, {{ $lng }}"><i class="fas fa-location-dot text-gold ml-1"></i>على الخريطة</a>
                                    @else
                                        <small class="en text-muted">{{ round($lat, 4) }}, {{ round($lng, 4) }}</small>
                                    @endcan
                                @else
                                    <small class="text-muted">بدون إحداثيات</small>
                                @endif
                            </td>
                            <td>
                                @if (! empty($extra))
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="collapse" data-bs-target="#rec-{{ $row->id }}" aria-expanded="false" aria-label="التفاصيل"><i class="fas fa-chevron-down"></i></button>
                                @endif
                            </td>
                        </tr>
                        @if (! empty($extra))
                            <tr class="collapse" id="rec-{{ $row->id }}">
                                <td colspan="7" style="background:var(--papyrus)">
                                    <dl class="row mb-0 small">
                                        @foreach ($extra as $k => $v)
                                            <dt class="col-sm-3 text-muted fw-normal">{{ $k }}</dt>
                                            <dd class="col-sm-9 mb-1">{{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</dd>
                                        @endforeach
                                    </dl>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="7">
                            <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-inbox"></i></span><p class="small mb-0">لا توجد سجلات مطابقة.</p></div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($data->hasPages())<div class="mt-3">{{ $data->withQueryString()->links() }}</div>@endif
    </div>
</div>
