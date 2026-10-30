<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>غير متصل — رابط | محافظة كفر الشيخ</title>
    <meta name="robots" content="noindex">
    @include('partials._pwa-head')
    {{-- لا خطوط خارجية: الصفحة يجب أن تعمل بالكامل بدون اتصال --}}
    <link href="{{ asset('assets/css/kfs-royal.css') }}?v={{ config('app.asset_version') }}" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', 'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, sans-serif; background: var(--ivory, #FAF8F2); color: var(--charcoal, #202522); margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .off-head { background: var(--egypt-green-deep, #0A2924); color: #FEFDFC; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid rgba(184,149,79,.28); }
        .off-head img { height: 40px; width: auto; }
        .off-head small { color: rgba(254,253,252,.72); font-size: .78rem; }
        .off-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 16px; }
        .off-card { position: relative; background: var(--white-soft, #FEFDFC); border: 1px solid rgba(18,63,53,.12); border-radius: 10px; box-shadow: 0 25px 65px rgba(18,63,53,.10); max-width: 560px; width: 100%; padding: 2.5rem 2rem 2rem; text-align: center; }
        .off-card::before { content: ""; position: absolute; inset: 10px; border: 1px solid rgba(184,149,79,.28); border-radius: 6px; pointer-events: none; }
        .off-card::after { content: ""; position: absolute; top: 0; left: 25%; right: 25%; height: 3px; background: #B8954F; }
        .off-icon { width: 64px; height: 64px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(184,149,79,.28); border-radius: 6px; color: #123F35; }
        .off-icon svg { width: 30px; height: 30px; }
        h1 { color: #123F35; font-size: 1.5rem; font-weight: 800; margin: 0 0 .5rem; }
        p { color: #626963; line-height: 1.9; margin: 0 0 1.25rem; }
        .cornice { height: 12px; position: relative; margin: 1.25rem auto; width: 70%; }
        .cornice::before, .cornice::after { content: ""; position: absolute; left: 0; right: 0; height: 1px; background: #B8954F; }
        .cornice::before { top: 3px; } .cornice::after { bottom: 3px; }
        ul { list-style: none; padding: 0; margin: 0 0 1.5rem; text-align: right; }
        li { padding: .5rem .25rem; border-bottom: 1px solid rgba(18,63,53,.08); font-size: .9rem; display: flex; gap: .6rem; align-items: baseline; }
        li:last-child { border-bottom: 0; }
        li b { color: #B8954F; }
        .off-btn { display: inline-flex; align-items: center; gap: .5rem; background: #123F35; color: #FEFDFC; border: 1px solid #123F35; border-radius: 6px; padding: .65rem 1.4rem; font-weight: 700; font-family: inherit; font-size: .95rem; cursor: pointer; text-decoration: none; }
        .off-btn:hover, .off-btn:focus-visible { background: #0A2924; border-color: #B8954F; }
        .off-status { margin-top: 1rem; font-size: .82rem; color: #858A84; }
        footer { background: #0A2924; color: rgba(254,253,252,.68); text-align: center; font-size: .78rem; padding: .9rem 16px; border-top: 1px solid #B8954F; }
    </style>
</head>
<body>
    <header class="off-head">
        <img src="{{ asset('logo.png') }}" alt="رابط">
        <small>جمهورية مصر العربية — محافظة كفر الشيخ<br>البوابة الإلكترونية الرسمية</small>
    </header>

    <main class="off-wrap">
        <section class="off-card" aria-labelledby="offTitle">
            <div class="off-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2 8.8a15 15 0 0 1 20 0M5 12.6a10 10 0 0 1 14 0M8.5 16.2a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1"/><path d="M3 3l18 18" stroke="#9B2C2C"/></svg>
            </div>
            <h1 id="offTitle">لا يوجد اتصال بالإنترنت</h1>
            <p>تعذّر الوصول إلى خوادم المنصة حالياً. لحماية بيانات المحافظة، لا تُعرض الصفحات والبيانات الحكومية بدون اتصال آمن.</p>

            <div class="cornice" aria-hidden="true"></div>

            <ul>
                <li><b>✓</b><span>واجهة التطبيق والخطوط والأيقونات محفوظة على جهازك لتحميل أسرع.</span></li>
                <li><b>✓</b><span>أي نموذج لم تضغط «حفظ» عليه سيبقى في الصفحة المفتوحة؛ أعد المحاولة بعد عودة الاتصال.</span></li>
                <li><b>✕</b><span>البيانات والتقارير والخريطة ولوحات الإدارة تتطلب اتصالاً ولا تُخزَّن على الجهاز.</span></li>
            </ul>

            <button type="button" class="off-btn" onclick="location.reload()">إعادة المحاولة</button>
            <div class="off-status" id="offStatus" role="status" aria-live="polite"></div>
        </section>
    </main>

    <footer>© {{ date('Y') }} محافظة كفر الشيخ — جميع الحقوق محفوظة</footer>

    <script>
        (function () {
            var s = document.getElementById('offStatus');
            function upd() { s.textContent = navigator.onLine ? 'تم رصد اتصال — جارٍ إعادة التحميل…' : 'في انتظار عودة الاتصال…'; }
            upd();
            window.addEventListener('online', function () { upd(); setTimeout(function () { location.reload(); }, 800); });
            window.addEventListener('offline', upd);
        })();
    </script>
</body>
</html>
