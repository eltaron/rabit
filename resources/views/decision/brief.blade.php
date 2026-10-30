@php
    $k = $brief['kpis'];
    $f = fn ($n, $d = 0) => $n === null ? '—' : number_format((float) $n, $d);
    $insights = collect($brief['insights']);
    $priority = $insights->whereIn('severity', ['critical', 'warning']);
    $centers = collect($brief['centers']);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>الموجز التنفيذي — محافظة كفر الشيخ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/kfs-royal.css') }}?v={{ config('app.asset_version') }}" rel="stylesheet">
    <style>
        body { background: var(--sand); padding: 24px 12px; font-size: 10.5pt; line-height: 1.75; }
        .sheet { background: var(--white-soft); max-width: 210mm; margin: 0 auto; padding: 16mm 15mm; box-shadow: var(--shadow-lg); }
        .doc-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-bottom: 10px; }
        .doc-head img { height: 48px; }
        .doc-head .gov { font-family: var(--ff-ar); color: var(--egypt-green); line-height: 1.5; }
        .doc-head .gov strong { display: block; font-size: 1.05rem; }
        .doc-head .meta { text-align: left; font-size: .78rem; color: var(--text-soft); }
        h1 { font-size: 1.45rem; margin: 14px 0 2px; }
        h2 { font-size: 1.02rem; margin: 18px 0 8px; display: flex; align-items: center; gap: 8px; }
        h2::before { content: ""; width: 28px; height: 2px; background: var(--egypt-gold); }
        .lead { color: var(--text-soft); margin: 0 0 6px; font-size: .85rem; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--line); border-radius: 6px; }
        .kpis div { padding: 8px 10px; border-left: 1px solid var(--line-gold); }
        .kpis div:last-child { border-left: 0; }
        .kpis b { display: block; font-size: 1.15rem; color: var(--egypt-green); font-family: var(--ff-ar); }
        .kpis span { font-size: .74rem; color: var(--text-soft); }
        table { width: 100%; border-collapse: collapse; font-size: .78rem; }
        th { background: var(--egypt-green); color: var(--white-soft); font-weight: 600; padding: 5px 6px; text-align: right; font-family: var(--ff-ar); }
        td { border-bottom: 1px solid var(--line); padding: 4px 6px; }
        tr:nth-child(even) td { background: var(--ivory); }
        .num { font-variant-numeric: lining-nums; white-space: nowrap; }
        .low { color: var(--danger); font-weight: 700; }
        .pri { border-right: 3px solid var(--warning); padding: 6px 10px; margin-bottom: 8px; background: var(--ivory); break-inside: avoid; }
        .pri.critical { border-right-color: var(--danger); }
        .pri .t { font-weight: 700; color: var(--egypt-green); }
        .pri .s { font-size: .72rem; color: var(--text-soft); }
        .pri .r { margin-top: 2px; }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 28px; font-size: .82rem; }
        .sign div { border-top: 1px solid var(--line-gold); padding-top: 6px; text-align: center; color: var(--text-soft); }
        .foot { margin-top: 18px; font-size: .7rem; color: var(--muted); text-align: center; }
        .toolbar { max-width: 210mm; margin: 0 auto 12px; display: flex; gap: 8px; justify-content: flex-end; }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; padding: 0; max-width: none; }
            .toolbar { display: none !important; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr, .pri { break-inside: avoid; }
        }
        @media (max-width: 640px) { .kpis { grid-template-columns: repeat(2, 1fr); } .sheet { padding: 18px 14px; } .table-wrap { overflow-x: auto; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('decision.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-right ml-1"></i>العودة</a>
        <button type="button" class="btn btn-kfs btn-sm" onclick="window.print()"><i class="fas fa-print ml-1"></i>طباعة / حفظ PDF</button>
    </div>

    <main class="sheet">
        <div class="doc-head">
            <div class="gov">
                <strong>محافظة كفر الشيخ</strong>
                ديوان عام المحافظة — منصة رابط لدعم القرار
            </div>
            <div class="meta">
                التاريخ: {{ now()->locale('ar')->translatedFormat('j F Y') }}<br>
                أُعدّ لـ: {{ $user->name }}<br>
                سري — للاستخدام الرسمي
            </div>
        </div>
        <div class="egypt-cornice"></div>

        <h1>الموجز التنفيذي للوضع الراهن</h1>
        <p class="lead">{{ $brief['scope_note'] }} — بيانات محدّثة حتى {{ $brief['generated_label'] }}</p>

        <h2>المؤشرات الرئيسية</h2>
        <div class="kpis">
            <div><b class="num">{{ $f($k['population']) }}</b><span>نسمة — {{ $k['centers'] }} مركز ومدينة</span></div>
            <div><b class="num">{{ $f($k['local_units']) }} / {{ $f($k['villages']) }}</b><span>وحدة محلية / قرية تابعة</span></div>
            <div><b class="num">{{ $f($k['records']) }}</b><span>سجل مكاني ووصفي</span></div>
            <div><b class="num">{{ $f($k['submissions']) }}</b><span>إرسال بيانات ({{ $f($k['pending_review']) }} قيد المراجعة)</span></div>
            <div><b class="num">{{ $f($k['schools']) }}</b><span>مدرسة</span></div>
            <div><b class="num">{{ $f($k['health']) }}</b><span>منشأة صحية مسجلة</span></div>
            <div><b class="num">{{ $f($k['vehicles_broken']) }} / {{ $f($k['vehicles']) }}</b><span>مركبة معطلة / الأسطول</span></div>
            <div><b class="num">{{ $f($k['maintenance_rate'], 1) }}%</b><span>تنفيذ خطة الصيانة</span></div>
        </div>

        <h2>الأولويات التي تتطلب قراراً</h2>
        @forelse ($priority as $i)
            <div class="pri {{ $i['severity'] }}">
                <div class="t">{{ $loop->iteration }}. {{ $i['title'] }}</div>
                <div class="s">{{ $i['severity_label'] }} — {{ $i['area'] }}@if ($i['evidence']) — {{ collect($i['evidence'])->map(fn ($v, $kk) => "$kk: $v")->implode('، ') }}@endif</div>
                <div>{{ $i['detail'] }}</div>
                <div class="r"><strong>التوصية:</strong> {{ $i['rec'] }}</div>
            </div>
        @empty
            <p>لا توجد أولويات حرجة وفق البيانات المتاحة.</p>
        @endforelse

        <h2>مقارنة المراكز</h2>
        @php
            $cols = ['schools_per100k' => 'مدارس/100 ألف', 'health_per100k' => 'صحة/100 ألف', 'bakeries_per100k' => 'مخابز/100 ألف', 'outlets_per100k' => 'منافذ/100 ألف', 'unfit_buildings' => 'آيلة للسقوط', 'vehicles_broken' => 'مركبات معطلة', 'maintenance_rate' => 'صيانة %'];
            $avgs = collect($cols)->map(fn ($l, $key) => $centers->whereNotNull($key)->avg($key));
        @endphp
        <div class="table-wrap">
            <table>
                <thead><tr><th>المركز</th><th>السكان</th>@foreach ($cols as $l)<th>{{ $l }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($centers as $c)
                        <tr>
                            <td>{{ $c['name'] }}</td>
                            <td class="num">{{ $f($c['population']) }}</td>
                            @foreach ($cols as $key => $l)
                                @php
                                    $hb = $indicators[$key][2] ?? ($key === 'vehicles_broken' ? false : true);
                                    $isLow = $c[$key] !== null && $avgs[$key] && ($hb ? $c[$key] < $avgs[$key] * .7 : $c[$key] > $avgs[$key] * 1.3);
                                @endphp
                                <td class="num {{ $isLow ? 'low' : '' }}">{{ $f($c[$key], str_ends_with($key, '100k') ? 1 : 0) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="lead" style="margin-top:4px">القيم باللون الأحمر أقل من 70% من متوسط المراكز (أو أعلى بـ30% للمؤشرات السلبية).</p>

        @if ($insights->whereIn('severity', ['info', 'positive'])->isNotEmpty())
            <h2>ملاحظات إضافية</h2>
            <ul>
                @foreach ($insights->whereIn('severity', ['info', 'positive']) as $i)
                    <li><strong>{{ $i['title'] }}:</strong> {{ $i['rec'] }}</li>
                @endforeach
            </ul>
        @endif

        <h2>حداثة مصادر البيانات</h2>
        <p class="lead">{{ collect($brief['freshness'])->map(fn ($s) => $s['source'] . ': ' . $s['label'])->implode(' · ') }}</p>

        <div class="sign">
            <div>إعداد: وحدة دعم القرار ونظم المعلومات</div>
            <div>اعتماد: ....................................</div>
        </div>
        <div class="foot">مستخرج آلياً من منصة رابط — البوابة الإلكترونية الرسمية لمحافظة كفر الشيخ · جمهورية مصر العربية</div>
    </main>
</body>
</html>
