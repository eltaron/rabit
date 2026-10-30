<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('error-code', 'خطأ') — @yield('error-title', 'تعذّر عرض الصفحة') | رابط — محافظة كفر الشيخ</title>

    @include('partials._pwa-head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/kfs-royal.css') }}?v={{ config('app.asset_version') }}" rel="stylesheet">

    <style>
        html, body { overflow-x: clip; }
        body { min-height: 100vh; display: flex; flex-direction: column; background: var(--ivory); }
        .err-head {
            background: var(--egypt-green-deep); border-bottom: 1px solid var(--egypt-gold-line);
            display: flex; justify-content: center; padding: 1rem 16px;
        }
        .err-head img { height: 44px; width: auto; }
        .err-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: clamp(2rem, 6vw, 5rem) 16px; }
        .err-card {
            width: 100%; max-width: 560px; text-align: center; background: var(--white-soft);
            border: 1px solid var(--line); border-radius: var(--r-lg); box-shadow: var(--shadow);
            padding: clamp(2rem, 5vw, 3.2rem) clamp(1.25rem, 4vw, 2.6rem);
        }
        .err-code { font-family: var(--ff-num); font-weight: 700; color: var(--egypt-gold); font-size: clamp(3rem, 9vw, 5rem); line-height: 1; }
        .err-title { font-size: clamp(1.35rem, 3vw, 1.8rem); margin: .9rem 0 .6rem; }
        .err-desc { color: var(--text-soft); margin: 0 auto 1.8rem; max-width: 420px; line-height: 1.95; }
        .err-actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; }
        .err-card .egypt-cornice { margin: 1.8rem 0 0; }
        .err-foot { text-align: center; color: var(--text-soft); font-size: .8rem; padding: 0 16px 1.4rem; }
        @media (max-width: 575.98px) { .err-actions .btn { width: 100%; } }
    </style>
</head>
<body>
<div class="gov-idbar">
    <div class="idbar-start">
        <span class="gov-flag" aria-hidden="true"><i></i><i></i><i></i></span>
        <span>جمهورية مصر العربية</span>
        <span class="idbar-sep"></span>
        <strong>محافظة كفر الشيخ</strong>
    </div>
    <div class="idbar-end"><span class="idbar-gold idbar-hide-sm">البوابة الإلكترونية الرسمية</span></div>
</div>
<header class="err-head">
    <a href="{{ url('/') }}" aria-label="الصفحة الرئيسية"><img src="{{ asset('logo.png') }}" alt="رابط"></a>
</header>

<main class="err-main">
    <div class="err-card egypt-frame">
        @yield('content')
        <div class="egypt-cornice is-soft" aria-hidden="true"></div>
    </div>
</main>

<div class="err-foot">جمهورية مصر العربية — محافظة كفر الشيخ &nbsp;·&nbsp; &copy; {{ date('Y') }}</div>
@include('partials._pwa-scripts')
</body>
</html>
