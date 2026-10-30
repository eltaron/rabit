@extends('layouts.app')

@section('title', $report->title)
@section('page-title', $report->title)
@section('page-subtitle', ($report->formSubmission->formTemplate->name ?? 'تقرير') . ' — الإصدار ' . $report->current_version)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($report->title, 40) }}</li>
@endsection

@section('page-actions')
    @can('manage_reports')
        <a href="{{ route('reports.edit', $report) }}" class="btn btn-kfs-primary"><i class="fas fa-pen-to-square ml-1"></i>تعديل</a>
    @endcan
    <button type="button" onclick="window.print()" class="btn btn-kfs-gold"><i class="fas fa-print ml-1"></i>طباعة</button>
    <a href="{{ route('reports.pdf', $report) }}" target="_blank" class="btn btn-kfs-gold"><i class="fas fa-file-pdf ml-1"></i>PDF</a>
    <a href="{{ route('reports.excel', $report) }}" class="btn btn-kfs-gold"><i class="fas fa-file-excel ml-1"></i>Excel</a>
@endsection

@push('styles')
<style>
    .rep-meta { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; }
    .rep-meta dt { font-size:.75rem; color:var(--text-soft); font-weight:500; }
    .rep-meta dd { margin:0; font-weight:600; color:var(--charcoal); }
    .kpi-tile { border:1px solid var(--line); border-top:2px solid var(--egypt-gold); border-radius:8px; padding:.9rem 1rem; background:var(--white-soft); height:100%; }
    .kpi-tile .k-label { font-size:.75rem; color:var(--text-soft); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .kpi-tile .k-val { font-size:1.4rem; font-weight:800; color:var(--egypt-green); line-height:1.2; font-variant-numeric:lining-nums; }
    .kpi-tile i { color:var(--egypt-gold); }
    .chart-card { border:1px solid var(--line); border-radius:8px; background:var(--white-soft); padding:1rem; height:100%; }
    .chart-type-btn { font-size:.7rem; padding:.15rem .5rem; color:var(--egypt-green); border:1px solid var(--line); background:transparent; }
    .chart-type-btn:hover { border-color:var(--egypt-gold); }
    .chart-type-btn.active { background:var(--egypt-green); color:var(--white-soft); border-color:var(--egypt-green); }
    .rep-content { line-height:2.1; background:var(--papyrus); border:1px solid var(--line); border-radius:var(--r); padding:1.25rem 1.5rem; min-height:120px; }
    .rep-ai { line-height:2.1; border:1px solid var(--line-gold); border-right:3px solid var(--egypt-gold); border-radius:var(--r); padding:1.25rem 1.5rem; background:var(--white-soft); }
    @media print {
        .col-lg-8, .col-lg-4 { width:100% !important; max-width:100% !important; flex:0 0 100% !important; }
        .row { display:block !important; }
        .table-responsive { overflow:visible !important; }
        .chart-type-group, .rep-actions { display:none !important; }
        .card-kfs { break-inside:avoid; }
    }
</style>
@endpush

@section('content')
@php
    $chartTypeLabels = ['bar' => 'أعمدة', 'line' => 'خطي', 'doughnut' => 'حلقي', 'pie' => 'دائري', 'radar' => 'راداري', 'polarArea' => 'قطبي'];
@endphp
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-kfs mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-file-lines ml-2"></i>بيانات التقرير <span class="badge badge-green en mr-1">v{{ $report->current_version }}</span></span>
                @include('dashboard.partials._status', ['status' => $report->status])
            </div>
            <div class="card-body">
                <dl class="rep-meta mb-0">
                    <div><dt>الإرسال المصدر</dt><dd>
                        @if ($report->formSubmission)
                            <a href="{{ route('submissions.show', $report->formSubmission) }}" class="en">{{ $report->formSubmission->reference_number }}</a>
                        @else — @endif
                    </dd></div>
                    <div><dt>النموذج</dt><dd>{{ $report->formSubmission->formTemplate->name ?? '—' }}</dd></div>
                    <div><dt>أنشأه</dt><dd>{{ $report->createdBy->name ?? '—' }}</dd></div>
                    <div><dt>تاريخ الإنشاء</dt><dd>{{ $report->created_at->format('Y/m/d H:i') }}</dd></div>
                    @if ($report->approvedBy)
                        <div><dt>اعتمده</dt><dd>{{ $report->approvedBy->name }} <small class="text-muted">{{ $report->approved_at?->format('Y/m/d') }}</small></dd></div>
                    @endif
                </dl>
                @if ($report->status === 'rejected' && $report->rejection_notes)
                    <div class="alert alert-danger small mt-3 mb-0"><strong>سبب الرفض:</strong> {{ $report->rejection_notes }}</div>
                @endif
            </div>
        </div>

        @if (count($visuals['kpis']))
            <div class="section-head">
                <div class="sh-line"><span class="sh-num">01</span></div>
                <h3>مؤشرات سريعة</h3>
            </div>
            <div class="row g-3 mb-4">
                @foreach ($visuals['kpis'] as $kpi)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="kpi-tile">
                            <div class="k-label"><i class="fas {{ $kpi['icon'] }} ml-1"></i>{{ $kpi['label'] }}</div>
                            <div class="k-val">{{ $kpi['value'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($visuals['charts']))
            <div class="section-head">
                <div class="sh-line"><span class="sh-num">02</span></div>
                <h3>الرسوم البيانية</h3>
                <p>مرّر المؤشر على العناصر لعرض القيم؛ البيانات الكاملة في الجدول أدناه.</p>
            </div>
            <div class="row g-3 mb-4" id="reportCharts">
                @foreach ($visuals['charts'] as $chart)
                    <div class="col-md-6">
                        <figure class="chart-card mb-0">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <figcaption class="fw-bold" style="color:var(--egypt-green);font-size:.9rem">{{ $chart['title'] }}</figcaption>
                                <div class="btn-group btn-group-sm chart-type-group" role="group" aria-label="نوع الرسم">
                                    @foreach ($chart['types'] as $type)
                                        <button type="button" class="btn chart-type-btn {{ $loop->first ? 'active' : '' }}" data-chart-key="{{ $chart['key'] }}" data-type="{{ $type }}">
                                            {{ $chartTypeLabels[$type] ?? $type }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            @if ($chart['summary'])<small class="text-muted d-block mb-2">{{ $chart['summary'] }}</small>@endif
                            <div style="height:260px;position:relative"><canvas data-chart-key="{{ $chart['key'] }}" role="img" aria-label="{{ $chart['title'] }}"></canvas></div>
                            <script type="application/json" id="chart-{{ $chart['key'] }}">@json($chart)</script>
                        </figure>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($rows->count() && count($headers))
            <div class="card-kfs mb-4">
                <div class="card-header d-flex justify-content-between"><span><i class="fas fa-table ml-2"></i>بيانات الإرسال</span><span class="badge badge-gold">{{ number_format($rows->count()) }} صف</span></div>
                <div class="table-responsive" style="max-height:520px">
                    <table class="table table-kfs mb-0" style="font-size:.82rem">
                        <thead style="position:sticky;top:0;z-index:1"><tr><th style="width:45px">#</th>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                <tr><td class="text-muted">{{ $i + 1 }}</td>@foreach ($headers as $h)<td>{{ $row[$h] ?? '—' }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card-kfs mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-align-right ml-2"></i>محتوى التقرير</span>
                @can('manage_reports')<a href="{{ route('reports.edit', $report) }}" class="btn btn-sm btn-kfs-gold rep-actions"><i class="fas fa-pen ml-1"></i>تعديل</a>@endcan
            </div>
            <div class="card-body">
                @if ($report->content)
                    <div class="rep-content">{!! nl2br(e($report->content)) !!}</div>
                @else
                    <div class="empty-state py-4">
                        <span class="eg-icon"><i class="fas fa-file-pen"></i></span>
                        <p class="mb-2 small">لم يُكتب محتوى التقرير بعد.</p>
                        @can('manage_reports')<a href="{{ route('reports.edit', $report) }}" class="btn btn-sm btn-kfs-gold rep-actions">إضافة محتوى</a>@endcan
                    </div>
                @endif

                @if ($report->ai_generated_content)
                    <h6 class="mt-4 mb-2"><i class="fas fa-wand-magic-sparkles ml-1 text-gold"></i>ملخص مولّد بالذكاء الاصطناعي</h6>
                    <div class="rep-ai">{!! nl2br(e($report->ai_generated_content)) !!}</div>
                @endif
            </div>
        </div>

        {{-- إجراءات الاعتماد والإرسال --}}
        @if ($report->status === 'pending_approval' && auth()->user()->can('approve_reports'))
            <div class="card-kfs mb-4 rep-actions">
                <div class="card-header"><i class="fas fa-stamp ml-2"></i>اعتماد التقرير</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="POST" action="{{ route('reports.approve', $report) }}">@csrf
                                <button class="btn btn-success w-100"><i class="fas fa-check ml-1"></i>اعتماد</button>
                            </form>
                        </div>
                        <div class="col-md-8">
                            <form method="POST" action="{{ route('reports.reject', $report) }}" class="d-flex gap-2">@csrf
                                <input type="text" name="rejection_notes" class="form-control" placeholder="سبب الرفض (إلزامي)" required maxlength="1000" aria-label="سبب الرفض">
                                <button class="btn btn-outline-danger text-nowrap"><i class="fas fa-xmark ml-1"></i>رفض</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (in_array($report->status, ['approved', 'sent']) && auth()->user()->can('send_reports') && $recipients->isNotEmpty())
            <div class="card-kfs mb-4 rep-actions">
                <div class="card-header"><i class="fas fa-paper-plane ml-2"></i>إرسال التقرير</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('reports.send', $report) }}" class="row g-2 align-items-end">@csrf
                        <div class="col-md-5">
                            <label class="form-label" for="sent_to">المستلم</label>
                            <select id="sent_to" name="sent_to" class="form-select" required>
                                <option value="">— اختر —</option>
                                @foreach ($recipients as $r)<option value="{{ $r->id }}">{{ $r->name }}{{ $r->job_title ? ' — ' . $r->job_title : '' }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="send_notes">ملاحظات</label>
                            <input id="send_notes" type="text" name="notes" class="form-control" maxlength="1000">
                        </div>
                        <div class="col-md-2"><button class="btn btn-kfs-primary w-100">إرسال</button></div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card-kfs mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-code-branch ml-2"></i>الإصدارات</span>
                <a href="{{ route('reports.versions.index', $report) }}" class="small fw-bold rep-actions">السجل الكامل</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($report->versions->sortByDesc('version_number')->take(6) as $version)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge {{ $version->is_current ? 'badge-gold' : 'badge-green' }} en">v{{ $version->version_number }}@if ($version->is_current) · الحالي @endif</span>
                            <small class="text-muted">{{ $version->created_at->format('Y/m/d H:i') }}</small>
                        </div>
                        <small class="text-muted d-block mt-1">{{ $version->createdBy->name ?? '' }}@if ($version->change_notes) — {{ $version->change_notes }}@endif</small>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted small py-4">لا توجد إصدارات</li>
                @endforelse
            </ul>
        </div>

        <div class="card-kfs">
            <div class="card-header"><i class="fas fa-circle-info ml-2"></i>معلومات</div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">الحالة</span>@include('dashboard.partials._status', ['status' => $report->status])</li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">عدد الإصدارات</span><span>{{ $report->versions->count() }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">آخر تعديل</span><span>{{ $report->updated_at->diffForHumans() }}</span></li>
                @if ($report->sent_at)
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">تاريخ الإرسال</span><span>{{ $report->sent_at->format('Y/m/d') }}</span></li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ألوان فئوية مثبتة الترتيب (تم التحقق منها لعمى الألوان) + تسميات وجدول بيانات كبديل
    const PALETTE = ['#1f8a66', '#d9822b', '#2a6fb8', '#e0a800', '#b0457a', '#5b4aa8'];
    const INK = '#303632', MUTED = '#858A84', GRID = 'rgba(18,63,53,.08)', SURFACE = '#FEFDFC';
    const FONT = { family: "'IBM Plex Sans Arabic', Tajawal, sans-serif", size: 11 };
    const instances = {};

    function paint(cfg) {
        const radial = ['pie', 'doughnut', 'polarArea'].includes(cfg.type);
        cfg.datasets = (cfg.datasets || []).map((ds, i) => {
            const d = Object.assign({}, ds);
            if (radial) {
                d.backgroundColor = (cfg.labels || []).map((_, j) => PALETTE[j % PALETTE.length]);
                d.borderColor = SURFACE; d.borderWidth = 2;
            } else {
                const c = PALETTE[i % PALETTE.length];
                d.backgroundColor = cfg.type === 'line' || cfg.type === 'radar' ? c + '22' : c;
                d.borderColor = c; d.borderWidth = cfg.type === 'bar' ? 0 : 2;
                d.borderRadius = cfg.type === 'bar' ? 4 : 0; d.maxBarThickness = 28;
                d.pointRadius = 4; d.pointBackgroundColor = c; d.fill = cfg.type === 'radar';
            }
            return d;
        });
        return radial;
    }

    function render(key) {
        const el = document.getElementById('chart-' + key);
        if (!el || !window.Chart) return;
        const cfg = JSON.parse(el.textContent);
        const radial = paint(cfg);
        const canvas = document.querySelector('canvas[data-chart-key="' + key + '"]');
        if (instances[key]) instances[key].destroy();
        const multi = radial || cfg.datasets.length > 1;
        instances[key] = new Chart(canvas.getContext('2d'), {
            type: cfg.type,
            data: { labels: cfg.labels, datasets: cfg.datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 600 },
                plugins: {
                    legend: { display: multi, position: 'bottom', rtl: true, labels: { color: INK, boxWidth: 12, boxHeight: 12, padding: 12, font: FONT } },
                    tooltip: { rtl: true, backgroundColor: '#0A2924', titleFont: FONT, bodyFont: FONT, borderColor: '#B8954F', borderWidth: 1 }
                },
                scales: (cfg.type === 'bar' || cfg.type === 'line') ? {
                    x: { ticks: { color: MUTED, font: FONT }, grid: { display: false }, border: { color: 'rgba(18,63,53,.2)' } },
                    y: { beginAtZero: true, ticks: { color: MUTED, font: FONT }, grid: { color: GRID }, border: { display: false } }
                } : {}
            }
        });
    }

    document.querySelectorAll('.chart-card').forEach(card => {
        const canvas = card.querySelector('canvas');
        if (!canvas) return;
        const key = canvas.dataset.chartKey;
        render(key);
        card.querySelectorAll('.chart-type-btn').forEach(btn => btn.addEventListener('click', function () {
            card.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const el = document.getElementById('chart-' + key);
            const cfg = JSON.parse(el.textContent);
            cfg.type = this.dataset.type;
            el.textContent = JSON.stringify(cfg);
            render(key);
        }));
    });
})();
</script>
@endpush
