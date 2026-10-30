<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'تسجيل الدخول') — رابط | محافظة كفر الشيخ</title>
    <meta name="description" content="الدخول إلى رابط — المنصة الرسمية لتداول البيانات بمحافظة كفر الشيخ">

    @include('partials._pwa-head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/kfs-royal.css') }}?v={{ config('app.asset_version') }}" rel="stylesheet">

    <style>
        html, body { overflow-x: clip; }
        body { min-height: 100vh; display: flex; flex-direction: column; background: var(--ivory); }
        .auth-shell { flex: 1; display: grid; grid-template-columns: 1fr 1fr; min-height: calc(100vh - var(--idbar-height)); }

        /* Official panel */
        .auth-panel {
            position: relative; isolation: isolate; overflow: hidden; color: var(--white-soft);
            background: linear-gradient(160deg, var(--egypt-green-deep), var(--egypt-green) 70%, var(--egypt-green-2));
            padding: clamp(2rem, 5vw, 4rem); display: flex; flex-direction: column; justify-content: space-between; gap: 2rem;
        }
        .auth-panel::before {
            content: ""; position: absolute; inset: 0; z-index: -1;
            background-image: url("{{ asset('assets/img/egypt-pattern.svg') }}"); background-size: 160px; opacity: .06;
        }
        .auth-panel::after { content: ""; position: absolute; inset: 18px; border: 1px solid var(--egypt-gold-line); pointer-events: none; z-index: -1; }
        .auth-panel .ap-logo img { height: 64px; width: auto; }
        .auth-panel h1 { color: var(--white-soft); font-size: clamp(1.7rem, 3vw, 2.6rem); line-height: 1.35; margin: 1.6rem 0 .9rem; }
        .auth-panel h1 span { color: var(--egypt-gold-2); }
        .auth-panel p { color: rgba(254,253,252,.75); max-width: 440px; line-height: 1.95; margin: 0; }
        .ap-list { list-style: none; padding: 0; margin: 1.8rem 0 0; display: grid; gap: .8rem; }
        .ap-list li { display: flex; align-items: center; gap: .8rem; color: rgba(254,253,252,.85); font-size: .92rem; }
        .ap-foot { font-size: .8rem; color: rgba(254,253,252,.6); border-top: 1px solid var(--egypt-gold-line); padding-top: 1rem; }

        /* Form side */
        .auth-main { display: flex; align-items: center; justify-content: center; padding: clamp(1.5rem, 5vw, 4rem) 16px; }
        .auth-card {
            width: 100%; max-width: 440px; background: var(--white-soft);
            border: 1px solid var(--line); border-top: 3px solid var(--egypt-gold); border-radius: var(--r-lg);
            box-shadow: var(--shadow); padding: clamp(1.5rem, 4vw, 2.4rem);
        }
        .auth-card .ac-head { margin-bottom: 1.6rem; }
        .auth-card .ac-head h2 { font-size: 1.5rem; margin: .6rem 0 .3rem; }
        .auth-card .ac-head p { color: var(--text-soft); margin: 0; font-size: .9rem; }
        .auth-card .form-control { padding: .7rem .95rem; font-size: .95rem; }
        .auth-card .btn-kfs { padding: .75rem 1rem; font-size: .95rem; }
        .pw-wrap { position: relative; }
        .pw-wrap .form-control { padding-left: 2.8rem; }
        .pw-toggle {
            position: absolute; left: 6px; top: 50%; transform: translateY(-50%);
            border: 0; background: transparent; color: var(--text-soft); width: 36px; height: 36px; border-radius: var(--r);
        }
        .pw-toggle:hover, .pw-toggle:focus-visible { color: var(--egypt-gold); }
        .otp-input { font-family: var(--ff-en); font-size: 1.6rem !important; letter-spacing: .6rem; text-align: center; }
        .auth-back { display: inline-flex; align-items: center; gap: .4rem; color: var(--text-soft); font-size: .86rem; }
        .auth-mobile-brand { display: none; text-align: center; margin-bottom: 1.2rem; }
        .auth-mobile-brand .amb {
            display: inline-flex; background: var(--egypt-green-deep); padding: .7rem 1.1rem; border-radius: var(--r-lg); border: 1px solid var(--egypt-gold-line);
        }
        .auth-mobile-brand img { height: 44px; width: auto; }

        @media (max-width: 991.98px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-panel { display: none; }
            .auth-mobile-brand { display: block; }
        }
    </style>
    @stack('styles')
</head>
<body>
<a href="#authMain" class="skip-link">تخطَّ إلى المحتوى</a>

<div class="gov-idbar">
    <div class="idbar-start">
        <span class="gov-flag" aria-hidden="true"><i></i><i></i><i></i></span>
        <span>جمهورية مصر العربية</span>
        <span class="idbar-sep"></span>
        <strong>محافظة كفر الشيخ</strong>
    </div>
    <div class="idbar-end">
        <span class="idbar-gold idbar-hide-sm">البوابة الإلكترونية الرسمية</span>
        <span class="idbar-sep idbar-hide-sm"></span>
        <button type="button" onclick="kfsA11y.font(-.1)" aria-label="تصغير الخط">ا-</button>
        <button type="button" onclick="kfsA11y.font(.1)" aria-label="تكبير الخط">ا+</button>
    </div>
</div>

<div class="auth-shell">
    <aside class="auth-panel" aria-label="عن المنصة">
        <div>
            <a href="{{ route('landing') }}" class="ap-logo" aria-label="الصفحة الرئيسية"><img src="{{ asset('logo.png') }}" alt="رابط"></a>
            <h1>منصة <span>رابط</span><br>لتداول البيانات الحكومية</h1>
            <p>المنصة الرسمية لمحافظة كفر الشيخ لتداول البيانات المكانية والوصفية بين الديوان العام والمراكز والمدن والقرى والمديريات والهيئات.</p>
            <div class="egypt-cornice mt-4" style="max-width:220px" aria-hidden="true"></div>
            <ul class="ap-list">
                <li><span class="eg-icon sm on-dark"><i class="fas fa-shield-halved"></i></span>صلاحيات حسب الجهة والدور الوظيفي</li>
                <li><span class="eg-icon sm on-dark"><i class="fas fa-map-location-dot"></i></span>خريطة تفاعلية لكل بيانات المحافظة</li>
                <li><span class="eg-icon sm on-dark"><i class="fas fa-file-excel"></i></span>قوالب Excel للإدخال والتحديث الجماعي</li>
            </ul>
        </div>
        <div class="ap-foot">جمهورية مصر العربية — محافظة كفر الشيخ &nbsp;·&nbsp; &copy; {{ date('Y') }}</div>
    </aside>

    <main class="auth-main" id="authMain" tabindex="-1">
        <div class="w-100" style="max-width:440px">
            <div class="auth-mobile-brand">
                <a href="{{ route('landing') }}" class="amb" aria-label="الصفحة الرئيسية"><img src="{{ asset('logo.png') }}" alt="رابط"></a>
            </div>

            @foreach (['success' => 'alert-success', 'error' => 'alert-danger', 'warning' => 'alert-warning', 'status' => 'alert-info'] as $key => $cls)
                @if (session($key) && is_string(session($key)))
                    <div class="alert {{ $cls }} mb-3" role="alert">{{ session($key) }}</div>
                @endif
            @endforeach

            <div class="auth-card">
                @yield('content')
            </div>

            <p class="text-center mt-3 mb-0">
                <a href="{{ route('landing') }}" class="auth-back"><i class="fas fa-arrow-right"></i>العودة إلى الصفحة الرئيسية</a>
            </p>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/kfs-royal.js') }}?v={{ config('app.asset_version') }}"></script>
@stack('scripts')
@include('partials._pwa-scripts')
</body>
</html>
