@extends('layouts.app')

@section('title', $template->name)
@section('page-title', $template->name)
@section('page-subtitle', $template->description ?: 'نموذج جمع بيانات')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('forms.templates.index') }}">النماذج</a></li>
    <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($template->name, 40) }}</li>
@endsection

@section('page-actions')
    @can('import_export_excel')
        <a href="{{ route('excel.template.download', $template) }}" class="btn btn-kfs-gold"><i class="fas fa-file-arrow-down ml-1"></i>تحميل قالب Excel</a>
    @endcan
    <a href="{{ route('forms.templates.edit', $template) }}" class="btn btn-kfs-gold"><i class="fas fa-pen ml-1"></i>تعديل</a>
    <form method="POST" action="{{ route('forms.templates.duplicate', $template) }}" class="d-inline">
        @csrf
        <button class="btn btn-kfs-gold"><i class="fas fa-copy ml-1"></i>نسخ</button>
    </form>
@endsection

@section('content')
    @include('excel._import-errors')

    <div class="gov-stats mb-4">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-table-columns"></i></div><div class="gs-num">{{ $template->formFields->where('is_visible', true)->count() }}</div><div class="gs-label">حقل / عمود</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-inbox"></i></div><div class="gs-num">{{ number_format($totalSubmissions) }}</div><div class="gs-label">إرسال ضمن نطاقك</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-hourglass-half"></i></div><div class="gs-num">{{ number_format($statusCounts['under_review'] ?? 0) }}</div><div class="gs-label">قيد المراجعة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-circle-check"></i></div><div class="gs-num">{{ number_format($statusCounts['approved'] ?? 0) }}</div><div class="gs-label">معتمد</div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- التداول --}}
            <div class="card-kfs mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="fas fa-share-nodes ml-2"></i>تداول النموذج بين الجهات</span>
                    <span class="badge badge-gold">{{ $distributions[$template->distribution] ?? $template->distribution }}</span>
                </div>
                @if ($template->distribution !== 'targeted')
                    <div class="card-body text-muted">
                        @if ($template->distribution === 'all')
                            النموذج متاح لكل جهات المحافظة لإدخال بياناتها.
                        @else
                            النموذج متاح للجهة المالكة ({{ $template->orgUnit->name ?? 'ديوان عام المحافظة' }}) والجهات التابعة لها فقط.
                        @endif
                    </div>
                @elseif ($coverage->isEmpty())
                    <div class="card-body text-muted">لم تُحدد جهات بعد.</div>
                @else
                    @php
                        $responded = $coverage->where('total', '>', 0)->count();
                    @endphp
                    <div class="card-body pb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>استجابة الجهات</span>
                            <span class="fw-bold">{{ $responded }} / {{ $coverage->count() }}</span>
                        </div>
                        <div class="progress mb-2"><div class="progress-bar" style="width: {{ $coverage->count() ? round($responded / $coverage->count() * 100) : 0 }}%"></div></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-kfs mb-0">
                            <thead>
                                <tr><th>الجهة</th><th>موعد التسليم</th><th>إرسالات</th><th>مُقدمة</th><th>معتمدة</th><th>الحالة</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($coverage as $c)
                                    <tr>
                                        <td>
                                            <i class="fas {{ $c['unit']->icon }} ml-1 text-gold"></i>{{ $c['unit']->name }}
                                            @if ($c['include_children'])<small class="text-muted d-block">تشمل الجهات التابعة</small>@endif
                                        </td>
                                        <td class="{{ $c['overdue'] ? 'text-danger fw-bold' : '' }}">{{ $c['due']?->format('Y-m-d') ?? '—' }}</td>
                                        <td>{{ $c['total'] }}</td>
                                        <td>{{ $c['submitted'] }}</td>
                                        <td>{{ $c['approved'] }}</td>
                                        <td>
                                            @if ($c['overdue'])
                                                <span class="badge-status status-error">متأخرة</span>
                                            @elseif ($c['approved'])
                                                <span class="badge-status status-success">مكتملة</span>
                                            @elseif ($c['submitted'])
                                                <span class="badge-status status-info">قيد المراجعة</span>
                                            @elseif ($c['total'])
                                                <span class="badge-status status-pending">جاري الإدخال</span>
                                            @else
                                                <span class="badge-status badge-draft">لم تبدأ</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- الحقول --}}
            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-table-columns ml-2"></i>حقول النموذج ({{ $template->formFields->count() }})</div>
                <div class="table-responsive">
                    <table class="table table-kfs mb-0">
                        <thead><tr><th>#</th><th>العنوان</th><th>المفتاح</th><th>النوع</th><th>إلزامي</th><th>الخيارات</th></tr></thead>
                        <tbody>
                            @forelse ($template->formFields as $i => $field)
                                <tr class="{{ $field->is_visible ? '' : 'text-muted' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-bold">{{ $field->label }} @unless ($field->is_visible)<small>(مخفي)</small>@endunless</td>
                                    <td class="en small">{{ $field->name }}</td>
                                    <td><span class="badge badge-green">{{ $fieldTypes[$field->type] ?? $field->type }}</span></td>
                                    <td>@if ($field->is_required)<i class="fas fa-check text-success" aria-label="نعم"></i>@else<span class="text-muted">—</span>@endif</td>
                                    <td class="small text-muted">{{ $field->options ? implode('، ', (array) $field->options) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد حقول — <a href="{{ route('forms.templates.edit', $template) }}">أضف الحقول</a></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- أحدث الإرسالات --}}
            <div class="card-kfs">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="fas fa-inbox ml-2"></i>أحدث الإرسالات</span>
                    <a href="{{ route('submissions.index', ['form_template_id' => $template->id]) }}" class="small">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-kfs mb-0">
                        <thead><tr><th>المرجع</th><th>الجهة</th><th>المُنشئ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                        <tbody>
                            @forelse ($recent as $s)
                                <tr>
                                    <td><a href="{{ route('submissions.show', $s) }}" class="fw-bold en">{{ $s->reference_number }}</a></td>
                                    <td>{{ $s->orgUnit->name ?? '—' }}</td>
                                    <td>{{ $s->createdBy->name ?? '—' }}</td>
                                    <td>@include('partials._status-badge', ['status' => $s->status])</td>
                                    <td class="small text-muted">{{ $s->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">لا توجد إرسالات بعد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-kfs mb-4 egypt-frame">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="fas fa-file-excel ml-2 text-gold"></i>إدخال البيانات</h5>
                    <div class="d-grid gap-2">
                        @can('create_submissions')
                            <a href="{{ route('submissions.create', ['form_template_id' => $template->id]) }}" class="btn btn-kfs"><i class="fas fa-keyboard ml-1"></i>إدخال يدوي (إرسال جديد)</a>
                        @endcan
                        @can('import_export_excel')
                            <a href="{{ route('excel.template.download', $template) }}" class="btn btn-kfs-gold"><i class="fas fa-file-arrow-down ml-1"></i>تحميل قالب Excel</a>
                            @can('create_submissions')
                                <button type="button" class="btn btn-outline-navy" data-bs-toggle="modal" data-bs-target="#uploadNew"><i class="fas fa-file-arrow-up ml-1"></i>رفع ملف كإرسال جديد</button>
                            @endcan
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-circle-info ml-2"></i>معلومات</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">الحالة</span>@include('partials._status-badge', ['status' => $template->is_active ? 'active' : 'inactive'])</li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">الجهة المالكة</span><span>{{ $template->orgUnit->name ?? 'ديوان عام المحافظة' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">الاعتماد</span><span>{{ $template->requires_approval ? 'يتطلب مراجعة واعتماد' : 'بدون اعتماد' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">البيانات المكانية</span>
                        <span>{{ $template->geo_lat_field && $template->geo_lng_field ? 'مربوطة بالخريطة' : 'غير مربوطة' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">أنشأه</span><span>{{ $template->createdBy->name ?? '—' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">آخر تعديل</span><span>{{ $template->updated_at?->diffForHumans() }}</span></li>
                </ul>
            </div>

            @if ($totalSubmissions)
                <div class="card-kfs">
                    <div class="card-header"><i class="fas fa-chart-simple ml-2"></i>توزيع الحالات</div>
                    <div class="card-body">
                        @foreach ($statusCounts as $status => $count)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                @include('partials._status-badge', ['status' => $status])
                                <span class="fw-bold">{{ $count }}</span>
                            </div>
                            <div class="progress mb-3" style="height:4px"><div class="progress-bar" style="width: {{ round($count / $totalSubmissions * 100) }}%"></div></div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @can('import_export_excel')
        @include('excel._upload-modal', [
            'id' => 'uploadNew',
            'action' => route('excel.template.import', $template),
            'title' => 'رفع بيانات: ' . $template->name,
            'subtitle' => 'سيتم إنشاء إرسال جديد باسم جهتك يحتوي على صفوف الملف.',
            'withMode' => false,
            'modes' => $modes,
        ])
    @endcan
@endsection
