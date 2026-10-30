{{-- أداء الجهات التابعة --}}
<div class="card-kfs h-100" data-reveal>
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-ranking-star ml-2"></i>{{ $title ?? 'أداء الجهات التابعة' }}</span>
        @can('view_map')<a href="{{ route('map.index') }}" class="small fw-bold">على الخريطة <i class="fas fa-arrow-left mr-1 text-gold"></i></a>@endcan
    </div>
    @if ($unitPerformance->isEmpty())
        <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-sitemap"></i></span><p class="mb-0 small">لا توجد جهات تابعة ضمن نطاقك.</p></div>
    @else
        <div class="table-responsive">
            <table class="table table-kfs mb-0">
                <thead><tr><th>الجهة</th><th>الإرسالات</th><th class="d-none d-md-table-cell">قيد المراجعة</th><th style="min-width:160px">نسبة الاعتماد</th></tr></thead>
                <tbody>
                    @foreach ($unitPerformance as $u)
                        <tr>
                            <td>{{ $u->name }}<span class="sub">{{ $u->type }}</span></td>
                            <td class="fw-bold">{{ number_format($u->total) }}</td>
                            <td class="d-none d-md-table-cell">{{ number_format($u->review) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2" title="{{ $u->done }} معتمد من {{ $u->total }}">
                                    <div class="progress flex-grow-1"><div class="progress-bar" style="width: {{ $u->rate }}%; background: var(--{{ ['success' => 'success', 'pending' => 'warning', 'error' => 'danger', 'draft' => 'muted'][$u->state] }})"></div></div>
                                    <small class="fw-bold" style="min-width:36px">{{ $u->total ? $u->rate . '%' : '—' }}</small>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
