<div>
    <div class="gov-stats mb-3">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-inbox"></i></div><div class="gs-num">{{ number_format($kpiData['total_submissions'] ?? 0) }}</div><div class="gs-label">إجمالي الإرسالات</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-circle-check"></i></div><div class="gs-num">{{ $kpiData['approval_rate'] ?? 0 }}%</div><div class="gs-label">نسبة الاعتماد</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-rotate-left"></i></div><div class="gs-num">{{ $kpiData['revision_rate'] ?? 0 }}%</div><div class="gs-label">نسبة طلب التعديل</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-stopwatch"></i></div><div class="gs-num">{{ $kpiData['avg_processing_days'] ?? 0 }}</div><div class="gs-label">متوسط أيام المعالجة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-chart-column"></i></div><div class="gs-num">{{ number_format($kpiData['total_reports_generated'] ?? 0) }}</div><div class="gs-label">تقارير منشأة</div></div>
    </div>
    @if (! empty($kpiData['department_stats']))
        @php $stats = collect($kpiData['department_stats']); $max = max(1, $stats->max('count')); @endphp
        <div class="card-kfs">
            <div class="card-header"><i class="fas fa-building ml-2"></i>الإرسالات حسب المديرية <small class="text-muted fw-normal">— الأعلى: {{ $kpiData['top_department'] ?? '—' }}</small></div>
            <div class="card-body">
                @foreach ($stats as $d)
                    @php $d = (array) $d; @endphp
                    <div class="d-grid align-items-center gap-2 py-1" style="grid-template-columns:150px 1fr 50px">
                        <span class="small text-truncate">{{ $d['name'] ?? '' }}</span>
                        <div class="progress"><div class="progress-bar" style="width: {{ ($d['count'] ?? 0) / $max * 100 }}%"></div></div>
                        <span class="small fw-bold text-start">{{ number_format($d['count'] ?? 0) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
