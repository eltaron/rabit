{{-- PWA: manifest + أيقونات + ألوان الهوية (KFS Royal Egyptian) --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v={{ config('app.asset_version') }}">
<meta name="theme-color" content="#0A2924">
<meta name="color-scheme" content="light">
<meta name="application-name" content="رابط">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="رابط">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="msapplication-TileColor" content="#0A2924">
<meta name="msapplication-TileImage" content="{{ asset('assets/img/pwa/icon-144.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/pwa/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/pwa/favicon-16.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/img/pwa/icon-192.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/pwa/apple-touch-icon.png') }}">
