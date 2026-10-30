@extends('layouts.app')

@section('title', 'خريطة كفر الشيخ التفاعلية')
@section('page-title', 'خريطة محافظة كفر الشيخ التفاعلية')
@section('page-subtitle', 'البيانات المكانية والوصفية للمراكز والقرى والمرافق والقطاعات في مكان واحد')
@section('breadcrumb')
    <li class="breadcrumb-item active">الخريطة التفاعلية</li>
@endsection

@section('page-actions')
    <button type="button" class="btn btn-kfs-gold btn-sm" id="btnTable"><i class="fas fa-table-list ml-1"></i>جدول البيانات</button>
    <button type="button" class="btn btn-kfs-gold btn-sm" id="btnExport"><i class="fas fa-file-excel ml-1"></i>تصدير Excel</button>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.min.css">
    <style>
        .content-wrapper > .p-4 { padding-bottom: 1rem !important; }
        .map-shell {
            position: relative; display: grid; grid-template-columns: 340px minmax(0, 1fr);
            height: max(560px, calc(100vh - var(--idbar-height) - var(--navbar-height) - 190px));
            border: 1px solid var(--line); border-radius: var(--r-lg); overflow: hidden;
            background: var(--white-soft); box-shadow: var(--shadow);
        }
        .map-shell:fullscreen { height: 100vh; border-radius: 0; }

        /* ---------- side panel ---------- */
        .map-panel { display: flex; flex-direction: column; min-height: 0; border-left: 1px solid var(--line); background: var(--white-soft); z-index: 2; }
        .map-panel .mp-head { padding: .9rem 1rem .75rem; border-bottom: 1px solid var(--line); background: var(--papyrus); }
        .map-panel .mp-body { flex: 1; overflow-y: auto; padding: .25rem 0 1rem; }
        .mp-search { position: relative; }
        .mp-search .top-search { width: 100%; height: 40px; }
        .mp-suggest {
            position: absolute; top: 44px; right: 0; left: 0; z-index: 1200; display: none;
            background: var(--white-soft); border: 1px solid var(--line); border-top: 2px solid var(--egypt-gold);
            border-radius: var(--r); box-shadow: var(--shadow-lg); max-height: 340px; overflow-y: auto;
        }
        .mp-suggest.open { display: block; }
        .mp-suggest button { display: flex; width: 100%; gap: .6rem; align-items: flex-start; text-align: right; background: none; border: 0; border-bottom: 1px solid var(--line); padding: .5rem .75rem; font-size: .82rem; }
        .mp-suggest button:hover, .mp-suggest button:focus-visible { background: var(--papyrus); }
        .mp-suggest small { color: var(--text-soft); display: block; font-size: .72rem; }
        .mp-tabs { display: flex; border-bottom: 1px solid var(--line); background: var(--white-soft); position: sticky; top: 0; z-index: 1; }
        .mp-tabs button { flex: 1; border: 0; background: none; padding: .6rem .25rem; font-family: var(--ff-ar); font-weight: 700; font-size: .82rem; color: var(--text-soft); border-bottom: 2px solid transparent; }
        .mp-tabs button.active { color: var(--egypt-green); border-bottom-color: var(--egypt-gold); }
        .mp-pane { display: none; padding: .5rem .75rem; }
        .mp-pane.active { display: block; }

        .lg-group { border-bottom: 1px solid var(--line); }
        .lg-group > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: .5rem; padding: .65rem .25rem; font-family: var(--ff-ar); font-weight: 700; color: var(--egypt-green); font-size: .86rem; }
        .lg-group > summary::-webkit-details-marker { display: none; }
        .lg-group > summary .chev { margin-inline-start: auto; color: var(--egypt-gold); transition: transform .4s var(--ease); font-size: .75rem; }
        .lg-group[open] > summary .chev { transform: rotate(-90deg); }
        .lg-sub { font-size: .7rem; color: var(--egypt-gold); font-weight: 700; padding: .35rem .5rem .1rem; }
        .lg-item { display: flex; align-items: center; gap: .55rem; padding: .32rem .5rem; border-radius: var(--r); font-size: .8rem; cursor: pointer; }
        .lg-item:hover { background: var(--papyrus); }
        .lg-item input { flex-shrink: 0; }
        .lg-item .sw { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #FEFDFC; font-size: .62rem; flex-shrink: 0; border: 1px solid rgba(254,253,252,.9); box-shadow: 0 0 0 1px var(--line); }
        .lg-item .lbl { flex: 1; min-width: 0; line-height: 1.4; }
        .lg-item .cnt { font-family: var(--ff-en); font-size: .7rem; color: var(--text-soft); white-space: nowrap; }
        .lg-item.is-empty { opacity: .55; cursor: default; }
        .lg-item.is-loading .cnt::after { content: " …"; }
        .lg-note { font-size: .72rem; color: var(--text-soft); padding: .25rem .5rem .6rem; }

        .ind-row { display: grid; grid-template-columns: 1fr auto; gap: .2rem .5rem; padding: .4rem .25rem; border-bottom: 1px dashed var(--line); font-size: .8rem; cursor: pointer; }
        .ind-row:hover { background: var(--papyrus); }
        .ind-row .bar { grid-column: 1 / -1; height: 4px; background: var(--sand); border-radius: 2px; overflow: hidden; }
        .ind-row .bar i { display: block; height: 100%; background: var(--egypt-green); }
        .ind-row .val { font-family: var(--ff-en); font-weight: 600; color: var(--egypt-green); }

        /* ---------- map ---------- */
        .map-wrap { position: relative; min-width: 0; min-height: 0; }
        #kfsMap { position: absolute; inset: 0; background: var(--papyrus); font-family: var(--ff-body); }
        #kfsMap .leaflet-tile-pane { filter: saturate(.72) sepia(.08); }
        #kfsMap.sat .leaflet-tile-pane { filter: saturate(.9); }
        .leaflet-container a { color: var(--egypt-green-3); }
        .leaflet-bar a, .leaflet-control-layers { border-radius: var(--r) !important; }
        .leaflet-bar a { color: var(--egypt-green); background: var(--white-soft); }
        .leaflet-bar a:hover { color: var(--egypt-gold); background: var(--papyrus); }
        .leaflet-bar { border: 1px solid var(--line) !important; box-shadow: var(--shadow) !important; }
        .leaflet-control-layers { border: 1px solid var(--line) !important; box-shadow: var(--shadow) !important; background: var(--white-soft); direction: rtl; text-align: right; font-family: var(--ff-body); }
        .leaflet-popup-content-wrapper { border-radius: var(--r-lg); border-top: 3px solid var(--egypt-gold); background: var(--white-soft); box-shadow: var(--shadow-lg); }
        .leaflet-popup-content { margin: 0; width: 320px !important; max-width: calc(100vw - 80px); direction: rtl; text-align: right; font-family: var(--ff-body); }
        .leaflet-popup-tip { background: var(--white-soft); }
        .fp-head { padding: .8rem 1rem .5rem; border-bottom: 1px solid var(--line); }
        .fp-cat { font-size: .7rem; color: var(--egypt-gold); font-weight: 700; font-family: var(--ff-ar); }
        .fp-title { font-family: var(--ff-ar); font-weight: 800; color: var(--egypt-green); font-size: .98rem; line-height: 1.4; }
        .fp-body { max-height: 230px; overflow-y: auto; padding: .25rem 1rem; }
        .fp-body table { width: 100%; font-size: .76rem; }
        .fp-body th { color: var(--text-soft); font-weight: 600; padding: .3rem 0 .3rem .5rem; vertical-align: top; width: 42%; font-family: var(--ff-body); }
        .fp-body td { padding: .3rem 0; border-bottom: 1px solid var(--line); color: var(--charcoal); word-break: break-word; }
        .fp-body tr:last-child td { border-bottom: 0; }
        .fp-actions { display: flex; flex-wrap: wrap; gap: .35rem; padding: .6rem 1rem .8rem; border-top: 1px solid var(--line); background: var(--papyrus); border-radius: 0 0 var(--r-lg) var(--r-lg); }
        .fp-actions .btn { font-size: .72rem; padding: .25rem .55rem; }

        .kfs-pin { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #FEFDFC; font-size: 10px; border: 1.5px solid #FEFDFC; box-shadow: 0 2px 6px rgba(10,41,36,.35); }
        .kfs-pin.is-hl { outline: 2px solid var(--egypt-gold); outline-offset: 2px; }
        .kfs-cluster { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(18,63,53,.9); color: #FEFDFC; font-family: var(--ff-en); font-size: .72rem; font-weight: 600; border: 2px solid var(--egypt-gold); box-shadow: 0 3px 10px rgba(10,41,36,.3); }
        .kfs-cluster.lg { width: 46px; height: 46px; }
        .center-label { background: rgba(254,253,252,.92); border: 1px solid var(--line-gold); color: var(--egypt-green-deep); font-family: var(--ff-ar); font-weight: 700; font-size: .72rem; box-shadow: none; border-radius: 4px; padding: 1px 6px; }
        .center-label::before { display: none; }

        .map-chip { position: absolute; z-index: 800; top: 12px; right: 12px; background: var(--white-soft); border: 1px solid var(--line); border-right: 3px solid var(--egypt-gold); border-radius: var(--r); padding: .35rem .7rem; font-size: .76rem; box-shadow: var(--shadow); display: none; align-items: center; gap: .5rem; max-width: calc(100% - 90px); }
        .map-chip.show { display: flex; }
        .map-chip button { border: 0; background: none; color: var(--danger); padding: 0 .2rem; }
        .map-status { position: absolute; z-index: 800; bottom: 10px; right: 12px; background: rgba(254,253,252,.94); border: 1px solid var(--line); border-radius: var(--r); padding: .2rem .6rem; font-size: .72rem; color: var(--text-soft); font-family: var(--ff-en); direction: ltr; }
        .map-legend { position: absolute; z-index: 800; bottom: 38px; right: 12px; background: rgba(254,253,252,.96); border: 1px solid var(--line); border-radius: var(--r); padding: .45rem .7rem; font-size: .72rem; max-width: 240px; max-height: 40%; overflow-y: auto; display: none; box-shadow: var(--shadow); }
        .map-legend.show { display: block; }
        .map-legend div { display: flex; align-items: center; gap: .4rem; padding: .1rem 0; }
        .map-legend i.d { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

        /* unit drawer */
        .unit-drawer { position: absolute; z-index: 900; top: 0; bottom: 0; left: 0; width: 360px; max-width: 100%; background: var(--white-soft); border-right: 1px solid var(--line); box-shadow: var(--shadow-lg); transform: translateX(-102%); transition: transform .6s var(--ease-out); display: flex; flex-direction: column; }
        .unit-drawer.open { transform: none; }
        .unit-drawer .ud-head { position: relative; isolation: isolate; padding: 1rem 1.1rem .9rem; background: var(--egypt-green-deep); color: var(--white-soft); border-bottom: 1px solid var(--egypt-gold); }
        .unit-drawer .ud-head::before { content: ""; position: absolute; inset: 0; z-index: -1; background-image: url("{{ asset('assets/img/egypt-pattern.svg') }}"); background-size: 140px; opacity: .06; }
        .unit-drawer .ud-head h5 { color: var(--white-soft); margin: .15rem 0 0; font-size: 1.1rem; }
        .unit-drawer .ud-head small { color: var(--egypt-gold-2); }
        .unit-drawer .ud-close { position: absolute; top: .6rem; left: .6rem; border: 1px solid var(--egypt-gold-line); background: transparent; color: var(--white-soft); width: 30px; height: 30px; border-radius: 50%; }
        .unit-drawer .ud-body { flex: 1; overflow-y: auto; padding: .9rem 1.1rem; font-size: .82rem; }
        .ud-kpis { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--line); border-radius: var(--r); margin-bottom: .9rem; }
        .ud-kpis > div { padding: .55rem .4rem; text-align: center; }
        .ud-kpis > div + div { border-right: 1px solid var(--line-gold); }
        .ud-kpis b { display: block; font-family: var(--ff-ar); font-size: 1.15rem; color: var(--egypt-green); }
        .ud-kpis span { font-size: .68rem; color: var(--text-soft); }
        .ud-sec { font-family: var(--ff-ar); font-weight: 700; color: var(--egypt-green); font-size: .85rem; margin: .8rem 0 .35rem; display: flex; align-items: center; gap: .45rem; }
        .ud-sec::after { content: ""; flex: 1; height: 1px; background: var(--line-gold); }
        .ud-list { list-style: none; padding: 0; margin: 0; }
        .ud-list li { display: flex; justify-content: space-between; gap: .5rem; padding: .28rem 0; border-bottom: 1px dashed var(--line); }
        .ud-list li a, .ud-list li button.linkish { color: var(--egypt-green-3); background: none; border: 0; padding: 0; text-align: right; }
        .ud-list li a:hover, .ud-list li button.linkish:hover { color: var(--egypt-gold); }
        .ud-list .n { font-family: var(--ff-en); color: var(--text-soft); }

        /* attribute table */
        .attr-table { position: absolute; z-index: 850; left: 0; right: 0; bottom: 0; height: 44%; background: var(--white-soft); border-top: 2px solid var(--egypt-gold); box-shadow: 0 -12px 30px rgba(18,63,53,.12); transform: translateY(102%); transition: transform .6s var(--ease-out); display: flex; flex-direction: column; }
        .attr-table.open { transform: none; }
        .attr-table .at-head { display: flex; gap: .5rem; align-items: center; padding: .5rem .75rem; border-bottom: 1px solid var(--line); background: var(--papyrus); flex-wrap: wrap; }
        .attr-table .at-head .form-control { max-width: 240px; height: 32px; padding: .2rem .6rem; font-size: .8rem; }
        .attr-table .at-body { flex: 1; overflow: auto; }
        .attr-table table { font-size: .78rem; margin: 0; }
        .attr-table tbody tr { cursor: pointer; }
        .attr-table thead th { position: sticky; top: 0; z-index: 1; }

        .panel-toggle { display: none; }

        @media (max-width: 991.98px) {
            .map-shell { grid-template-columns: minmax(0, 1fr); height: max(520px, calc(100vh - var(--idbar-height) - var(--navbar-height) - 170px)); }
            .map-panel { position: absolute; z-index: 950; top: 0; bottom: 0; right: 0; width: min(340px, 88%); transform: translateX(102%); transition: transform .6s var(--ease-out); box-shadow: var(--shadow-lg); }
            .map-panel.open { transform: none; }
            .panel-toggle { display: inline-flex; position: absolute; z-index: 900; top: 12px; right: 12px; }
            .map-chip { top: 58px; }
            .unit-drawer { width: 100%; }
            .attr-table { height: 60%; }
        }
        @media (max-width: 575.98px) {
            .map-legend { display: none !important; }
            .leaflet-popup-content { width: 260px !important; }
        }
    </style>
@endpush

@section('content')
    <div class="map-shell" id="mapShell">
        {{-- لوحة التحكم الجانبية --}}
        <aside class="map-panel" id="mapPanel" aria-label="لوحة طبقات الخريطة">
            <div class="mp-head">
                <div class="mp-search">
                    <div class="top-search">
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="mapSearch" placeholder="ابحث عن مكان أو جهة أو قرية…" autocomplete="off" aria-label="بحث على الخريطة" aria-controls="mapSuggest">
                    </div>
                    <div class="mp-suggest" id="mapSuggest" role="listbox"></div>
                </div>
                <div class="d-flex gap-2 mt-2 align-items-center">
                    <select class="form-select form-select-sm" id="unitFilter" aria-label="تصفية حسب الجهة">
                        <option value="">كل المحافظة</option>
                        @foreach ($unitOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" id="unitInfoBtn" title="تفاصيل الجهة" aria-label="تفاصيل الجهة المختارة" disabled><i class="fas fa-circle-info"></i></button>
                </div>
            </div>
            <div class="mp-tabs" role="tablist">
                <button class="active" data-tab="layers" role="tab" aria-selected="true"><i class="fas fa-layer-group ml-1 text-gold"></i>الطبقات</button>
                <button data-tab="ind" role="tab" aria-selected="false"><i class="fas fa-chart-simple ml-1 text-gold"></i>المؤشرات</button>
            </div>
            <div class="mp-body">
                <div class="mp-pane active" id="paneLayers" role="tabpanel">
                    <div class="text-center text-muted py-4" id="layersLoading"><i class="fas fa-circle-notch fa-spin ml-1"></i>جارٍ تحميل الطبقات…</div>
                    <div id="layerTree"></div>
                </div>
                <div class="mp-pane" id="paneInd" role="tabpanel">
                    <label class="form-label mt-1" for="indicatorSel">المؤشر الموضوعي للمراكز</label>
                    <select class="form-select form-select-sm" id="indicatorSel">
                        @foreach ($indicators as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="showCircles" checked>
                        <label class="form-check-label" for="showCircles" style="font-size:.8rem">إظهار الدوائر الموضوعية على الخريطة</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showLabels" checked>
                        <label class="form-check-label" for="showLabels" style="font-size:.8rem">إظهار أسماء المراكز</label>
                    </div>
                    <div class="ud-sec mt-3">ترتيب المراكز</div>
                    <div id="indList"><div class="text-muted small">جارٍ التحميل…</div></div>
                </div>
            </div>
        </aside>

        {{-- الخريطة --}}
        <div class="map-wrap">
            <button type="button" class="btn btn-kfs btn-sm panel-toggle" id="panelToggle" aria-controls="mapPanel" aria-expanded="false"><i class="fas fa-layer-group ml-1"></i>الطبقات</button>
            <div id="kfsMap" dir="ltr" role="application" aria-label="خريطة محافظة كفر الشيخ"></div>
            <div class="map-chip" id="unitChip"><i class="fas fa-filter text-gold"></i><span id="unitChipText"></span><button type="button" id="unitChipClear" aria-label="إلغاء التصفية"><i class="fas fa-xmark"></i></button></div>
            <div class="map-legend" id="mapLegend" aria-label="مفتاح الخريطة"></div>
            <div class="map-status" id="mapStatus">—</div>

            {{-- درج تفاصيل الجهة --}}
            <section class="unit-drawer" id="unitDrawer" aria-label="تفاصيل الجهة" aria-hidden="true">
                <div class="ud-head">
                    <button type="button" class="ud-close" id="udClose" aria-label="إغلاق"><i class="fas fa-xmark"></i></button>
                    <small id="udType"></small>
                    <h5 id="udName"></h5>
                    <div style="font-size:.72rem;color:rgba(254,253,252,.65)" id="udCrumb"></div>
                </div>
                <div class="ud-body" id="udBody"></div>
            </section>

            {{-- جدول البيانات الوصفية --}}
            <section class="attr-table" id="attrTable" aria-label="جدول البيانات الوصفية" aria-hidden="true">
                <div class="at-head">
                    <strong class="text-green" style="font-family:var(--ff-ar)"><i class="fas fa-table-list ml-1 text-gold"></i>المعالم الظاهرة</strong>
                    <span class="badge badge-gold" id="atCount">0</span>
                    <input type="search" class="form-control" id="atFilter" placeholder="تصفية الجدول…" aria-label="تصفية الجدول">
                    <div class="form-check form-switch m-0 small"><input class="form-check-input" type="checkbox" id="atInView" checked><label class="form-check-label" for="atInView">داخل حدود العرض فقط</label></div>
                    <span class="ml-auto d-flex gap-1">
                        <button class="btn btn-sm btn-kfs" id="atExport"><i class="fas fa-file-excel ml-1"></i>تصدير</button>
                        <button class="btn btn-sm btn-outline-secondary" id="atClose" aria-label="إغلاق الجدول"><i class="fas fa-chevron-down"></i></button>
                    </span>
                </div>
                <div class="at-body">
                    <table class="table table-kfs table-hover mb-0">
                        <thead><tr><th>#</th><th>الاسم</th><th>الطبقة / التصنيف</th><th>المركز</th><th class="en">Lat, Lng</th></tr></thead>
                        <tbody id="atBody"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js"></script>
    <script>
    (function () {
        'use strict';
        const ROUTES = {
            layers: @json(route('map.layers')),
            features: @json(route('map.features')),
            units: @json(route('map.units')),
            unit: @json(url('map/unit')),
            search: @json(route('map.search')),
            export: @json(route('map.export')),
        };
        const INITIAL = @json($initial);
        const BOUNDARY = @json($boundary);
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('reduce-motion');
        const nf = new Intl.NumberFormat('ar-EG');
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const $ = id => document.getElementById(id);
        const getJSON = url => fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

        /* ------------------------------------------------------------------ map */
        const map = L.map('kfsMap', { zoomControl: false, preferCanvas: false, attributionControl: true, minZoom: 7, maxZoom: 19 });
        L.control.zoom({ position: 'topleft', zoomInTitle: 'تكبير', zoomOutTitle: 'تصغير' }).addTo(map);
        const bases = {
            'خريطة هادئة': L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 19 }),
            'خريطة الشوارع': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }),
            'صور القمر الصناعي': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri', maxZoom: 19 }),
        };
        bases['خريطة هادئة'].addTo(map);
        map.on('baselayerchange', e => $('kfsMap').classList.toggle('sat', e.name === 'صور القمر الصناعي'));

        // حدود المحافظة + قناع عاجي خارجها
        const ring = BOUNDARY.map(([lng, lat]) => [lat, lng]);
        let boundaryLayer = null;
        if (ring.length) {
            const world = [[-89, -179], [-89, 179], [89, 179], [89, -179]];
            L.polygon([world, ring], { stroke: false, fillColor: '#FAF8F2', fillOpacity: .62, interactive: false }).addTo(map);
            L.polyline(ring.concat([ring[0]]), { color: '#123F35', weight: 4, opacity: .85, interactive: false }).addTo(map);
            boundaryLayer = L.polyline(ring.concat([ring[0]]), { color: '#B8954F', weight: 1.6, dashArray: '6 6', interactive: false }).addTo(map);
            map.fitBounds(boundaryLayer.getBounds(), { padding: [20, 20] });
            map.setMaxBounds(boundaryLayer.getBounds().pad(1.2));
        } else {
            map.setView([31.25, 30.9], 10);
        }

        const overlays = {};
        const layersCtl = L.control.layers(bases, overlays, { position: 'topleft', collapsed: true }).addTo(map);

        // أدوات: موقعي + ملء الشاشة + إعادة الضبط
        const Tools = L.Control.extend({
            options: { position: 'topleft' },
            onAdd() {
                const el = L.DomUtil.create('div', 'leaflet-bar');
                el.innerHTML =
                    '<a href="#" role="button" title="موقعي الحالي" aria-label="موقعي الحالي" data-act="locate"><i class="fas fa-location-crosshairs"></i></a>' +
                    '<a href="#" role="button" title="ملء الشاشة" aria-label="ملء الشاشة" data-act="fs"><i class="fas fa-expand"></i></a>' +
                    '<a href="#" role="button" title="عرض المحافظة كاملة" aria-label="عرض المحافظة كاملة" data-act="home"><i class="fas fa-house"></i></a>';
                L.DomEvent.disableClickPropagation(el);
                el.addEventListener('click', e => {
                    const a = e.target.closest('a'); if (!a) return; e.preventDefault();
                    if (a.dataset.act === 'home' && boundaryLayer) map.fitBounds(boundaryLayer.getBounds(), { padding: [20, 20] });
                    if (a.dataset.act === 'fs') {
                        const shell = $('mapShell');
                        if (document.fullscreenElement) document.exitFullscreen(); else shell.requestFullscreen?.();
                    }
                    if (a.dataset.act === 'locate') map.locate({ setView: true, maxZoom: 15 });
                });
                return el;
            }
        });
        new Tools().addTo(map);
        document.addEventListener('fullscreenchange', () => setTimeout(() => map.invalidateSize(), 200));
        let meMarker = null;
        map.on('locationfound', e => { meMarker?.remove(); meMarker = L.circleMarker(e.latlng, { radius: 8, color: '#FEFDFC', weight: 2, fillColor: '#2D6477', fillOpacity: 1 }).addTo(map).bindTooltip('موقعك الحالي'); });
        map.on('locationerror', () => window.Swal ? Swal.fire({ icon: 'info', title: 'تعذّر تحديد الموقع', text: 'تأكد من السماح للمتصفح بالوصول إلى موقعك.' }) : null);

        const fly = (latlng, zoom) => reduceMotion ? map.setView(latlng, zoom) : map.flyTo(latlng, zoom, { duration: 1.1 });
        const statusEl = $('mapStatus');
        map.on('mousemove', e => statusEl.textContent = e.latlng.lat.toFixed(5) + ', ' + e.latlng.lng.toFixed(5));

        /* ------------------------------------------------------------------ state */
        const state = { unit: INITIAL.unit || null, active: new Map(), meta: {}, indicator: 'facilities', centers: [] };

        /* ------------------------------------------------------------------ markers */
        const pinIcon = (color, icon) => L.divIcon({
            className: '', iconSize: [24, 24], iconAnchor: [12, 12], popupAnchor: [0, -10],
            html: '<div class="kfs-pin" style="background:' + color + '"><i class="fas ' + (icon || 'fa-location-dot') + '"></i></div>'
        });
        const clusterIcon = (color) => c => {
            const n = c.getChildCount();
            return L.divIcon({ className: '', iconSize: n > 99 ? [46, 46] : [38, 38], html: '<div class="kfs-cluster' + (n > 99 ? ' lg' : '') + '" style="background:' + color + 'e6">' + n + '</div>' });
        };

        function popupHtml(p, latlng) {
            const rows = Object.entries(p.attrs || {}).map(([k, v]) => '<tr><th>' + esc(k) + '</th><td>' + esc(v) + '</td></tr>').join('');
            const coords = latlng.lat.toFixed(6) + ', ' + latlng.lng.toFixed(6);
            let actions = '<button class="btn btn-outline-secondary" data-act="copy" data-c="' + coords + '"><i class="far fa-copy ml-1"></i>نسخ الإحداثيات</button>' +
                '<button class="btn btn-outline-secondary" data-act="table"><i class="fas fa-table-list ml-1"></i>عرض في الجدول</button>';
            if (p.url) actions += '<a class="btn btn-kfs" href="' + esc(p.url) + '"><i class="fas fa-up-right-from-square ml-1"></i>فتح الإرسال</a>';
            if (p.unit_id) actions += '<button class="btn btn-outline-secondary" data-act="unit" data-u="' + p.unit_id + '"><i class="fas fa-circle-info ml-1"></i>بيانات الجهة</button>';
            actions += '<a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=' + coords.replace(' ', '') + '"><i class="fas fa-route ml-1"></i>الاتجاهات</a>';
            return '<div class="fp-head"><div class="fp-cat">' + esc(p.category || '') + (p.reference ? ' · <span class="en">' + esc(p.reference) + '</span>' : '') + '</div>' +
                '<div class="fp-title">' + esc(p.title) + '</div>' + (p.center ? '<small class="text-muted"><i class="fas fa-location-dot ml-1 text-gold"></i>' + esc(p.center) + '</small>' : '') + '</div>' +
                (rows ? '<div class="fp-body"><table>' + rows + '</table></div>' : '<div class="fp-body text-muted small py-2">لا توجد بيانات وصفية إضافية.</div>') +
                '<div class="fp-actions">' + actions + '</div>';
        }
        map.on('popupopen', e => {
            const el = e.popup.getElement();
            el.querySelectorAll('[data-act]').forEach(b => b.addEventListener('click', ev => {
                ev.preventDefault();
                if (b.dataset.act === 'copy') {
                    navigator.clipboard?.writeText(b.dataset.c).then(() => { b.innerHTML = '<i class="fas fa-check ml-1"></i>تم النسخ'; });
                } else if (b.dataset.act === 'table') {
                    openTable(e.popup._source?.feature?.properties?.id);
                } else if (b.dataset.act === 'unit') {
                    openUnit(+b.dataset.u);
                }
            }));
        });

        /* ------------------------------------------------------------------ layer loading */
        function layerUrl(id) {
            const u = new URL(ROUTES.features, location.origin);
            u.searchParams.set('layer', id);
            if (state.unit) u.searchParams.set('unit', state.unit);
            return u.toString();
        }

        async function enableLayer(id, opts = {}) {
            const meta = state.meta[id] || { color: '#123F35', icon: 'fa-location-dot', label: id };
            const item = document.querySelector('.lg-item[data-layer="' + CSS.escape(id) + '"]');
            item?.classList.add('is-loading');
            try {
                const fc = await getJSON(layerUrl(id));
                disableLayer(id, true);
                const group = L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 46, spiderfyOnMaxZoom: true, iconCreateFunction: clusterIcon(meta.color), chunkedLoading: true });
                const gj = L.geoJSON(fc, {
                    pointToLayer: (f, latlng) => L.marker(latlng, { icon: pinIcon(meta.color, f.properties.icon || meta.icon), title: f.properties.title, keyboard: true }),
                    onEachFeature: (f, lyr) => { lyr.feature = f; lyr.bindPopup(() => popupHtml(f.properties, lyr.getLatLng()), { maxWidth: 340 }); }
                });
                group.addLayer(gj);
                group.addTo(map);
                state.active.set(id, { group, fc });
                layersCtl.addOverlay(group, '<span style="color:' + meta.color + '">●</span> ' + esc(meta.label));
                if (item) {
                    item.querySelector('input').checked = true;
                    if (fc.truncated) item.querySelector('.cnt').title = 'تم عرض أول ' + fc.count + ' معلم فقط';
                }
                if (opts.fit && fc.features.length) map.fitBounds(gj.getBounds(), { padding: [40, 40], maxZoom: 14 });
                if (opts.focusId != null) focusFeature(id, opts.focusId);
            } catch (e) {
                if (item) item.querySelector('input').checked = false;
                console.error(e);
            } finally {
                item?.classList.remove('is-loading');
                syncUrl(); renderLegend(); refreshTable();
            }
        }

        function disableLayer(id, silent) {
            const a = state.active.get(id);
            if (!a) return;
            map.removeLayer(a.group);
            layersCtl.removeLayer(a.group);
            state.active.delete(id);
            const item = document.querySelector('.lg-item[data-layer="' + CSS.escape(id) + '"]');
            if (item) item.querySelector('input').checked = false;
            if (!silent) { syncUrl(); renderLegend(); refreshTable(); }
        }

        function reloadActive() {
            [...state.active.keys()].forEach(id => enableLayer(id));
        }

        function focusFeature(layerId, fid) {
            const a = state.active.get(layerId);
            if (!a) return;
            let target = null;
            a.group.eachLayer(l => { if (String(l.feature?.properties?.id) === String(fid)) target = l; });
            if (target) a.group.zoomToShowLayer(target, () => target.openPopup());
        }

        /* ------------------------------------------------------------------ layer tree */
        function renderTree(groups) {
            const tree = $('layerTree');
            tree.innerHTML = '';
            groups.forEach((g, gi) => {
                g.layers.forEach(l => state.meta[l.id] = l);
                const det = document.createElement('details');
                det.className = 'lg-group';
                if (gi < 2 || g.layers.some(l => INITIAL.layers.includes(l.id))) det.open = true;
                const geo = g.layers.reduce((s, l) => s + l.geo, 0);
                det.innerHTML = '<summary><i class="fas ' + g.icon + ' text-gold"></i>' + esc(g.label) +
                    '<span class="cnt en" style="font-size:.7rem;color:var(--text-soft);font-weight:500">' + nf.format(geo) + '</span><i class="fas fa-chevron-left chev"></i></summary>';
                let lastParent = null;
                if (!g.layers.length) det.insertAdjacentHTML('beforeend', '<div class="lg-note">لا توجد بيانات مكانية في هذه المجموعة حالياً.</div>');
                g.layers.forEach(l => {
                    if (l.parent && l.parent !== lastParent) { det.insertAdjacentHTML('beforeend', '<div class="lg-sub">' + esc(l.parent) + '</div>'); lastParent = l.parent; }
                    const empty = l.geo === 0;
                    const lbl = document.createElement('label');
                    lbl.className = 'lg-item' + (empty ? ' is-empty' : '');
                    lbl.dataset.layer = l.id;
                    lbl.title = empty ? 'السجلات موجودة لكن بدون إحداثيات (' + nf.format(l.total) + ' سجل) — تظهر في مؤشرات المراكز' : l.label;
                    lbl.innerHTML = '<input type="checkbox" class="form-check-input m-0"' + (empty ? ' disabled' : '') + ' aria-label="' + esc(l.label) + '">' +
                        '<span class="sw" style="background:' + l.color + '"><i class="fas ' + l.icon + '"></i></span>' +
                        '<span class="lbl">' + esc(l.label) + '</span>' +
                        '<span class="cnt">' + (l.geo !== l.total ? nf.format(l.geo) + ' / ' + nf.format(l.total) : nf.format(l.total)) + '</span>';
                    lbl.querySelector('input').addEventListener('change', ev => ev.target.checked ? enableLayer(l.id, { fit: false }) : disableLayer(l.id));
                    det.appendChild(lbl);
                });
                if (g.summary) {
                    const s = g.summary.filter(x => x.geo < x.total);
                    if (s.length) det.insertAdjacentHTML('beforeend', '<div class="lg-note"><i class="fas fa-circle-info ml-1 text-gold"></i>سجلات وصفية بدون إحداثيات: ' +
                        s.map(x => esc(x.label) + ' ' + nf.format(x.total - x.geo)).join(' · ') + '</div>');
                }
                tree.appendChild(det);
            });
            $('layersLoading').remove();
        }

        function renderLegend() {
            const lg = $('mapLegend');
            const rows = [...state.active.keys()].map(id => {
                const m = state.meta[id] || {}; const a = state.active.get(id);
                return '<div><i class="d" style="background:' + (m.color || '#123F35') + '"></i>' + esc(m.label || id) + ' <span class="en text-muted">(' + a.fc.count + ')</span></div>';
            });
            if ($('showCircles').checked && state.centers.length) rows.unshift('<div><i class="d" style="background:rgba(18,63,53,.25);border:1px solid #B8954F"></i>' + esc($('indicatorSel').selectedOptions[0]?.text || '') + '</div>');
            lg.innerHTML = rows.join('');
            lg.classList.toggle('show', rows.length > 0);
        }

        /* ------------------------------------------------------------------ center circles */
        const circleLayer = L.layerGroup().addTo(map);
        const labelLayer = L.layerGroup().addTo(map);
        function renderCircles() {
            circleLayer.clearLayers(); labelLayer.clearLayers();
            const key = $('indicatorSel').value;
            const vals = state.centers.map(c => c.indicators[key] || 0);
            const max = Math.max(1, ...vals);
            state.centers.forEach(c => {
                if (c.lat == null) return;
                const v = c.indicators[key] || 0;
                if ($('showCircles').checked) {
                    const r = 8 + Math.sqrt(v / max) * 30;
                    L.circleMarker([c.lat, c.lng], { radius: r, color: '#B8954F', weight: 1.2, fillColor: '#123F35', fillOpacity: state.unit == c.id ? .42 : .2 })
                        .bindTooltip('<strong>' + esc(c.name) + '</strong><br>' + esc($('indicatorSel').selectedOptions[0]?.text) + ': ' + nf.format(v), { direction: 'top' })
                        .on('click', () => openUnit(c.id))
                        .addTo(circleLayer);
                }
                if ($('showLabels').checked) {
                    L.marker([c.lat, c.lng], { opacity: 0, interactive: false, keyboard: false, icon: L.divIcon({ className: '', iconSize: [1, 1] }) })
                        .bindTooltip(esc(c.name.replace(/^مركز\s+/, '')), { permanent: true, direction: 'center', className: 'center-label', offset: [0, 0] })
                        .addTo(labelLayer);
                }
            });
            // قائمة الترتيب
            const sorted = [...state.centers].sort((a, b) => (b.indicators[key] || 0) - (a.indicators[key] || 0));
            $('indList').innerHTML = sorted.map(c => {
                const v = c.indicators[key] || 0;
                return '<div class="ind-row" data-u="' + c.id + '" tabindex="0" role="button"><span>' + esc(c.name) + '</span><span class="val">' + nf.format(v) +
                    '</span><span class="bar"><i style="width:' + (v / max * 100).toFixed(1) + '%"></i></span></div>';
            }).join('') || '<div class="text-muted small">لا توجد مراكز ضمن نطاقك.</div>';
            $('indList').querySelectorAll('.ind-row').forEach(r => {
                const go = () => { const c = state.centers.find(x => x.id == r.dataset.u); if (c?.lat) fly([c.lat, c.lng], 12); openUnit(+r.dataset.u); };
                r.addEventListener('click', go); r.addEventListener('keydown', e => { if (e.key === 'Enter') go(); });
            });
            renderLegend();
        }
        ['indicatorSel', 'showCircles', 'showLabels'].forEach(id => $(id).addEventListener('change', renderCircles));

        /* ------------------------------------------------------------------ unit drawer */
        async function openUnit(id) {
            if (!id) return;
            const d = $('unitDrawer');
            $('udName').textContent = 'جارٍ التحميل…'; $('udType').textContent = ''; $('udCrumb').textContent = ''; $('udBody').innerHTML = '';
            d.classList.add('open'); d.setAttribute('aria-hidden', 'false');
            try {
                const r = await getJSON(ROUTES.unit + '/' + id);
                const u = r.unit;
                $('udType').innerHTML = '<i class="fas ' + u.icon + ' ml-1"></i>' + esc(u.type_label) + (u.code ? ' · <span class="en">' + esc(u.code) + '</span>' : '');
                $('udName').textContent = u.name;
                $('udCrumb').textContent = u.breadcrumb.join(' / ');
                const subs = Object.values(r.submissions || {}).reduce((a, b) => a + b, 0);
                const facTotal = r.facilities.reduce((a, b) => a + b.count, 0);
                let h = '<div class="ud-kpis"><div><b>' + nf.format(facTotal) + '</b><span>مرفق ومنشأة</span></div><div><b>' + nf.format(r.descendants_total) +
                    '</b><span>جهة تابعة</span></div><div><b>' + nf.format(subs) + '</b><span>إرسال نماذج</span></div></div>';
                if (u.is_partial) h += '<div class="alert alert-info py-2 small mb-2">المرافق المعروضة على مستوى ' + esc(u.is_partial) + '.</div>';
                const info = [['رئيس الجهة', u.head], ['التليفون', u.phone], ['البريد', u.email], ['العنوان', u.address],
                    ['عدد السكان', u.population ? nf.format(u.population) : null], ['المساحة (كم²)', u.area_km2], ['الحسابات', nf.format(r.users)],
                    ['الوظائف (الشاغرة)', r.positions ? nf.format(r.positions) + ' (' + nf.format(r.vacant_positions) + ')' : null]].filter(x => x[1]);
                if (info.length) h += '<div class="ud-sec">البيانات الأساسية</div><ul class="ud-list">' + info.map(([k, v]) => '<li><span class="text-muted">' + k + '</span><span>' + esc(v) + '</span></li>').join('') + '</ul>';
                if (u.description) h += '<p class="small text-muted mt-2 mb-0">' + esc(u.description) + '</p>';
                if (r.facilities.length) h += '<div class="ud-sec">المرافق والمنشآت</div><ul class="ud-list">' + r.facilities.map(f =>
                    '<li><button class="linkish" data-layer="' + esc(f.layer) + '">' + esc(f.label) + '</button><span class="n">' + nf.format(f.count) + '</span></li>').join('') + '</ul>';
                if (r.sectors.length) h += '<div class="ud-sec">السجلات القطاعية</div><ul class="ud-list">' + r.sectors.map(s => '<li><span>' + esc(s.label) + '</span><span class="n">' + nf.format(s.count) + '</span></li>').join('') + '</ul>';
                if (r.children_by_type.length) h += '<div class="ud-sec">الجهات التابعة مباشرة</div><ul class="ud-list">' + r.children_by_type.map(c => '<li><span>' + esc(c.label) + '</span><span class="n">' + nf.format(c.count) + '</span></li>').join('') + '</ul>' +
                    '<ul class="ud-list mt-1">' + r.children.map(c => '<li><button class="linkish" data-unit="' + c.id + '">' + esc(c.name) + '</button><small class="text-muted">' + esc(c.type) + '</small></li>').join('') + '</ul>' +
                    (r.children_total > r.children.length ? '<small class="text-muted">و ' + nf.format(r.children_total - r.children.length) + ' جهة أخرى…</small>' : '');
                if (r.recent_places.length) h += '<div class="ud-sec">أماكن على الخريطة</div><ul class="ud-list">' + r.recent_places.map(p => '<li><button class="linkish" data-lat="' + p.lat + '" data-lng="' + p.lng + '">' + esc(p.name || '—') + '</button><small class="text-muted">' + esc(p.category) + '</small></li>').join('') + '</ul>';
                if (r.templates.length) h += '<div class="ud-sec">النماذج الموجهة للجهة</div><ul class="ud-list">' + r.templates.map(t => '<li><span>' + esc(t) + '</span></li>').join('') + '</ul>';
                h += '<div class="d-flex gap-2 mt-3 flex-wrap"><button class="btn btn-sm btn-kfs" data-filter="' + u.id + '"><i class="fas fa-filter ml-1"></i>تصفية الخريطة على هذه الجهة</button>' +
                    (u.lat ? '<button class="btn btn-sm btn-kfs-gold" data-lat="' + u.lat + '" data-lng="' + u.lng + '"><i class="fas fa-crosshairs ml-1"></i>تحديد على الخريطة</button>' : '') + '</div>' +
                    (u.approx && u.lat ? '<small class="text-muted d-block mt-2">لا تتوفر إحداثيات دقيقة لهذه الجهة؛ يُستخدم موقع الجهة الأعلى.</small>' : '');
                $('udBody').innerHTML = h;
                $('udBody').querySelectorAll('[data-layer]').forEach(b => b.addEventListener('click', () => enableLayer(b.dataset.layer, { fit: true })));
                $('udBody').querySelectorAll('[data-unit]').forEach(b => b.addEventListener('click', () => openUnit(+b.dataset.unit)));
                $('udBody').querySelectorAll('[data-lat]').forEach(b => b.addEventListener('click', () => fly([+b.dataset.lat, +b.dataset.lng], 15)));
                $('udBody').querySelectorAll('[data-filter]').forEach(b => b.addEventListener('click', () => setUnit(+b.dataset.filter, true)));
            } catch (e) {
                $('udName').textContent = 'تعذّر تحميل بيانات الجهة';
            }
        }
        $('udClose').addEventListener('click', () => { $('unitDrawer').classList.remove('open'); $('unitDrawer').setAttribute('aria-hidden', 'true'); });

        /* ------------------------------------------------------------------ unit filter */
        function setUnit(id, flyTo) {
            state.unit = id || null;
            $('unitFilter').value = state.unit || '';
            $('unitInfoBtn').disabled = !state.unit;
            const chip = $('unitChip');
            chip.classList.toggle('show', !!state.unit);
            if (state.unit) {
                $('unitChipText').textContent = ($('unitFilter').selectedOptions[0]?.text || '').replace(/^[—\s]+/, '');
                const c = state.centers.find(x => x.id == state.unit);
                if (flyTo && c?.lat) fly([c.lat, c.lng], 12);
            }
            reloadActive(); renderCircles(); syncUrl();
        }
        $('unitFilter').addEventListener('change', e => setUnit(+e.target.value || null, true));
        $('unitChipClear').addEventListener('click', () => setUnit(null));
        $('unitInfoBtn').addEventListener('click', () => openUnit(state.unit));

        /* ------------------------------------------------------------------ search */
        let sTimer = null, sResults = [];
        const sugg = $('mapSuggest');
        $('mapSearch').addEventListener('input', e => {
            clearTimeout(sTimer);
            const q = e.target.value.trim();
            if (q.length < 2) { sugg.classList.remove('open'); return; }
            sTimer = setTimeout(async () => {
                const u = new URL(ROUTES.search, location.origin); u.searchParams.set('q', q);
                try {
                    sResults = (await getJSON(u.toString())).results;
                    sugg.innerHTML = sResults.length ? sResults.map((r, i) =>
                        '<button type="button" role="option" data-i="' + i + '"><i class="fas ' + (r.icon || 'fa-location-dot') + ' text-gold mt-1"></i><span>' + esc(r.title) +
                        '<small>' + esc(r.subtitle || '') + (r.lat == null ? ' · بدون إحداثيات' : '') + '</small></span></button>').join('')
                        : '<div class="p-3 small text-muted">لا توجد نتائج مطابقة.</div>';
                    sugg.classList.add('open');
                    sugg.querySelectorAll('button').forEach(b => b.addEventListener('click', () => pickResult(sResults[+b.dataset.i])));
                } catch (err) { sugg.classList.remove('open'); }
            }, 280);
        });
        $('mapSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter' && sResults[0]) { e.preventDefault(); pickResult(sResults[0]); }
            if (e.key === 'Escape') sugg.classList.remove('open');
            if (e.key === 'ArrowDown') sugg.querySelector('button')?.focus();
        });
        document.addEventListener('click', e => { if (!e.target.closest('.mp-search')) sugg.classList.remove('open'); });
        function pickResult(r) {
            sugg.classList.remove('open');
            if (!r) return;
            if (r.type === 'unit') { openUnit(r.id); if (r.lat != null) fly([r.lat, r.lng], r.approx ? 11 : 14); }
            else if (r.lat != null) {
                fly([r.lat, r.lng], 16);
                if (r.layer) state.active.has(r.layer) ? focusFeature(r.layer, r.id) : enableLayer(r.layer, { focusId: r.id });
            } else if (window.Swal) Swal.fire({ icon: 'info', title: r.title, text: 'هذا السجل مسجّل بدون إحداثيات، ويظهر ضمن مؤشرات المركز التابع له.' });
            closePanelMobile();
        }

        /* ------------------------------------------------------------------ attribute table */
        function visibleFeatures() {
            const inView = $('atInView').checked, b = map.getBounds(), out = [];
            state.active.forEach((a, id) => a.fc.features.forEach(f => {
                const [lng, lat] = f.geometry.coordinates;
                if (!inView || b.contains([lat, lng])) out.push(f);
            }));
            return out;
        }
        function refreshTable() {
            if (!$('attrTable').classList.contains('open')) return;
            const q = $('atFilter').value.trim();
            let rows = visibleFeatures();
            if (q) rows = rows.filter(f => (f.properties.title + ' ' + f.properties.category + ' ' + (f.properties.center || '') + ' ' + Object.values(f.properties.attrs || {}).join(' ')).includes(q));
            $('atCount').textContent = nf.format(rows.length);
            $('atBody').innerHTML = rows.slice(0, 600).map((f, i) => {
                const [lng, lat] = f.geometry.coordinates, p = f.properties;
                return '<tr data-l="' + esc(p.layer) + '" data-id="' + esc(p.id) + '" data-hl="' + esc(state.tableHl) + '"' + (String(p.id) === String(state.tableHl) ? ' class="table-active"' : '') + '><td class="en">' + (i + 1) + '</td><td>' + esc(p.title) + '</td><td>' + esc(p.category) + '</td><td>' + esc(p.center || '—') + '</td><td class="en" dir="ltr">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</td></tr>';
            }).join('') + (rows.length > 600 ? '<tr><td colspan="5" class="text-center text-muted">يُعرض أول ٦٠٠ صف — استخدم التصدير للحصول على الكل.</td></tr>' : '')
                || '<tr><td colspan="5" class="text-center text-muted py-4">فعّل طبقة أو حرّك الخريطة لعرض المعالم.</td></tr>';
            $('atBody').querySelectorAll('tr[data-l]').forEach(tr => tr.addEventListener('click', () => focusFeature(tr.dataset.l, tr.dataset.id)));
        }
        function openTable(hlId) {
            state.tableHl = hlId;
            const t = $('attrTable'); t.classList.add('open'); t.setAttribute('aria-hidden', 'false');
            refreshTable();
            if (hlId != null) setTimeout(() => $('atBody').querySelector('.table-active')?.scrollIntoView({ block: 'center' }), 50);
        }
        function toggleTable() { $('attrTable').classList.contains('open') ? closeTable() : openTable(); }
        function closeTable() { $('attrTable').classList.remove('open'); $('attrTable').setAttribute('aria-hidden', 'true'); }
        $('btnTable').addEventListener('click', toggleTable);
        $('atClose').addEventListener('click', closeTable);
        $('atFilter').addEventListener('input', refreshTable);
        $('atInView').addEventListener('change', refreshTable);
        map.on('moveend', refreshTable);

        function exportExcel() {
            if (!state.active.size) { window.Swal ? Swal.fire({ icon: 'info', title: 'لا توجد طبقات مفعّلة', text: 'فعّل طبقة واحدة على الأقل من لوحة الطبقات قبل التصدير.' }) : alert('فعّل طبقة أولاً'); return; }
            const u = new URL(ROUTES.export, location.origin);
            u.searchParams.set('layers', [...state.active.keys()].join(','));
            if (state.unit) u.searchParams.set('unit', state.unit);
            if ($('attrTable').classList.contains('open') && $('atInView').checked) u.searchParams.set('bbox', map.getBounds().toBBoxString());
            location.href = u.toString();
        }
        $('btnExport').addEventListener('click', exportExcel);
        $('atExport').addEventListener('click', exportExcel);

        /* ------------------------------------------------------------------ panel / tabs */
        document.querySelectorAll('.mp-tabs button').forEach(b => b.addEventListener('click', () => {
            document.querySelectorAll('.mp-tabs button').forEach(x => { x.classList.toggle('active', x === b); x.setAttribute('aria-selected', x === b); });
            $('paneLayers').classList.toggle('active', b.dataset.tab === 'layers');
            $('paneInd').classList.toggle('active', b.dataset.tab === 'ind');
        }));
        $('panelToggle').addEventListener('click', () => {
            const p = $('mapPanel'); p.classList.toggle('open');
            $('panelToggle').setAttribute('aria-expanded', p.classList.contains('open'));
        });
        function closePanelMobile() { if (window.innerWidth < 992) { $('mapPanel').classList.remove('open'); $('panelToggle').setAttribute('aria-expanded', 'false'); } }
        map.on('click', closePanelMobile);

        function syncUrl() {
            const u = new URL(location.href);
            u.searchParams.delete('facility');
            state.active.size ? u.searchParams.set('layer', [...state.active.keys()].join(',')) : u.searchParams.delete('layer');
            state.unit ? u.searchParams.set('unit', state.unit) : u.searchParams.delete('unit');
            history.replaceState(null, '', u.toString());
        }

        /* ------------------------------------------------------------------ boot */
        Promise.all([getJSON(ROUTES.layers), getJSON(ROUTES.units)]).then(([lr, ur]) => {
            renderTree(lr.groups);
            state.centers = ur.centers;
            renderCircles();

            if (INITIAL.unit) { setUnit(INITIAL.unit, true); openUnit(INITIAL.unit); }
            const want = INITIAL.layers.filter(id => state.meta[id]);
            if (INITIAL.focus && INITIAL.focus.layer) {
                if (!want.includes(INITIAL.focus.layer)) want.push(INITIAL.focus.layer);
                if (INITIAL.focus.lat) fly([INITIAL.focus.lat, INITIAL.focus.lng], 16);
            }
            if (!want.length && !INITIAL.unit) {
                // افتراضي: أكبر طبقة مرافق ذات إحداثيات
                const fac = (lr.groups.find(g => g.key === 'facilities')?.layers || []).filter(l => l.geo > 0).sort((a, b) => b.geo - a.geo)[0];
                if (fac) want.push(fac.id);
            }
            want.forEach(id => enableLayer(id, { focusId: INITIAL.focus && INITIAL.focus.layer === id ? INITIAL.focus.id : null }));
        }).catch(() => {
            $('layersLoading').innerHTML = '<span class="text-danger"><i class="fas fa-triangle-exclamation ml-1"></i>تعذّر تحميل الطبقات. تحقق من الاتصال ثم أعد تحميل الصفحة.</span>';
        });

        window.addEventListener('resize', () => map.invalidateSize());
    })();
    </script>
@endpush
