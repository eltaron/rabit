<!DOCTYPE html>
<html lang="ar" dir="rtl" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'لوحة التحكم') — رابط | البوابة الرسمية لمحافظة كفر الشيخ</title>
    <meta name="description" content="@yield('description', 'رابط — المنصة الرسمية لمحافظة كفر الشيخ لتداول البيانات المكانية والوصفية ودعم اتخاذ القرار.')">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="محافظة كفر الشيخ">

    @include('partials._pwa-head')

    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_EG">
    <meta property="og:site_name" content="رابط — محافظة كفر الشيخ">
    <meta property="og:title" content="@yield('title', 'لوحة التحكم') — رابط">
    <meta property="og:image" content="{{ asset('logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/kfs-royal.css') }}?v={{ config('app.asset_version') }}" rel="stylesheet">
    <script>
        /* apply accessibility prefs before paint */
        try { var s = JSON.parse(localStorage.getItem('kfs.scale') || '1'); document.documentElement.style.setProperty('--font-scale', s);
              if (JSON.parse(localStorage.getItem('kfs.hc') || 'false')) document.documentElement.classList.add('hc');
              if (JSON.parse(localStorage.getItem('kfs.motion') || 'false')) document.documentElement.classList.add('reduce-motion'); } catch (e) {}
    </script>

    @livewireStyles
    @stack('styles')
</head>
<body data-cursor="on">
<a href="#main" class="skip-link">تخطَّ إلى المحتوى</a>
<div class="scroll-progress" aria-hidden="true"></div>

@include('partials._loader')

{{-- شريط الهوية الحكومية --}}
<div class="app-idbar-wrap">
    <div class="gov-idbar" role="banner">
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
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

@include('partials._sidebar')

<div class="main-content">
    @include('partials._navbar')

    <main class="content-wrapper" id="main" tabindex="-1">
        <header class="page-header">
            <div>
                @hasSection('breadcrumb')
                    <nav aria-label="مسار التنقل">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                @endif
                <h4>@yield('page-title', 'لوحة التحكم')</h4>
                @hasSection('page-subtitle')
                    <div class="page-sub">@yield('page-subtitle')</div>
                @endif
            </div>
            <div class="page-actions">
                @yield('page-actions')
            </div>
        </header>

        @foreach (['success' => ['alert-success', 'fa-circle-check'], 'error' => ['alert-danger', 'fa-triangle-exclamation'], 'warning' => ['alert-warning', 'fa-circle-exclamation'], 'info' => ['alert-info', 'fa-circle-info']] as $key => [$cls, $icon])
            @if (session($key))
                <div class="container-fluid px-4 pt-3">
                    <div class="alert {{ $cls }} alert-dismissible fade show d-flex align-items-start gap-2 mb-0" role="alert">
                        <i class="fas {{ $icon }} mt-1"></i>
                        <div>{!! nl2br(e(session($key))) !!}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                    </div>
                </div>
            @endif
        @endforeach

        @if ($errors->any() && ! View::hasSection('suppress-errors'))
            <div class="container-fluid px-4 pt-3">
                <div class="alert alert-danger mb-0" role="alert">
                    <div class="fw-bold mb-1"><i class="fas fa-triangle-exclamation ml-1"></i>يرجى مراجعة الأخطاء التالية:</div>
                    <ul class="mb-0 pl-0 pr-3">
                        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="p-4">
            @yield('content')
        </div>
    </main>

    @include('partials._footer')
</div>

{{-- عناصر عائمة محدودة: إمكانية الوصول + العودة للأعلى --}}
<div class="float-stack no-print">
    <button type="button" class="float-btn to-top" aria-label="العودة إلى الأعلى"><i class="fas fa-arrow-up"></i></button>
    <button type="button" class="float-btn" id="a11yBtn" onclick="kfsA11y.toggle()" aria-expanded="false" aria-controls="a11yPanel" title="إمكانية الوصول">
        <i class="fas fa-universal-access"></i>
    </button>
</div>
<div class="a11y-panel" id="a11yPanel" role="dialog" aria-label="إعدادات إمكانية الوصول">
    <h6><i class="fas fa-universal-access ml-1 text-gold"></i>إمكانية الوصول</h6>
    <div class="a11y-row">
        <span>حجم الخط <small class="text-muted" id="a11yScaleLabel">100%</small></span>
        <span class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary" onclick="kfsA11y.font(-.1)" aria-label="تصغير">ا-</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="kfsA11y.font(0)" aria-label="افتراضي">ا</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="kfsA11y.font(.1)" aria-label="تكبير">ا+</button>
        </span>
    </div>
    <label class="a11y-row" for="a11yContrast"><span>تباين عالٍ</span>
        <span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="a11yContrast" onchange="kfsA11y.contrast(this.checked)"></span></label>
    <label class="a11y-row" for="a11yMotion"><span>إيقاف الحركة</span>
        <span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="a11yMotion" onchange="kfsA11y.motion(this.checked)"></span></label>
    <div class="a11y-row"><a href="{{ route('manual') }}"><i class="fas fa-book-open ml-1"></i>دليل الاستخدام</a>
        <button class="btn btn-sm btn-link p-0" onclick="kfsA11y.reset()">إعادة الضبط</button></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script src="{{ asset('assets/js/kfs-royal.js') }}?v={{ config('app.asset_version') }}"></script>

@livewireScripts
@include('partials._pwa-scripts')
@stack('scripts')
</body>
</html>
