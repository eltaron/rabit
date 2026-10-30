@extends('layouts.app')

@section('title', 'مساعد دعم القرار')
@section('page-title', 'مساعد دعم القرار للقيادات')
@section('page-subtitle', 'البيانات ← المعلومات ← المعرفة ← القرار — ' . $brief['scope_note'])
@section('breadcrumb')
    <li class="breadcrumb-item active">دعم القرار</li>
@endsection

@section('page-actions')
    <a href="{{ route('decision.brief') }}" target="_blank" class="btn btn-kfs-gold"><i class="fas fa-file-lines ml-1"></i>الموجز التنفيذي</a>
    <a href="{{ route('decision.index', ['refresh' => 1]) }}" class="btn btn-kfs-gold"><i class="fas fa-rotate ml-1"></i>تحديث البيانات</a>
@endsection

@php
    $k = $brief['kpis'];
    $f = fn ($n, $d = 0) => $n === null ? '—' : number_format((float) $n, $d);
    $insights = collect($brief['insights']);
    $sevCounts = $insights->countBy('severity');
    $decisions = $insights->whereIn('severity', ['critical', 'warning'])->count();
    $sevIcon = ['critical' => 'fa-circle-exclamation', 'warning' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info', 'positive' => 'fa-circle-check'];
@endphp

@push('styles')
<style>
    .ds-meta { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; align-items: center; color: var(--text-soft); font-size: .8rem; margin-bottom: 1rem; }
    .ds-meta .chip { display: inline-flex; align-items: center; gap: .4rem; padding: .2rem .6rem; border: 1px solid var(--line); border-radius: var(--r); background: var(--white-soft); }
    .ds-meta .chip i { color: var(--egypt-gold); }

    /* DATA → INFORMATION → KNOWLEDGE → DECISION */
    .dikd { display: grid; grid-template-columns: repeat(4, 1fr); position: relative; background: var(--white-soft); border: 1px solid var(--line); border-radius: 8px; box-shadow: var(--shadow); margin: 1.25rem 0 1.5rem; }
    .dikd::before { content: ""; position: absolute; top: 38px; right: 12%; left: 12%; height: 1px; background: var(--egypt-gold); }
    .dikd .step { position: relative; text-align: center; padding: 1.1rem .75rem 1rem; }
    .dikd .step .eg-icon { position: relative; z-index: 1; margin-bottom: .5rem; }
    .dikd .step .en { display: block; font-size: .64rem; letter-spacing: 2px; color: var(--egypt-gold); font-weight: 600; }
    .dikd .step h6 { margin: .1rem 0 .15rem; font-size: .95rem; }
    .dikd .step .v { font-weight: 800; color: var(--egypt-green); font-size: 1.15rem; font-variant-numeric: lining-nums; }
    .dikd .step small { color: var(--text-soft); display: block; font-size: .74rem; }
    @media (max-width: 767.98px) {
        .dikd { grid-template-columns: repeat(2, 1fr); }
        .dikd::before { display: none; }
    }

    /* Insights */
    .ins-filter { display: flex; gap: .4rem; flex-wrap: wrap; }
    .ins-filter button { border: 1px solid var(--line); background: var(--white-soft); color: var(--text); border-radius: var(--r); font-size: .78rem; padding: .25rem .65rem; font-weight: 600; }
    .ins-filter button.active { border-color: var(--egypt-gold); color: var(--egypt-green); background: var(--egypt-gold-soft); }
    .insight { border: 1px solid var(--line); border-right: 3px solid var(--line-gold); border-radius: 8px; background: var(--white-soft); padding: 1rem 1.1rem; margin-bottom: .75rem; }
    .insight.sev-critical { border-right-color: var(--danger); }
    .insight.sev-warning { border-right-color: var(--warning); }
    .insight.sev-info { border-right-color: var(--info); }
    .insight.sev-positive { border-right-color: var(--success); }
    .insight h6 { font-size: .95rem; margin: .35rem 0 .3rem; line-height: 1.6; }
    .insight .detail { color: var(--text); font-size: .85rem; margin-bottom: .5rem; }
    .insight .evidence { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .55rem; }
    .insight .evidence span { font-size: .74rem; background: var(--papyrus); border: 1px solid var(--line); border-radius: 4px; padding: .1rem .45rem; color: var(--text); }
    .insight .evidence b { color: var(--egypt-green); font-weight: 700; }
    .insight .rec { font-size: .84rem; color: var(--egypt-green); background: rgba(18,63,53,.04); border-radius: var(--r); padding: .5rem .7rem; }
    .insight .rec i { color: var(--egypt-gold); }
    .insight .area { font-size: .72rem; color: var(--text-soft); }
    .insight .ask-more { font-size: .76rem; }

    /* Comparison table bars (single-series magnitude: one hue) */
    .cmp-bar { position: relative; height: 10px; background: var(--papyrus); border-radius: 0 4px 4px 0; min-width: 120px; }
    .cmp-bar .fill { position: absolute; top: 0; bottom: 0; right: 0; background: var(--egypt-green-3); border-radius: 4px 0 0 4px; transition: width .7s var(--ease); }
    .cmp-bar .avg { position: absolute; top: -4px; bottom: -4px; width: 2px; background: var(--egypt-gold); }
    .cmp-row:hover .cmp-bar .fill { background: var(--egypt-green); }
    .cmp-legend { font-size: .76rem; color: var(--text-soft); display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
    .cmp-legend .sw { display: inline-block; width: 14px; height: 8px; border-radius: 2px; vertical-align: middle; margin-left: .3rem; }

    /* Chat */
    .ds-chat { position: sticky; top: calc(var(--idbar-height) + var(--navbar-height) + 16px); display: flex; flex-direction: column; height: min(78vh, 760px); }
    .ds-chat .card-body { flex: 1; overflow-y: auto; padding: 1rem; background: var(--ivory); }
    .msg { margin-bottom: .9rem; display: flex; flex-direction: column; }
    .msg .bubble { border-radius: 8px; padding: .65rem .85rem; font-size: .86rem; line-height: 1.85; max-width: 100%; overflow-wrap: anywhere; }
    .msg.user { align-items: flex-start; }
    .msg.user .bubble { background: var(--egypt-green); color: var(--white-soft); max-width: 88%; }
    .msg.assistant { align-items: stretch; }
    .msg.assistant .bubble { background: var(--white-soft); border: 1px solid var(--line); border-top: 2px solid var(--egypt-gold); }
    .msg .bubble h3, .msg .bubble h4 { font-size: .92rem; margin: .6rem 0 .3rem; color: var(--egypt-green); }
    .msg .bubble h3:first-child, .msg .bubble h4:first-child { margin-top: 0; }
    .msg .bubble ul, .msg .bubble ol { padding-right: 1.1rem; padding-left: 0; margin: .2rem 0 .4rem; }
    .msg .bubble hr { margin: .6rem 0; border-color: var(--line); }
    .msg .meta { display: flex; justify-content: space-between; align-items: center; font-size: .7rem; color: var(--muted); margin-top: .25rem; gap: .5rem; }
    .msg .meta button { border: 0; background: none; color: var(--text-soft); font-size: .72rem; padding: 0; }
    .msg .meta button:hover { color: var(--egypt-gold); }
    .src-badge { font-size: .68rem; border: 1px solid var(--egypt-gold-line); color: #7a5f2c; border-radius: 4px; padding: 0 .4rem; }
    .suggest { display: flex; flex-wrap: wrap; gap: .35rem; }
    .suggest button { border: 1px solid var(--egypt-gold-line); background: var(--white-soft); color: var(--egypt-green); font-size: .76rem; border-radius: var(--r); padding: .25rem .6rem; text-align: right; }
    .suggest button:hover { background: var(--egypt-gold-soft); }
    .typing { display: inline-flex; gap: 4px; align-items: center; color: var(--text-soft); font-size: .8rem; }
    .typing span { width: 6px; height: 6px; border-radius: 50%; background: var(--egypt-gold); animation: dsdot 1.2s var(--ease) infinite; }
    .typing span:nth-child(2) { animation-delay: .2s; } .typing span:nth-child(3) { animation-delay: .4s; }
    @keyframes dsdot { 0%, 80%, 100% { opacity: .25; } 40% { opacity: 1; } }
    .ds-chat textarea { resize: none; min-height: 44px; max-height: 140px; }
    @media (max-width: 991.98px) { .ds-chat { position: static; height: 640px; } }
</style>
@endpush

@section('content')
    <div class="ds-meta">
        <span class="chip"><i class="far fa-clock"></i>آخر تحديث للموجز: {{ $brief['generated_label'] }}</span>
        <span class="chip"><i class="fas fa-layer-group"></i>{{ $brief['scope_note'] }}</span>
        <span class="chip">
            <i class="fas fa-microchip"></i>
            @if (count($aiStatus['configured']))
                التحليل اللغوي: {{ implode(' ثم ', $aiStatus['labels']) }}، مع محرك التحليل الداخلي احتياطياً
            @else
                محرك التحليل الداخلي (بدون اتصال خارجي)
            @endif
        </span>
    </div>

    {{-- Government statistics --}}
    <div class="gov-stats" data-reveal>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-people-group"></i></div><div class="gs-num" data-count="{{ (int) $k['population'] }}">{{ $f($k['population']) }}</div><div class="gs-label">نسمة (تعداد المراكز)</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-city"></i></div><div class="gs-num" data-count="{{ $k['centers'] }}">{{ $k['centers'] }}</div><div class="gs-label">مركز ومدينة · {{ $f($k['villages']) }} قرية تابعة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-database"></i></div><div class="gs-num" data-count="{{ $k['records'] }}">{{ $f($k['records']) }}</div><div class="gs-label">سجل مكاني ووصفي</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-truck"></i></div><div class="gs-num">{{ $f($k['vehicles_broken']) }}<small class="text-muted fs-6"> / {{ $f($k['vehicles']) }}</small></div><div class="gs-label">مركبة معطلة من الأسطول</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-screwdriver-wrench"></i></div><div class="gs-num">{{ $f($k['maintenance_rate'], 1) }}%</div><div class="gs-label">تنفيذ خطة الصيانة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-inbox"></i></div><div class="gs-num" data-count="{{ $k['pending_review'] }}">{{ $f($k['pending_review']) }}</div><div class="gs-label">إرسال ينتظر المراجعة · {{ $f($k['overdue_targets']) }} متأخر</div></div>
    </div>

    {{-- DIKD ribbon --}}
    <div class="dikd" aria-label="مسار تحويل البيانات إلى قرار">
        <div class="step"><span class="eg-icon"><i class="fas fa-database"></i></span><span class="en">DATA</span><h6>البيانات</h6><div class="v">{{ $f($k['records'] + $k['submissions']) }}</div><small>سجل من {{ count($brief['freshness']) }} مصادر</small></div>
        <div class="step"><span class="eg-icon"><i class="fas fa-chart-simple"></i></span><span class="en">INFORMATION</span><h6>المعلومات</h6><div class="v">{{ count($indicators) * count($brief['centers']) }}</div><small>مؤشر محسوب لـ{{ count($brief['centers']) }} مراكز</small></div>
        <div class="step"><span class="eg-icon"><i class="fas fa-lightbulb"></i></span><span class="en">KNOWLEDGE</span><h6>المعرفة</h6><div class="v">{{ $insights->count() }}</div><small>استنتاج آلي</small></div>
        <div class="step"><span class="eg-icon"><i class="fas fa-gavel"></i></span><span class="en">DECISION</span><h6>القرار</h6><div class="v">{{ $decisions }}</div><small>توصية تتطلب قراراً</small></div>
    </div>

    <div class="row g-4">
        {{-- Insights --}}
        <div class="col-lg-7">
            <div class="section-head">
                <div class="sh-line"><span class="sh-num">01</span></div>
                <h3>الاستنتاجات والتوصيات</h3>
                <p>مرتبة حسب الأولوية، ولكل استنتاج الأدلة الرقمية والتوصية المقترحة.</p>
            </div>
            <div class="ins-filter mb-3" role="group" aria-label="تصفية حسب الأولوية">
                <button type="button" class="active" data-sev="all">الكل ({{ $insights->count() }})</button>
                @foreach (\App\Services\DecisionSupportService::SEVERITY as $sev => [$label])
                    @if ($sevCounts[$sev] ?? 0)
                        <button type="button" data-sev="{{ $sev }}"><i class="fas {{ $sevIcon[$sev] }} ml-1"></i>{{ $label }} ({{ $sevCounts[$sev] }})</button>
                    @endif
                @endforeach
            </div>

            @forelse ($insights as $i)
                <article class="insight sev-{{ $i['severity'] }}" data-sev="{{ $i['severity'] }}">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <span class="badge-status {{ ['critical' => 'status-error', 'warning' => 'status-pending', 'info' => 'status-info', 'positive' => 'status-success'][$i['severity']] }}">
                            {{ $i['severity_label'] }}
                        </span>
                        <span class="area"><i class="fas fa-tag ml-1 text-gold"></i>{{ $i['area'] }}</span>
                    </div>
                    <h6>{{ $i['title'] }}</h6>
                    <div class="detail">{{ $i['detail'] }}</div>
                    @if ($i['evidence'])
                        <div class="evidence">
                            @foreach ($i['evidence'] as $ek => $ev)
                                <span>{{ $ek }}: <b>{{ $ev }}</b></span>
                            @endforeach
                        </div>
                    @endif
                    <div class="rec"><i class="fas fa-gavel ml-1"></i><strong>التوصية:</strong> {{ $i['rec'] }}</div>
                    <div class="text-start mt-2">
                        <button type="button" class="btn btn-link btn-sm p-0 ask-more" data-q="{{ 'حلّل بالتفصيل: ' . $i['title'] . ' — وما البدائل والمخاطر؟' }}">
                            <i class="fas fa-comments ml-1"></i>اسأل المساعد عن هذا
                        </button>
                    </div>
                </article>
            @empty
                <div class="empty-state card-kfs">
                    <span class="eg-icon lg"><i class="fas fa-circle-check"></i></span>
                    <h5>لا توجد استنتاجات حالياً</h5>
                    <p class="mb-0">لم تُرصد مؤشرات تستدعي التدخل وفق البيانات المتاحة.</p>
                </div>
            @endforelse
        </div>

        {{-- Assistant --}}
        <div class="col-lg-5">
            <div class="card-kfs ds-chat" id="dsChat">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chess-king ml-2"></i>المستشار الذكي</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="dsReset" title="بدء محادثة جديدة"><i class="fas fa-broom ml-1"></i>جديدة</button>
                </div>
                <div class="card-body" id="dsMessages" aria-live="polite">
                    <div class="msg assistant" id="dsWelcome" @if ($history->isNotEmpty()) hidden @endif>
                        <div class="bubble">
                            <h4>مرحباً بكم</h4>
                            أنا مستشار دعم القرار. أجيب من بيانات المنصة الفعلية — المراكز والقرى والمنشآت والمركبات والصيانة والنماذج — وأقدّم البدائل والتوصيات. اختر سؤالاً أو اكتب سؤالك.
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="suggest mb-2" id="dsSuggest">
                        @foreach ($suggestions as $s)
                            <button type="button" data-q="{{ $s }}">{{ $s }}</button>
                        @endforeach
                    </div>
                    <form id="dsForm" class="d-flex gap-2 align-items-end">
                        <label for="dsInput" class="visually-hidden">سؤالك</label>
                        <textarea id="dsInput" class="form-control" rows="1" maxlength="2000" placeholder="اكتب سؤالك… (Enter للإرسال)"></textarea>
                        <button class="btn btn-kfs" type="submit" id="dsSend" aria-label="إرسال"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Center comparison --}}
    <div class="section-head mt-5">
        <div class="sh-line"><span class="sh-num">02</span></div>
        <h3>مقارنة المراكز</h3>
        <p>اختر المؤشر لمقارنة المراكز؛ الخط الذهبي يمثل متوسط المراكز.</p>
    </div>
    <div class="card-kfs">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="fas fa-ranking-star ml-2"></i>المؤشر</span>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="cmpIndicator" class="form-select form-select-sm" style="min-width:260px" aria-label="اختر المؤشر">
                    @foreach ($indicators as $key => [$label])
                        <option value="{{ $key }}" @selected($key === 'schools_per100k')>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" id="cmpSort" class="btn btn-sm btn-outline-secondary" aria-label="عكس الترتيب"><i class="fas fa-arrow-down-wide-short"></i></button>
            </div>
        </div>
        <div class="px-3 pt-2 cmp-legend">
            <span><span class="sw" style="background:var(--egypt-green-3)"></span>قيمة المركز</span>
            <span><span class="sw" style="background:var(--egypt-gold);width:3px;height:12px"></span>متوسط المراكز: <strong id="cmpAvg" class="text-green">—</strong></span>
            <span id="cmpNote"></span>
        </div>
        <div class="table-responsive">
            <table class="table table-kfs mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>المركز</th>
                        <th style="width:40%">المقارنة</th>
                        <th>القيمة</th>
                        <th>التقييم</th>
                        <th class="d-none d-md-table-cell">السكان</th>
                    </tr>
                </thead>
                <tbody id="cmpBody"></tbody>
            </table>
        </div>
    </div>

    {{-- Operations --}}
    <div class="section-head mt-5">
        <div class="sh-line"><span class="sh-num">03</span></div>
        <h3>المتابعة التشغيلية</h3>
        <p>المركبات والصيانة وحداثة مصادر البيانات.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-kfs h-100">
                <div class="card-header"><i class="fas fa-truck ml-2"></i>أعلى الجهات أعطالاً في المركبات</div>
                <ul class="list-group list-group-flush">
                    @forelse ($brief['vehicles']['top_broken_locations'] as $loc => $l)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $loc }}</span><span><strong class="text-danger">{{ $l['broken'] }}</strong> <small class="text-muted">/ {{ $l['total'] }}</small></span></li>
                    @empty
                        <li class="list-group-item text-muted">لا توجد أعطال مسجلة.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-kfs h-100">
                <div class="card-header"><i class="fas fa-screwdriver-wrench ml-2"></i>تنفيذ الصيانة (الأقل أولاً)</div>
                <ul class="list-group list-group-flush">
                    @forelse (collect($brief['maintenance']['by_entity'])->take(7) as $name => $e)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between"><span>{{ $name }}</span><strong class="{{ $e['rate'] < 60 ? 'text-danger' : ($e['rate'] < 85 ? 'text-warning' : 'text-success') }}">{{ $e['rate'] }}%</strong></div>
                            <div class="progress mt-1" role="progressbar" aria-label="{{ $name }}" aria-valuenow="{{ $e['rate'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $e['rate'] }}%"></div></div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">لا توجد خطة صيانة مرفوعة.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-kfs h-100">
                <div class="card-header"><i class="fas fa-clock-rotate-left ml-2"></i>حداثة مصادر البيانات</div>
                <ul class="list-group list-group-flush">
                    @foreach ($brief['freshness'] as $s)
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span>{{ $s['source'] }}</span>
                            <small class="{{ $s['days'] === null ? 'text-muted' : ($s['days'] > 60 ? 'text-warning' : 'text-success') }}">
                                @if ($s['days'] !== null && $s['days'] > 60)<i class="fas fa-triangle-exclamation ml-1"></i>@endif{{ $s['label'] }}
                            </small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <script type="application/json" id="dsData">@json(['centers' => $brief['centers'], 'indicators' => $indicators, 'history' => $history])</script>
@endsection

@push('scripts')
<script>
(function () {
    const DATA = JSON.parse(document.getElementById('dsData').textContent);
    const fmt = new Intl.NumberFormat('en-US', { maximumFractionDigits: 1 });
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    /* ---------------- Insights filter ---------------- */
    document.querySelectorAll('.ins-filter button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.ins-filter button').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        document.querySelectorAll('.insight').forEach(el => el.hidden = !(b.dataset.sev === 'all' || el.dataset.sev === b.dataset.sev));
    }));

    /* ---------------- Center comparison ---------------- */
    const sel = document.getElementById('cmpIndicator');
    const body = document.getElementById('cmpBody');
    let desc = true;
    function renderCmp() {
        const key = sel.value;
        const [label, unit, higherBetter] = DATA.indicators[key];
        const rows = DATA.centers.filter(c => c[key] !== null && c[key] !== undefined);
        const missing = DATA.centers.filter(c => c[key] === null || c[key] === undefined);
        const max = Math.max(...rows.map(c => +c[key]), 0) || 1;
        const avg = rows.length ? rows.reduce((s, c) => s + +c[key], 0) / rows.length : 0;
        rows.sort((a, b) => desc ? b[key] - a[key] : a[key] - b[key]);
        document.getElementById('cmpAvg').textContent = fmt.format(avg) + ' ' + unit;
        document.getElementById('cmpNote').textContent = missing.length ? ('مراكز بلا بيانات لهذا المؤشر: ' + missing.map(c => c.short).join('، ')) : '';
        body.innerHTML = rows.map((c, i) => {
            const v = +c[key];
            let rating = '<span class="text-muted">—</span>';
            if (higherBetter !== null && avg > 0) {
                const r = v / avg;
                const good = higherBetter ? r >= 1.15 : r <= .85;
                const bad = higherBetter ? r < .7 : r > 1.3;
                rating = bad ? '<span class="badge-status status-error"><i class="fas fa-arrow-down"></i> أقل من المطلوب</span>'
                       : good ? '<span class="badge-status status-success"><i class="fas fa-arrow-up"></i> أفضل من المتوسط</span>'
                       : '<span class="badge-status status-info">قريب من المتوسط</span>';
            }
            const title = esc(c.name) + ': ' + fmt.format(v) + ' ' + esc(unit) + ' (المتوسط ' + fmt.format(avg) + ')';
            return '<tr class="cmp-row">' +
                '<td class="en text-muted">' + (i + 1) + '</td>' +
                '<td class="fw-semibold">' + esc(c.name) + '</td>' +
                '<td><div class="cmp-bar" title="' + title + '"><div class="fill" style="width:' + (v / max * 100) + '%"></div>' +
                '<div class="avg" style="right:calc(' + (avg / max * 100) + '% - 1px)"></div></div></td>' +
                '<td class="en fw-bold text-green">' + fmt.format(v) + ' <small class="text-muted">' + esc(unit) + '</small></td>' +
                '<td>' + rating + '</td>' +
                '<td class="en d-none d-md-table-cell text-muted">' + (c.population ? fmt.format(c.population) : '—') + '</td></tr>';
        }).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">لا تتوفر بيانات لهذا المؤشر.</td></tr>';
    }
    sel.addEventListener('change', renderCmp);
    document.getElementById('cmpSort').addEventListener('click', () => { desc = !desc; renderCmp(); });
    renderCmp();

    /* ---------------- Assistant chat ---------------- */
    const box = document.getElementById('dsMessages');
    const form = document.getElementById('dsForm');
    const input = document.getElementById('dsInput');
    const send = document.getElementById('dsSend');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let busy = false;

    // Minimal, safe markdown: escape first, then format headings / lists / bold / rules.
    function md(text) {
        const lines = esc(text).split(/\r?\n/);
        let html = '', list = null;
        const close = () => { if (list) { html += '</' + list + '>'; list = null; } };
        const inline = s => s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/`([^`]+)`/g, '<code>$1</code>');
        for (const raw of lines) {
            const l = raw.trim();
            if (!l) { close(); continue; }
            let m;
            if (/^-{3,}$/.test(l)) { close(); html += '<hr>'; }
            else if ((m = l.match(/^#{1,4}\s+(.*)$/))) { close(); html += '<h4>' + inline(m[1]) + '</h4>'; }
            else if ((m = l.match(/^[-*•]\s+(.*)$/))) { if (list !== 'ul') { close(); html += '<ul>'; list = 'ul'; } html += '<li>' + inline(m[1]) + '</li>'; }
            else if ((m = l.match(/^\d+[.)]\s+(.*)$/))) { if (list !== 'ol') { close(); html += '<ol>'; list = 'ol'; } html += '<li>' + inline(m[1]) + '</li>'; }
            else { close(); html += '<p class="mb-1">' + inline(l) + '</p>'; }
        }
        close();
        return html;
    }

    function addMsg(role, content, source, time) {
        document.getElementById('dsWelcome').hidden = true;
        const el = document.createElement('div');
        el.className = 'msg ' + role;
        el.innerHTML = '<div class="bubble">' + (role === 'assistant' ? md(content) : esc(content).replace(/\n/g, '<br>')) + '</div>' +
            '<div class="meta"><span>' + (source ? '<span class="src-badge">' + esc(source) + '</span> ' : '') + esc(time || '') + '</span>' +
            (role === 'assistant' ? '<button type="button" class="copy"><i class="far fa-copy ml-1"></i>نسخ</button>' : '') + '</div>';
        const copy = el.querySelector('.copy');
        if (copy) copy.addEventListener('click', () => {
            const done = () => { copy.innerHTML = '<i class="fas fa-check ml-1"></i>تم النسخ'; setTimeout(() => copy.innerHTML = '<i class="far fa-copy ml-1"></i>نسخ', 1800); };
            if (navigator.clipboard) navigator.clipboard.writeText(content).then(done).catch(() => {}); else done();
        });
        box.appendChild(el);
        box.scrollTop = box.scrollHeight;
        return el;
    }

    (DATA.history || []).forEach(m => addMsg(m.role, m.content, m.source, m.time));

    async function ask(q) {
        q = (q || '').trim();
        if (!q || busy) return;
        busy = true; send.disabled = true; input.value = '';
        addMsg('user', q, null, 'الآن');
        const wait = document.createElement('div');
        wait.className = 'msg assistant';
        wait.innerHTML = '<div class="bubble"><span class="typing"><span></span><span></span><span></span> جارٍ تحليل البيانات…</span></div>';
        box.appendChild(wait); box.scrollTop = box.scrollHeight;
        try {
            const res = await fetch(@json(route('decision.ask')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ question: q })
            });
            const data = await res.json().catch(() => ({}));
            wait.remove();
            if (!res.ok || !data.success) {
                addMsg('assistant', data.message || 'تعذر الحصول على إجابة الآن. حاول مرة أخرى.', null, '');
            } else {
                addMsg('assistant', data.answer, data.source_label, 'الآن · ' + data.elapsed + ' ث');
            }
        } catch (e) {
            wait.remove();
            addMsg('assistant', 'تعذر الاتصال بالخادم. تحقق من الاتصال بالشبكة.', null, '');
        } finally {
            busy = false; send.disabled = false; input.focus();
        }
    }

    form.addEventListener('submit', e => { e.preventDefault(); ask(input.value); });
    input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ask(input.value); } });
    input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(140, input.scrollHeight) + 'px'; });
    document.querySelectorAll('#dsSuggest button, .ask-more').forEach(b => b.addEventListener('click', () => {
        ask(b.dataset.q);
        if (window.innerWidth < 992) document.getElementById('dsChat').scrollIntoView({ behavior: 'smooth' });
    }));
    document.getElementById('dsReset').addEventListener('click', async () => {
        if (busy) return;
        await fetch(@json(route('decision.ask')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ reset: true }) });
        box.querySelectorAll('.msg:not(#dsWelcome)').forEach(el => el.remove());
        document.getElementById('dsWelcome').hidden = false;
    });
})();
</script>
@endpush
