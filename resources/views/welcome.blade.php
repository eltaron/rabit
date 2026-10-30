<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>رابط — البوابة الرسمية لتداول البيانات بمحافظة كفر الشيخ</title>
    <meta name="description" content="رابط: المنصة الرسمية لمحافظة كفر الشيخ لتداول البيانات المكانية والوصفية بين المراكز والمدن والقرى والمديريات والهيئات، ودعم اتخاذ القرار.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_EG">
    <meta property="og:title" content="رابط — محافظة كفر الشيخ">
    <meta property="og:image" content="{{ asset('logo.png') }}">

    @include('partials._pwa-head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/kfs-royal.css') }}?v={{ config('app.asset_version') }}" rel="stylesheet">

    <style>
        html, body { overflow-x: clip; }
        body { font-size: 1rem; }
        .lp-container { width: 100%; max-width: 1240px; margin-inline: auto; padding-inline: 24px; }

        /* ---------- Identity bar (static on landing) ---------- */
        .lp-idbar { position: relative; z-index: 1046; }

        /* ---------- Header ---------- */
        .hdr {
            position: sticky; top: 0; z-index: 1040;
            background: var(--egypt-green-deep); color: var(--white-soft);
            border-bottom: 1px solid var(--egypt-gold-line);
            transition: background .6s var(--ease), backdrop-filter .6s var(--ease);
        }
        .hdr.is-stuck { background: rgba(10,41,36,.94); backdrop-filter: blur(16px); }
        .hdr-inner { height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
        .hdr-logo img { height: 46px; width: auto; display: block; }
        .hdr-nav { display: flex; align-items: center; gap: .25rem; list-style: none; margin: 0; padding: 0; }
        .hdr-nav a {
            color: rgba(254,253,252,.82); font-family: var(--ff-ar); font-weight: 500; font-size: .93rem;
            padding: .5rem .8rem; position: relative; display: block;
        }
        .hdr-nav a::after {
            content: ""; position: absolute; right: .8rem; left: .8rem; bottom: 2px; height: 1px;
            background: var(--egypt-gold); transform: scaleX(0); transform-origin: right; transition: transform .5s var(--ease);
        }
        .hdr-nav a:hover, .hdr-nav a.active { color: var(--egypt-gold-2); }
        .hdr-nav a:hover::after, .hdr-nav a.active::after { transform: scaleX(1); }
        .hdr-actions { display: flex; align-items: center; gap: .6rem; }
        .btn-hdr {
            border: 1px solid var(--egypt-gold); color: var(--white-soft); background: transparent;
            padding: .5rem 1.1rem; border-radius: var(--r); font-weight: 700; font-family: var(--ff-ar); font-size: .88rem;
            display: inline-flex; align-items: center; gap: .5rem; transition: background .4s var(--ease), color .4s var(--ease);
        }
        .btn-hdr:hover { background: var(--egypt-gold); color: var(--egypt-green-deep); }
        .burger {
            width: 44px; height: 44px; border-radius: var(--r); border: 1px solid var(--egypt-gold-line);
            background: transparent; color: var(--white-soft); display: none; align-items: center; justify-content: center;
        }

        /* Mobile menu */
        .mmenu {
            position: fixed; inset: 0; z-index: 1060; background: var(--egypt-green-deep); color: var(--white-soft);
            display: flex; flex-direction: column; padding: 1.25rem 16px 2rem;
            opacity: 0; visibility: hidden; transition: opacity .5s var(--ease), visibility .5s;
        }
        .mmenu.open { opacity: 1; visibility: visible; }
        .mmenu .mm-top { display: flex; justify-content: space-between; align-items: center; }
        .mmenu .mm-top img { height: 40px; }
        .mmenu ul { list-style: none; padding: 0; margin: 2rem 0 0; }
        .mmenu li a {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 0; color: var(--white-soft); font-family: var(--ff-ar); font-weight: 700; font-size: 1.3rem;
            border-bottom: 1px solid var(--egypt-gold-line);
        }
        .mmenu li a i { color: var(--egypt-gold); font-size: .9rem; }
        .mmenu .mm-foot { margin-top: auto; color: rgba(254,253,252,.6); font-size: .85rem; }

        /* ---------- Hero ---------- */
        .hero {
            position: relative; isolation: isolate; overflow: hidden; color: var(--white-soft);
            background: linear-gradient(90deg, rgba(10,41,36,.98), rgba(18,63,53,.92), rgba(18,63,53,.70)), var(--egypt-green);
            padding: clamp(56px, 8vw, 110px) 0 clamp(64px, 8vw, 110px);
        }
        .hero::before {
            content: ""; position: absolute; inset: 0; z-index: -1;
            background-image: url("{{ asset('assets/img/egypt-pattern.svg') }}"); background-size: 160px; opacity: .05;
        }
        .hero-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: clamp(2rem, 5vw, 4rem); align-items: center; }
        .gov-badge {
            display: inline-flex; align-items: center; gap: .7rem;
            border: 1px solid var(--egypt-gold-line); border-radius: var(--r);
            padding: .45rem .9rem .45rem 1rem; font-size: .85rem; color: var(--egypt-gold-2); font-family: var(--ff-ar); font-weight: 500;
        }
        .gov-badge img { height: 22px; width: auto; }
        .hero h1 {
            color: var(--white-soft); font-size: clamp(2.1rem, 4.6vw, 4.2rem); font-weight: 800; line-height: 1.22;
            margin: 1.4rem 0 1.1rem;
        }
        .hero h1 .gold { color: var(--egypt-gold-2); }
        .hero .lead { color: rgba(254,253,252,.78); font-size: clamp(1rem, 1.4vw, 1.12rem); line-height: 1.95; max-width: 560px; }
        .hero-ctas { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 2rem; }
        .btn-hero-primary {
            background: var(--egypt-gold); color: var(--egypt-green-deep); border: 1px solid var(--egypt-gold);
            padding: .85rem 1.6rem; border-radius: var(--r); font-weight: 800; font-family: var(--ff-ar);
            display: inline-flex; align-items: center; gap: .6rem; transition: background .4s var(--ease);
        }
        .btn-hero-primary:hover { background: var(--egypt-gold-2); color: var(--egypt-green-deep); }
        .btn-hero-ghost {
            background: transparent; color: var(--white-soft); border: 1px solid rgba(254,253,252,.35);
            padding: .85rem 1.6rem; border-radius: var(--r); font-weight: 700; font-family: var(--ff-ar);
            display: inline-flex; align-items: center; gap: .6rem; transition: border-color .4s var(--ease), color .4s var(--ease);
        }
        .btn-hero-ghost:hover { border-color: var(--egypt-gold); color: var(--egypt-gold-2); }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 1.4rem; margin-top: 2.2rem; color: rgba(254,253,252,.65); font-size: .85rem; }
        .hero-meta i { color: var(--egypt-gold); margin-left: .4rem; }

        .hero-visual { position: relative; border: 1px solid var(--egypt-gold-line); border-radius: var(--r-lg); padding: 14px; background: rgba(10,41,36,.35); }
        .hero-visual::before { content: ""; position: absolute; top: -1px; left: 25%; right: 25%; height: 3px; background: var(--egypt-gold); }
        .hero-visual svg { width: 100%; height: auto; display: block; }
        .hero-visual .hv-caption {
            display: flex; justify-content: space-between; align-items: center; gap: 1rem;
            border-top: 1px solid var(--egypt-gold-line); margin-top: 10px; padding-top: 10px; font-size: .78rem; color: rgba(254,253,252,.65);
        }
        .hero-visual .hv-caption strong { color: var(--egypt-gold-2); font-family: var(--ff-ar); }
        .map-outline { fill: rgba(184,149,79,.06); stroke: var(--egypt-gold); stroke-width: 1.4; stroke-linejoin: round; }
        .map-outline-2 { fill: none; stroke: rgba(184,149,79,.28); stroke-width: 1; transform: translate(6px, 6px); }
        .map-grid line { stroke: rgba(254,253,252,.06); stroke-width: 1; }
        .map-dot { fill: var(--egypt-gold-2); }
        .map-ring { fill: none; stroke: rgba(209,181,116,.55); stroke-width: 1; }
        .map-label { fill: rgba(254,253,252,.85); font-family: Tajawal, sans-serif; font-size: 12px; font-weight: 500; }
        .map-link { stroke: rgba(184,149,79,.22); stroke-width: 1; stroke-dasharray: 3 4; fill: none; }

        /* ---------- Sections ---------- */
        .lp-section { padding: var(--section-y) 0; position: relative; }
        .lp-section.papyrus { background: var(--papyrus); }
        .lp-head { max-width: 720px; margin-bottom: clamp(2rem, 4vw, 3rem); }
        .lp-head .sh-line { display: flex; align-items: center; gap: .8rem; margin-bottom: .6rem; }
        .lp-head .sh-line::before { content: ""; width: 56px; height: 1px; background: var(--egypt-gold); }
        .lp-head .sh-num { font-family: var(--ff-num); color: var(--egypt-gold); font-weight: 700; font-size: 1.15rem; }
        .lp-head h2 { font-size: clamp(1.7rem, 3vw, 2.8rem); margin: 0 0 .6rem; }
        .lp-head p { color: var(--text-soft); margin: 0; font-size: 1.02rem; }
        .lp-head.on-dark h2 { color: var(--white-soft); }
        .lp-head.on-dark p { color: rgba(254,253,252,.72); }

        /* Stats */
        .stats-wrap { margin-top: calc(var(--section-y) * -.55); position: relative; z-index: 2; }
        .gov-stats { grid-template-columns: repeat(6, 1fr); }
        .gov-stats .gs-item { padding: 1.6rem 1.3rem; }
        .gov-stats .gs-num { font-size: clamp(2rem, 3.4vw, 3rem); }
        .stats-note { font-size: .82rem; color: var(--text-soft); margin-top: .9rem; }

        /* Services */
        .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
        .service-card { padding: 1.8rem; }
        .service-card .sc-num { font-family: var(--ff-num); color: var(--egypt-gold); font-size: .95rem; margin-inline-start: auto; }

        /* Identity */
        .identity-grid { display: grid; grid-template-columns: .9fr 1.1fr; gap: clamp(2rem, 5vw, 4rem); align-items: center; }
        .identity-quote { font-family: var(--ff-ar); font-weight: 800; color: var(--egypt-green); font-size: clamp(1.9rem, 3.6vw, 3.1rem); line-height: 1.35; margin: 0; }
        .identity-quote span { color: var(--egypt-gold); }
        .id-tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .id-tile {
            background: var(--white-soft); border: 1px solid var(--line); border-radius: 8px; overflow: hidden;
            display: flex; flex-direction: column;
        }
        .id-tile .id-art { height: 140px; background: var(--egypt-green); position: relative; border-bottom: 1px solid var(--egypt-gold-line); }
        .id-tile .id-art svg { position: absolute; inset: 0; width: 100%; height: 100%; }
        .id-tile .id-body { padding: 1rem 1.1rem 1.2rem; }
        .id-tile h3 { font-size: 1.05rem; margin: 0 0 .35rem; }
        .id-tile p { font-size: .86rem; color: var(--text-soft); margin: 0; line-height: 1.8; }

        /* Centers */
        .centers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .9rem; }
        .center-item {
            display: flex; align-items: center; gap: .85rem; padding: 1rem 1.1rem;
            background: var(--white-soft); border: 1px solid var(--line); border-radius: 8px;
            transition: border-color .5s var(--ease), box-shadow .5s var(--ease);
        }
        .center-item:hover { border-color: var(--line-gold); box-shadow: var(--shadow); }
        .center-item .ci-num { font-family: var(--ff-num); color: var(--egypt-gold); font-size: 1.15rem; width: 28px; }
        .center-item strong { font-family: var(--ff-ar); color: var(--egypt-green); font-weight: 700; display: block; }
        .center-item small { color: var(--text-soft); font-size: .78rem; }

        /* Digital transformation */
        .digital { background: var(--egypt-green-deep); color: var(--white-soft); isolation: isolate; overflow: hidden; }
        .digital::before {
            content: ""; position: absolute; inset: 0; z-index: -1;
            background-image: url("{{ asset('assets/img/egypt-pattern.svg') }}"); background-size: 160px; opacity: .04;
        }
        .chain { display: grid; grid-template-columns: repeat(4, 1fr); position: relative; margin-top: 1rem; }
        .chain::before { content: ""; position: absolute; top: 31px; right: 12%; left: 12%; height: 1px; background: var(--egypt-gold); }
        .chain-step { text-align: center; position: relative; padding: 0 .75rem; }
        .chain-step .cs-node {
            width: 62px; height: 62px; margin: 0 auto 1rem; border: 1px solid var(--egypt-gold); border-radius: 50%;
            background: var(--egypt-green-deep); color: var(--egypt-gold-2); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            position: relative;
        }
        .chain-step .cs-en { font-family: var(--ff-en); letter-spacing: 2px; font-size: .72rem; color: var(--egypt-gold); font-weight: 600; }
        .chain-step h3 { color: var(--white-soft); font-size: 1.2rem; margin: .2rem 0 .5rem; }
        .chain-step p { color: rgba(254,253,252,.68); font-size: .88rem; margin: 0; line-height: 1.85; }
        .digital-points { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--egypt-gold-line); border: 1px solid var(--egypt-gold-line); margin-top: clamp(2.5rem, 5vw, 4rem); }
        .digital-points div { background: var(--egypt-green-deep); padding: 1.3rem 1.4rem; display: flex; gap: .9rem; align-items: flex-start; }
        .digital-points h4 { color: var(--white-soft); font-size: 1rem; margin: 0 0 .25rem; }
        .digital-points p { color: rgba(254,253,252,.65); font-size: .84rem; margin: 0; line-height: 1.75; }

        /* Steps */
        .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.4rem; counter-reset: s; }
        .step { border-top: 1px solid var(--egypt-gold-line); padding-top: 1.2rem; position: relative; }
        .step::before { content: ""; position: absolute; top: -1px; right: 0; width: 40px; height: 2px; background: var(--egypt-gold); }
        .step .st-num { font-family: var(--ff-num); color: var(--egypt-gold); font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .step h3 { font-size: 1.08rem; margin: .7rem 0 .4rem; }
        .step p { color: var(--text-soft); font-size: .88rem; margin: 0; line-height: 1.85; }

        /* CTA */
        .cta-box {
            background: var(--white-soft); border: 1px solid var(--line); border-radius: var(--r-lg); box-shadow: var(--shadow);
            padding: clamp(2rem, 5vw, 3.6rem); text-align: center;
        }
        .cta-box h2 { font-size: clamp(1.6rem, 3vw, 2.4rem); margin: .4rem 0 .6rem; }
        .cta-box p { color: var(--text-soft); margin: 0 auto 1.8rem; max-width: 560px; }

        /* Footer */
        .lp-footer { background: var(--egypt-green-deep); color: rgba(254,253,252,.7); position: relative; isolation: isolate; padding-bottom: 1.5rem; }
        .lp-footer::before {
            content: ""; position: absolute; inset: 0; z-index: -1;
            background-image: url("{{ asset('assets/img/egypt-pattern.svg') }}"); background-size: 160px; opacity: .03;
        }
        .lp-footer .ft-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 2.5rem; padding: 3.2rem 0 2.4rem; }
        .lp-footer img { height: 48px; width: auto; }
        .lp-footer h4 { color: var(--egypt-gold-2); font-size: .95rem; margin: 0 0 1rem; }
        .lp-footer ul { list-style: none; padding: 0; margin: 0; }
        .lp-footer li { margin-bottom: .55rem; font-size: .9rem; }
        .lp-footer a { color: rgba(254,253,252,.75); }
        .lp-footer a:hover { color: var(--egypt-gold-2); }
        .lp-footer .ft-bottom {
            border-top: 1px solid var(--egypt-gold-line); padding-top: 1.2rem;
            display: flex; justify-content: space-between; flex-wrap: wrap; gap: .75rem; font-size: .82rem;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 1199.98px) {
            .hdr-nav { display: none; }
            .burger { display: inline-flex; }
            .gov-stats { grid-template-columns: repeat(3, 1fr); }
            .gov-stats .gs-item:nth-child(4)::before { display: none; }
            .gov-stats .gs-item:nth-child(n+4) { border-top: 1px solid var(--line); }
            .services-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 991.98px) {
            .hero-grid, .identity-grid { grid-template-columns: 1fr; }
            .hero-visual { max-width: 560px; }
            .chain { grid-template-columns: repeat(2, 1fr); row-gap: 2rem; }
            .chain::before { display: none; }
            .digital-points { grid-template-columns: 1fr; }
            .steps { grid-template-columns: repeat(2, 1fr); }
            .lp-footer .ft-grid { grid-template-columns: 1fr 1fr; }
            .lp-footer .ft-brand { grid-column: 1 / -1; }
        }
        @media (max-width: 575.98px) {
            .lp-container { padding-inline: 16px; }
            .hdr-inner { height: 64px; }
            .hdr-logo img { height: 38px; }
            .hdr-actions .btn-hdr span { display: none; }
            .gov-stats { grid-template-columns: repeat(2, 1fr); }
            .gov-stats .gs-item::before { display: none; }
            .gov-stats .gs-item:nth-child(even)::before { display: block; }
            .gov-stats .gs-item:nth-child(n+3) { border-top: 1px solid var(--line); }
            .services-grid, .id-tiles, .steps { grid-template-columns: 1fr; }
            .chain { grid-template-columns: 1fr; }
            .hero-ctas a { width: 100%; justify-content: center; }
            .lp-footer .ft-grid { grid-template-columns: 1fr; }
            .map-label { font-size: 14px; }
        }
    </style>
</head>
<body id="top">
<a href="#main" class="skip-link">تخطَّ إلى المحتوى</a>
<div class="scroll-progress" aria-hidden="true"></div>

{{-- Government identity bar --}}
<div class="gov-idbar lp-idbar">
    <div class="idbar-start">
        <span class="gov-flag" aria-hidden="true"><i></i><i></i><i></i></span>
        <span>جمهورية مصر العربية</span>
        <span class="idbar-sep"></span>
        <strong>محافظة كفر الشيخ</strong>
    </div>
    <div class="idbar-end">
        <span class="idbar-gold idbar-hide-sm">البوابة الإلكترونية الرسمية</span>
        <span class="idbar-sep idbar-hide-sm"></span>
        <span class="idbar-hide-xs"><i class="far fa-calendar ml-1"></i>{{ now()->locale('ar')->translatedFormat('l j F Y') }}</span>
        <span class="idbar-sep idbar-hide-xs"></span>
        <button type="button" onclick="kfsA11y.font(-.1)" aria-label="تصغير الخط">ا-</button>
        <button type="button" onclick="kfsA11y.font(.1)" aria-label="تكبير الخط">ا+</button>
    </div>
</div>

{{-- Header --}}
<header class="hdr" id="hdr">
    <div class="lp-container hdr-inner">
        <a href="#top" class="hdr-logo" aria-label="رابط — الرئيسية"><img src="{{ asset('logo.png') }}" alt="رابط"></a>
        <nav aria-label="التنقل الرئيسي">
            <ul class="hdr-nav">
                <li><a href="#top" class="active">الرئيسية</a></li>
                <li><a href="#stats">المحافظة في أرقام</a></li>
                <li><a href="#services">خدمات المنصة</a></li>
                <li><a href="#identity">كفر الشيخ</a></li>
                <li><a href="#centers">المراكز والمدن</a></li>
                <li><a href="#digital">التحول الرقمي</a></li>
            </ul>
        </nav>
        <div class="hdr-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-hdr"><i class="fas fa-gauge-high"></i><span>لوحة التحكم</span></a>
            @else
                <a href="{{ route('login') }}" class="btn-hdr"><i class="fas fa-right-to-bracket"></i><span>تسجيل الدخول</span></a>
            @endauth
            <button class="burger" type="button" aria-label="فتح القائمة" aria-controls="mmenu" aria-expanded="false" onclick="lpMenu(true)">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

{{-- Mobile menu --}}
<div class="mmenu egypt-pattern on-dark" id="mmenu" role="dialog" aria-modal="true" aria-label="القائمة">
    <div class="mm-top">
        <img src="{{ asset('logo.png') }}" alt="رابط">
        <button class="burger" style="display:inline-flex" type="button" aria-label="إغلاق القائمة" onclick="lpMenu(false)"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="egypt-cornice" style="margin-top:1.2rem" aria-hidden="true"></div>
    <ul>
        <li><a href="#top" onclick="lpMenu(false)">الرئيسية <i class="fas fa-arrow-left"></i></a></li>
        <li><a href="#stats" onclick="lpMenu(false)">المحافظة في أرقام <i class="fas fa-arrow-left"></i></a></li>
        <li><a href="#services" onclick="lpMenu(false)">خدمات المنصة <i class="fas fa-arrow-left"></i></a></li>
        <li><a href="#identity" onclick="lpMenu(false)">كفر الشيخ <i class="fas fa-arrow-left"></i></a></li>
        <li><a href="#centers" onclick="lpMenu(false)">المراكز والمدن <i class="fas fa-arrow-left"></i></a></li>
        <li><a href="#digital" onclick="lpMenu(false)">التحول الرقمي <i class="fas fa-arrow-left"></i></a></li>
        <li><a href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'لوحة التحكم' : 'تسجيل الدخول' }} <i class="fas fa-arrow-left"></i></a></li>
    </ul>
    <div class="mm-foot">جمهورية مصر العربية — محافظة كفر الشيخ</div>
</div>

<main id="main" tabindex="-1">

    {{-- Hero --}}
    <section class="hero" aria-labelledby="heroTitle">
        <div class="lp-container hero-grid">
            <div data-reveal>
                <span class="gov-badge">
                    <img src="{{ asset('logo.png') }}" alt="" aria-hidden="true">
                    البوابة الإلكترونية الرسمية لمحافظة كفر الشيخ
                </span>
                <h1 id="heroTitle">منصة <span class="gold">رابط</span><br>لتداول البيانات المكانية<br>والوصفية</h1>
                <p class="lead">
                    منصة حكومية موحدة تربط ديوان عام المحافظة بالمراكز والمدن والقرى والمديريات والهيئات،
                    لتداول البيانات وتحديثها عبر نماذج ذكية وقوالب Excel، وعرضها على خريطة تفاعلية،
                    ودعم القيادات في اتخاذ القرار.
                </p>
                <div class="hero-ctas">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-hero-primary">
                        <i class="fas fa-right-to-bracket"></i> الدخول إلى المنصة
                    </a>
                    <a href="#identity" class="btn-hero-ghost">
                        استكشف المحافظة <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                <div class="hero-meta">
                    <span><i class="fas fa-shield-halved"></i>صلاحيات حسب الجهة والدور</span>
                    <span><i class="fas fa-lock"></i>مصادقة ثنائية</span>
                    <span><i class="fas fa-clock-rotate-left"></i>سجل تدقيق كامل</span>
                </div>
            </div>

            <figure class="hero-visual m-0" data-reveal data-delay=".15" aria-label="خريطة حدود محافظة كفر الشيخ ومراكزها">
                <svg viewBox="0 0 600 420" role="img" aria-labelledby="mapTitle">
                    <title id="mapTitle">حدود محافظة كفر الشيخ ومواقع المراكز</title>
                    <g class="map-grid" aria-hidden="true">
                        @for ($i = 1; $i < 6; $i++)
                            <line x1="0" y1="{{ $i * 70 }}" x2="600" y2="{{ $i * 70 }}"/>
                        @endfor
                        @for ($i = 1; $i < 10; $i++)
                            <line x1="{{ $i * 60 }}" y1="0" x2="{{ $i * 60 }}" y2="420"/>
                        @endfor
                    </g>
                    @if ($outlinePath)
                        <path class="map-outline-2" d="{{ $outlinePath }}"/>
                        <path class="map-outline" d="{{ $outlinePath }}"/>
                    @endif
                    @php $pts = collect($centers)->filter(fn ($c) => $c['x'] !== null)->values(); $hub = $pts->first(); @endphp
                    @if ($hub)
                        @foreach ($pts->skip(1) as $c)
                            <line class="map-link" x1="{{ $hub['x'] }}" y1="{{ $hub['y'] }}" x2="{{ $c['x'] }}" y2="{{ $c['y'] }}"/>
                        @endforeach
                    @endif
                    @foreach ($pts as $c)
                        <circle class="map-ring" cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="9"/>
                        <circle class="map-dot" cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="3.2"/>
                        <text class="map-label" x="{{ $c['x'] }}" y="{{ $c['y'] - 14 }}" text-anchor="middle">{{ $c['short'] }}</text>
                    @endforeach
                    {{-- north + sun disk --}}
                    <g transform="translate(560 44)" aria-hidden="true">
                        <circle r="16" fill="none" stroke="rgba(184,149,79,.5)"/>
                        <path d="M0 -11 L5 6 L0 2 L-5 6Z" fill="#D1B574"/>
                    </g>
                </svg>
                <figcaption class="hv-caption">
                    <span>حدود المحافظة ومواقع المراكز الإدارية</span>
                    <strong>{{ count($centers) }} مركز ومدينة</strong>
                </figcaption>
            </figure>
        </div>
    </section>
    <div class="egypt-cornice" aria-hidden="true"></div>

    {{-- Statistics --}}
    <section id="stats" class="lp-section pt-0" aria-labelledby="statsTitle" style="padding-bottom:calc(var(--section-y) * .6)">
        <div class="lp-container stats-wrap">
            <h2 id="statsTitle" class="visually-hidden">محافظة كفر الشيخ في أرقام</h2>
            <div class="gov-stats" data-reveal>
                @foreach ($stats as $s)
                    <div class="gs-item">
                        <div class="gs-icon"><i class="fas {{ $s['icon'] }}"></i></div>
                        <div class="gs-num" data-count="{{ $s['value'] }}">{{ number_format($s['value']) }}</div>
                        <div class="gs-label">{{ $s['label'] }}</div>
                    </div>
                @endforeach
            </div>
            <p class="stats-note"><i class="fas fa-circle-info ml-1 text-gold"></i>أرقام محدثة آلياً من قاعدة بيانات المنصة.</p>
        </div>
    </section>

    {{-- Services --}}
    <section id="services" class="lp-section papyrus" aria-labelledby="servicesTitle">
        <div class="lp-container">
            <div class="lp-head" data-reveal>
                <div class="sh-line"><span class="sh-num">01</span></div>
                <h2 id="servicesTitle">خدمات المنصة</h2>
                <p>أدوات رقمية متكاملة لكل مستوى إداري — من مدخل البيانات بالوحدة المحلية حتى القيادة العليا.</p>
            </div>
            @php
                $services = [
                    ['fa-map-location-dot', 'الخريطة التفاعلية', 'كل البيانات المكانية للمحافظة على خريطة واحدة: المراكز والقرى والمنشآت والخدمات، مع بحث وتصفية واستعراض فوري للمعلومة.'],
                    ['fa-file-lines', 'تداول النماذج', 'نماذج بيانات موحدة توجَّه للجهات المعنية، مع دورة عمل للإدخال والمراجعة والاعتماد وتتبع كامل للحالة.'],
                    ['fa-file-excel', 'قوالب Excel', 'تحميل قالب كل نموذج وتعبئته دون اتصال ثم إعادة رفعه للإضافة أو التعديل الجماعي، مع تحقق آلي من البيانات.'],
                    ['fa-chess-king', 'دعم القرار للقيادات', 'مساعد ذكي يحلل مؤشرات المحافظة ويقدم ملخصات وتنبيهات وتوصيات تساعد القيادات على اتخاذ القرار.'],
                    ['fa-sitemap', 'الهيكل التنظيمي والوظيفي', 'شجرة موحدة للمحافظة ومراكزها ومدنها وقراها ومديرياتها وهيئاتها، مع الهيكل الوظيفي وشاغلي الوظائف.'],
                    ['fa-chart-column', 'التقارير', 'تقارير رسمية قابلة للاعتماد والإرسال والتصدير، مع إصدارات محفوظة لكل تقرير.'],
                ];
            @endphp
            <div class="services-grid">
                @foreach ($services as $i => [$icon, $title, $desc])
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="service-card" data-reveal data-delay="{{ ($i % 3) * .08 }}">
                        <div class="d-flex align-items-start">
                            <span class="eg-icon"><i class="fas {{ $icon }}"></i></span>
                            <span class="sc-num">{{ sprintf('%02d', $i + 1) }}</span>
                        </div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                        <span class="sc-link">الدخول للخدمة <i class="fas fa-arrow-left mr-1"></i></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kafr El-Sheikh identity --}}
    <section id="identity" class="lp-section egypt-pattern is-light" aria-labelledby="identityTitle">
        <div class="lp-container identity-grid">
            <div data-reveal>
                <div class="lp-head mb-4">
                    <div class="sh-line"><span class="sh-num">02</span></div>
                    <h2 id="identityTitle">كفر الشيخ</h2>
                </div>
                <p class="identity-quote">أرض التاريخ<br><span>والتنمية</span><br>والعطاء</p>
                <p class="mt-4 text-muted" style="max-width:480px">
                    محافظة ساحلية زراعية في شمال الدلتا، تطل على البحر المتوسط وتحتضن بحيرة البرلس،
                    وتمثل الزراعة والثروة السمكية ركيزتين أساسيتين لاقتصادها.
                </p>
            </div>
            <div class="id-tiles">
                <article class="id-tile" data-reveal>
                    <div class="id-art" aria-hidden="true">
                        <svg viewBox="0 0 200 140" preserveAspectRatio="xMidYMid slice">
                            <circle cx="150" cy="48" r="18" fill="none" stroke="#B8954F"/>
                            <path d="M0 92h200M0 100h200" stroke="rgba(184,149,79,.5)"/>
                            <path d="M0 112 q25 -8 50 0 t50 0 t50 0 t50 0" fill="none" stroke="rgba(209,181,116,.6)"/>
                            <path d="M0 124 q25 -8 50 0 t50 0 t50 0 t50 0" fill="none" stroke="rgba(209,181,116,.35)"/>
                            <path d="M40 92 l12 -22 l12 22" fill="none" stroke="#D1B574"/>
                        </svg>
                    </div>
                    <div class="id-body">
                        <h3>بحيرة البرلس</h3>
                        <p>محمية طبيعية ومصدر رئيسي للثروة السمكية ومقصد للطيور المهاجرة.</p>
                    </div>
                </article>
                <article class="id-tile" data-reveal data-delay=".08">
                    <div class="id-art" aria-hidden="true">
                        <svg viewBox="0 0 200 140" preserveAspectRatio="xMidYMid slice">
                            @for ($i = 0; $i < 7; $i++)
                                <path d="M{{ 20 + $i * 28 }} 130 V70 M{{ 20 + $i * 28 }} 70 l-9 -14 h18 z" fill="none" stroke="{{ $i % 2 ? 'rgba(209,181,116,.55)' : '#B8954F' }}"/>
                            @endfor
                            <path d="M0 130h200" stroke="#B8954F"/>
                        </svg>
                    </div>
                    <div class="id-body">
                        <h3>الزراعة</h3>
                        <p>من أهم محافظات إنتاج الأرز والقطن والمحاصيل الاستراتيجية في مصر.</p>
                    </div>
                </article>
                <article class="id-tile" data-reveal data-delay=".16">
                    <div class="id-art" aria-hidden="true">
                        <svg viewBox="0 0 200 140" preserveAspectRatio="xMidYMid slice">
                            <path d="M100 30 v80 M88 110 h24 M92 30 h16 v12 h-16z" fill="none" stroke="#D1B574"/>
                            <path d="M70 52 l30 -6 l30 6" fill="none" stroke="rgba(184,149,79,.6)"/>
                            <path d="M0 118 h200 M0 124 h200" stroke="rgba(184,149,79,.45)"/>
                        </svg>
                    </div>
                    <div class="id-body">
                        <h3>الساحل</h3>
                        <p>شريط ساحلي على البحر المتوسط يضم بلطيم وبرج البرلس ومشروعات تنموية واعدة.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Centers --}}
    <section id="centers" class="lp-section papyrus" aria-labelledby="centersTitle">
        <div class="lp-container">
            <div class="lp-head" data-reveal>
                <div class="sh-line"><span class="sh-num">03</span></div>
                <h2 id="centersTitle">المراكز والمدن</h2>
                <p>التقسيم الإداري للمحافظة كما هو مسجل بالمنصة، وتتبع كل مركز مدنه ووحداته المحلية وقراه.</p>
            </div>
            <div class="centers-grid">
                @forelse ($centers as $i => $c)
                    <div class="center-item" data-reveal data-delay="{{ ($i % 4) * .05 }}">
                        <span class="ci-num">{{ sprintf('%02d', $i + 1) }}</span>
                        <div>
                            <strong>{{ $c['name'] }}</strong>
                            <small>محافظة كفر الشيخ</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">لم تُسجل المراكز بعد.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Digital transformation --}}
    <section id="digital" class="lp-section digital" aria-labelledby="digitalTitle">
        <div class="lp-container">
            <div class="lp-head on-dark" data-reveal>
                <div class="sh-line"><span class="sh-num">04</span></div>
                <h2 id="digitalTitle">مستقبل حكومي أكثر ذكاءً وسرعةً</h2>
                <p>من البيان الخام إلى القرار المدروس — مسار واضح يحكم تداول البيانات داخل المحافظة.</p>
            </div>
            <div class="chain">
                @foreach ([
                    ['fa-database', 'DATA', 'البيانات', 'تُجمع من الجهات عبر النماذج وقوالب Excel وقواعد البيانات القطاعية.'],
                    ['fa-layer-group', 'INFORMATION', 'المعلومات', 'تُنظم وتُربط بالهيكل الإداري والموقع الجغرافي وتُراجع وتُعتمد.'],
                    ['fa-lightbulb', 'KNOWLEDGE', 'المعرفة', 'تُحلل في مؤشرات وتقارير وخرائط تكشف الفجوات والأولويات.'],
                    ['fa-scale-balanced', 'DECISION', 'القرار', 'تصل للقيادات في صورة توصيات وملخصات تدعم القرار الرشيد.'],
                ] as $i => [$icon, $en, $ar, $desc])
                    <div class="chain-step" data-reveal data-delay="{{ $i * .1 }}">
                        <div class="cs-node"><i class="fas {{ $icon }}"></i></div>
                        <div class="cs-en">{{ $en }}</div>
                        <h3>{{ $ar }}</h3>
                        <p>{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
            <div class="digital-points" data-reveal>
                <div><span class="eg-icon sm on-dark"><i class="fas fa-shield-halved"></i></span><div><h4>الأمان والصلاحيات</h4><p>كل مستخدم يرى بيانات جهته فقط وفق دوره ونطاقه.</p></div></div>
                <div><span class="eg-icon sm on-dark"><i class="fas fa-eye"></i></span><div><h4>الشفافية</h4><p>سجل تدقيق يوثق كل إضافة وتعديل واعتماد.</p></div></div>
                <div><span class="eg-icon sm on-dark"><i class="fas fa-link"></i></span><div><h4>التكامل</h4><p>ربط المراكز والمديريات والهيئات في منظومة بيانات واحدة.</p></div></div>
            </div>
        </div>
    </section>
    <div class="egypt-cornice" aria-hidden="true"></div>

    {{-- How it works --}}
    <section class="lp-section" aria-labelledby="stepsTitle">
        <div class="lp-container">
            <div class="lp-head" data-reveal>
                <div class="sh-line"><span class="sh-num">05</span></div>
                <h2 id="stepsTitle">كيف تعمل المنصة</h2>
                <p>دورة عمل حكومية واضحة من إنشاء النموذج حتى وصول المعلومة لمتخذ القرار.</p>
            </div>
            <div class="steps">
                @foreach ([
                    ['إعداد النموذج وتوجيهه', 'تُصمم الجهة المختصة النموذج وتحدد الجهات المطلوب منها البيانات.'],
                    ['إدخال البيانات', 'تُدخل الجهات البيانات مباشرة أو عبر تحميل قالب Excel وإعادة رفعه.'],
                    ['المراجعة والاعتماد', 'يراجع المسؤولون البيانات ويعتمدونها أو يعيدونها للاستكمال.'],
                    ['العرض ودعم القرار', 'تظهر البيانات على الخريطة واللوحات والتقارير ومساعد دعم القرار.'],
                ] as $i => [$t, $d])
                    <div class="step" data-reveal data-delay="{{ $i * .08 }}">
                        <div class="st-num">{{ sprintf('%02d', $i + 1) }}</div>
                        <h3>{{ $t }}</h3>
                        <p>{{ $d }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="lp-section papyrus pt-0" style="padding-top:0;background:linear-gradient(180deg,var(--ivory) 50%,var(--papyrus) 50%)" aria-labelledby="ctaTitle">
        <div class="lp-container">
            <div class="cta-box egypt-frame" data-reveal>
                <span class="eg-icon lg"><i class="fas fa-handshake"></i></span>
                <h2 id="ctaTitle">ابدأ تداول بيانات جهتك إلكترونياً</h2>
                <p>وصول أسرع وأدق إلى بيانات المحافظة، بصلاحيات آمنة تناسب دورك وجهتك.</p>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-kfs btn-lg px-4">
                    ابدأ الآن <i class="fas fa-arrow-left mr-2"></i>
                </a>
            </div>
        </div>
    </section>
</main>

{{-- Footer --}}
<footer class="lp-footer" role="contentinfo">
    <div class="egypt-cornice" aria-hidden="true"></div>
    <div class="lp-container">
        <div class="ft-grid">
            <div class="ft-brand">
                <img src="{{ asset('logo.png') }}" alt="رابط">
                <p class="mt-3 mb-0" style="max-width:360px;line-height:1.9">
                    البوابة الإلكترونية الرسمية لتداول البيانات المكانية والوصفية<br>بمحافظة كفر الشيخ
                </p>
            </div>
            <div>
                <h4>روابط مهمة</h4>
                <ul>
                    <li><a href="#services">خدمات المنصة</a></li>
                    <li><a href="#stats">المحافظة في أرقام</a></li>
                    <li><a href="#centers">المراكز والمدن</a></li>
                    <li><a href="{{ route('login') }}">تسجيل الدخول</a></li>
                </ul>
            </div>
            <div>
                <h4>الدعم الفني</h4>
                <ul>
                    <li><i class="fas fa-building ml-2 text-gold"></i>ديوان عام محافظة كفر الشيخ</li>
                    <li><i class="fas fa-circle-info ml-2 text-gold"></i>للحصول على حساب يُرجى التواصل مع مسؤول النظام بجهتك</li>
                </ul>
            </div>
        </div>
        <div class="ft-bottom">
            <span>جمهورية مصر العربية — محافظة كفر الشيخ</span>
            <span>&copy; {{ date('Y') }} جميع الحقوق محفوظة</span>
        </div>
    </div>
</footer>

<div class="float-stack">
    <button type="button" class="float-btn to-top" aria-label="العودة إلى الأعلى"><i class="fas fa-arrow-up"></i></button>
</div>

<script src="{{ asset('assets/js/kfs-royal.js') }}?v={{ config('app.asset_version') }}"></script>
<script>
    function lpMenu(open) {
        const m = document.getElementById('mmenu');
        m.classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
        document.querySelectorAll('.hdr .burger').forEach(b => b.setAttribute('aria-expanded', open));
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') lpMenu(false); });
    (function () {
        const hdr = document.getElementById('hdr');
        const links = [...document.querySelectorAll('.hdr-nav a')];
        const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);
        const onScroll = () => {
            hdr.classList.toggle('is-stuck', window.scrollY > 40);
            let cur = sections[0];
            sections.forEach(s => { if (s.getBoundingClientRect().top < 140) cur = s; });
            links.forEach(a => a.classList.toggle('active', document.querySelector(a.getAttribute('href')) === cur));
        };
        window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
    })();
</script>
@include('partials._pwa-scripts')
</body>
</html>
