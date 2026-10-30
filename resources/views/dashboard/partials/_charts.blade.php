{{-- توزيع الحالات + الاتجاه الشهري (سلسلة واحدة، خانات حالة محجوزة مع تسمية نصية) --}}
@php
    $series = $monthly ?? [];
    $maxM = max(1, collect($series)->max('value'));
    $trendTitle = $trendTitle ?? 'الإرسالات خلال آخر 6 أشهر';
@endphp
<div class="row g-4 mb-4">
    <div class="col-lg-5" data-reveal>
        <div class="card-kfs h-100">
            <div class="card-header"><i class="fas fa-list-check ml-2"></i>{{ $breakdownTitle ?? 'حالة الإرسالات' }}</div>
            <div class="card-body">
                @foreach ($statusBreakdown as $r)
                    <div class="hbar" title="{{ $r['label'] }}: {{ number_format($r['value']) }} ({{ $r['pct'] }}%)">
                        <span class="lbl"><span class="badge-status status-{{ $r['class'] }}" style="padding:.1rem .4rem">{{ $r['label'] }}</span></span>
                        <div class="track" role="img" aria-label="{{ $r['label'] }} {{ $r['value'] }}"><div class="fill {{ $r['class'] }}" style="width: {{ $r['pct'] }}%"></div></div>
                        <span class="val">{{ number_format($r['value']) }}</span>
                    </div>
                @endforeach
                <div class="text-muted small mt-2">الإجمالي: <strong class="text-green">{{ number_format(collect($statusBreakdown)->sum('value')) }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7" data-reveal data-delay=".08">
        <div class="card-kfs h-100">
            <div class="card-header"><i class="fas fa-chart-column ml-2"></i>{{ $trendTitle }}</div>
            <div class="card-body">
                <div class="vchart" role="img" aria-label="{{ $trendTitle }}">
                    @foreach ([.25, .5, .75] as $g)<span class="gl" style="bottom: {{ $g * 100 }}%"></span>@endforeach
                    @foreach ($series as $m)
                        <div class="col" tabindex="0">
                            <span class="tip">{{ $m['label'] }}: {{ number_format($m['value']) }}</span>
                            <div class="bar" style="height: {{ max(1, $m['value'] / $maxM * 100) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="vchart-x">@foreach ($series as $m)<span>{{ $m['label'] }}</span>@endforeach</div>
                <table class="visually-hidden"><caption>{{ $trendTitle }}</caption>
                    @foreach ($series as $m)<tr><th>{{ $m['label'] }}</th><td>{{ $m['value'] }}</td></tr>@endforeach
                </table>
            </div>
        </div>
    </div>
</div>
