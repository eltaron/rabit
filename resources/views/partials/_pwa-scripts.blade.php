{{-- PWA: تسجيل Service Worker + التحديث التلقائي + التثبيت + حالة الاتصال --}}
<style>
    .pwa-toast {
        position: fixed; right: 50%; transform: translateX(50%); bottom: 18px; z-index: 3500;
        display: none; align-items: center; gap: .75rem; max-width: calc(100vw - 32px);
        background: #0A2924; color: #FEFDFC; border: 1px solid #B8954F; border-top-width: 3px;
        border-radius: 8px; padding: .65rem .9rem; box-shadow: 0 25px 65px rgba(18,63,53,.25);
        font-family: 'Tajawal', 'IBM Plex Sans Arabic', sans-serif; font-size: .86rem;
    }
    .pwa-toast.show { display: flex; }
    .pwa-toast i { color: #D1B574; }
    .pwa-toast button {
        background: transparent; color: #D1B574; border: 1px solid #B8954F; border-radius: 6px;
        padding: .2rem .7rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: .8rem;
    }
    .pwa-toast button:hover { background: #B8954F; color: #0A2924; }
    .pwa-toast .pwa-x { border: 0; color: rgba(254,253,252,.6); padding: 0 .25rem; }
    .pwa-install-btn { display: none; }
    .pwa-install-btn.show { display: flex; }
    .pwa-install-fixed { position: fixed; left: 18px; bottom: 18px; z-index: 1050; }
    @media print { .pwa-toast, .pwa-install-btn { display: none !important; } }
</style>

<div class="pwa-toast" id="pwaUpdateToast" role="status" aria-live="polite">
    <i class="fas fa-rotate"></i><span>يتوفر إصدار جديد من المنصة</span>
    <button type="button" id="pwaUpdateBtn">تحديث</button>
    <button type="button" class="pwa-x" aria-label="إغلاق" onclick="this.parentElement.classList.remove('show')">✕</button>
</div>
<div class="pwa-toast" id="pwaNetToast" role="status" aria-live="polite">
    <i class="fas fa-wifi"></i><span id="pwaNetText"></span>
</div>
<div class="pwa-toast" id="pwaIosToast" role="status" aria-live="polite">
    <i class="fas fa-mobile-screen"></i>
    <span>لتثبيت التطبيق: اضغط زر المشاركة ثم «إضافة إلى الشاشة الرئيسية»</span>
    <button type="button" class="pwa-x" aria-label="إغلاق" onclick="this.parentElement.classList.remove('show');try{localStorage.setItem('kfs.iosHint','1')}catch(e){}">✕</button>
</div>

<script>
(function () {
    'use strict';
    var SW_URL = @json(asset('sw.js')) + '?v=' + encodeURIComponent(@json((string) config('app.asset_version')));
    var SCOPE = @json(rtrim(url('/'), '/') . '/');
    var $ = function (id) { return document.getElementById(id); };

    /* ---------- حالة الاتصال ---------- */
    var netTimer;
    function netToast(online) {
        var t = $('pwaNetToast'); if (!t) return;
        $('pwaNetText').textContent = online ? 'تمت استعادة الاتصال بالإنترنت' : 'أنت غير متصل بالإنترنت — بعض الخدمات غير متاحة';
        t.querySelector('i').className = online ? 'fas fa-wifi' : 'fas fa-plug-circle-xmark';
        t.classList.add('show'); clearTimeout(netTimer);
        if (online) netTimer = setTimeout(function () { t.classList.remove('show'); }, 3500);
    }
    window.addEventListener('offline', function () { netToast(false); });
    window.addEventListener('online', function () { netToast(true); });

    /* ---------- زر التثبيت ---------- */
    var deferred = null;
    function installButton() {
        var b = $('pwaInstallBtn');
        if (b) return b;
        b = document.createElement('button');
        b.type = 'button'; b.id = 'pwaInstallBtn';
        b.className = 'float-btn pwa-install-btn';
        b.title = 'تثبيت التطبيق'; b.setAttribute('aria-label', 'تثبيت التطبيق على الجهاز');
        b.innerHTML = '<i class="fas fa-download" aria-hidden="true"></i>';
        var stack = document.querySelector('.float-stack');
        if (stack) stack.insertBefore(b, stack.firstChild);
        else { b.classList.add('pwa-install-fixed'); b.style.cssText += 'width:44px;height:44px;border-radius:50%;background:#0A2924;color:#D1B574;border:1px solid #B8954F;align-items:center;justify-content:center;cursor:pointer'; document.body.appendChild(b); }
        b.addEventListener('click', function () {
            if (!deferred) return;
            deferred.prompt();
            deferred.userChoice.finally(function () { deferred = null; b.classList.remove('show'); });
        });
        return b;
    }
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault(); deferred = e; installButton().classList.add('show');
    });
    window.addEventListener('appinstalled', function () { var b = $('pwaInstallBtn'); if (b) b.classList.remove('show'); deferred = null; });

    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    try {
        if (isIos && !standalone && !localStorage.getItem('kfs.iosHint')) {
            setTimeout(function () { var t = $('pwaIosToast'); if (t) t.classList.add('show'); }, 4000);
        }
    } catch (e) { /* storage unavailable */ }

    /* ---------- Service Worker ---------- */
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

    var refreshing = false, userAsked = false;
    navigator.serviceWorker.addEventListener('controllerchange', function () {
        if (refreshing || !userAsked) return;
        refreshing = true; window.location.reload();
    });

    function promptUpdate(worker) {
        var t = $('pwaUpdateToast'); if (!t || !worker) return;
        t.classList.add('show');
        $('pwaUpdateBtn').onclick = function () {
            userAsked = true; t.classList.remove('show');
            worker.postMessage({ type: 'SKIP_WAITING' });
        };
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register(SW_URL, { scope: SCOPE }).then(function (reg) {
            if (reg.waiting && navigator.serviceWorker.controller) promptUpdate(reg.waiting);
            reg.addEventListener('updatefound', function () {
                var nw = reg.installing; if (!nw) return;
                nw.addEventListener('statechange', function () {
                    if (nw.state === 'installed' && navigator.serviceWorker.controller) promptUpdate(nw);
                });
            });
            var check = function () { reg.update().catch(function () {}); };
            setInterval(check, 60 * 60 * 1000);
            document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') check(); });
        }).catch(function (err) { console.warn('[PWA] SW registration failed:', err); });
    });

    /* عند تسجيل الخروج: امسح أي كاش للصفحات */
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (f && f.action && /\/logout(\?|$)/.test(f.action) && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_RUNTIME' });
        }
    }, true);
})();
</script>
