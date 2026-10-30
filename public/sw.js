/* ==========================================================================
   رابط — Service Worker (محافظة كفر الشيخ)
   - الإصدار يُمرَّر عند التسجيل: sw.js?v=<config('app.asset_version')>
   - لا يُخزَّن أي HTML لصفحات بعد تسجيل الدخول ولا أي بيانات/ملفات تصدير.
   ========================================================================== */
'use strict';

const VERSION = new URL(self.location.href).searchParams.get('v') || 'dev';
const SCOPE = new URL(self.registration.scope);          // e.g. http://localhost/kfsformgenerator/public/
const BASE = SCOPE.pathname;                              // e.g. /kfsformgenerator/public/

const CACHE_SHELL = `rabet-shell-${VERSION}`;             // app shell (precache)
const CACHE_STATIC = `rabet-static-${VERSION}`;           // same-origin static assets (cache-first)
const CACHE_CDN = 'rabet-cdn-v1';                          // CDN libs & fonts (stale-while-revalidate)
const CACHE_PAGES = `rabet-pages-${VERSION}`;             // public pages only (offline)
const KEEP = [CACHE_SHELL, CACHE_STATIC, CACHE_CDN, CACHE_PAGES];

const OFFLINE_URL = BASE + 'offline';
const LIMITS = { [CACHE_STATIC]: 150, [CACHE_CDN]: 60 };

const u = p => BASE + p;
const PRECACHE = [
    OFFLINE_URL,
    u('assets/css/kfs-royal.css?v=' + VERSION),
    u('assets/js/kfs-royal.js?v=' + VERSION),
    u('assets/img/egypt-pattern.svg'),
    u('assets/img/egypt-cornice.svg'),
    u('logo.png'),
    u('manifest.webmanifest'),
    u('assets/img/pwa/icon-192.png'),
    u('assets/img/pwa/icon-512.png'),
    u('assets/img/pwa/favicon-32.png'),
];

// صفحات عامة آمنة يمكن عرضها بدون اتصال.
// الصفحة الرئيسية العامة لا تُخزَّن لأنها قد تعرض اسم المستخدم المسجّل؛ فقط صفحة عدم الاتصال.
const PUBLIC_PAGES = [OFFLINE_URL];

const CDN_HOSTS = ['cdn.jsdelivr.net', 'cdnjs.cloudflare.com', 'fonts.googleapis.com', 'fonts.gstatic.com', 'unpkg.com'];

// مسارات لا تُخزَّن إطلاقاً (تمر للشبكة مباشرة)
const BYPASS = [
    /^admin(\/|$)/, /^login/, /^logout/, /^2fa/, /^livewire/, /^api\//, /^sanctum/,
    /^excel(\/|$)/, /export/i, /\/pdf$/, /\.(xlsx|xls|csv|pdf|docx?)$/i,
    /^map\/(features|layers|units|unit|search|center-data)/, /^decision-support/, /^chatbot/,
    /^notifications/, /^profile/, /^upload/, /^storage\//,
];

const STATIC_RE = /\.(css|js|mjs|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|otf|eot|geojson)$/i;

/* ------------------------------------------------------------------ helpers */
const relPath = url => url.pathname.startsWith(BASE) ? url.pathname.slice(BASE.length) : url.pathname.replace(/^\//, '');

async function trim(cacheName) {
    const max = LIMITS[cacheName];
    if (!max) return;
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}

function cacheable(res) {
    return res && (res.ok || res.type === 'opaque') && !res.headers.get('Content-Disposition');
}

async function cacheFirst(req, cacheName) {
    const cache = await caches.open(cacheName);
    const hit = await cache.match(req) || await caches.match(req);
    if (hit) return hit;
    const res = await fetch(req);
    if (cacheable(res) && res.type !== 'opaque') { cache.put(req, res.clone()); trim(cacheName); }
    return res;
}

async function staleWhileRevalidate(event, req, cacheName) {
    const cache = await caches.open(cacheName);
    const hit = await cache.match(req);
    const net = fetch(req).then(res => {
        if (cacheable(res)) { cache.put(req, res.clone()); trim(cacheName); }
        return res;
    }).catch(() => hit);
    if (hit) { event.waitUntil(net); return hit; }
    return net;
}

async function navigate(event, req, path) {
    const isPublic = PUBLIC_PAGES.includes(new URL(req.url).pathname);
    try {
        const res = await fetch(req);
        // نخزّن فقط الصفحات العامة (الرئيسية العامة وصفحة عدم الاتصال)؛ لا HTML بعد الدخول
        if (isPublic && res.ok && !res.redirected) {
            const cache = await caches.open(CACHE_PAGES);
            cache.put(req, res.clone());
        }
        return res;
    } catch (e) {
        if (isPublic) {
            const hit = await caches.match(req);
            if (hit) return hit;
        }
        return (await caches.match(OFFLINE_URL)) || new Response(
            '<!doctype html><meta charset="utf-8"><body dir="rtl" style="font-family:sans-serif;padding:2rem;background:#FAF8F2;color:#123F35">لا يوجد اتصال بالإنترنت حالياً.</body>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

/* ------------------------------------------------------------------ lifecycle */
self.addEventListener('install', event => {
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE_SHELL);
        // كل عنصر على حدة حتى لا يفشل التثبيت بسبب ملف واحد
        await Promise.all(PRECACHE.map(p => cache.add(new Request(p, { cache: 'reload', credentials: 'same-origin' })).catch(() => null)));
    })());
    // لا skipWaiting تلقائياً: المستخدم يقرر التحديث من التنبيه (أو أول تثبيت)
    if (!self.registration.active) self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const names = await caches.keys();
        await Promise.all(names.filter(n => n.startsWith('rabet-') && !KEEP.includes(n)).map(n => caches.delete(n)));
        if (self.registration.navigationPreload) { try { await self.registration.navigationPreload.disable(); } catch (e) { /* ignore */ } }
        await self.clients.claim();
    })());
});

self.addEventListener('message', event => {
    const type = event.data && event.data.type;
    if (type === 'SKIP_WAITING') self.skipWaiting();
    if (type === 'CLEAR_RUNTIME') {
        // عند تسجيل الخروج: احذف كل ما ليس أصولاً ثابتة
        event.waitUntil(caches.keys().then(ns => Promise.all(ns.filter(n => n.startsWith('rabet-pages-')).map(n => caches.delete(n)))));
    }
    if (type === 'GET_VERSION' && event.ports[0]) event.ports[0].postMessage(VERSION);
});

/* ------------------------------------------------------------------ fetch */
self.addEventListener('fetch', event => {
    const req = event.request;
    if (req.method !== 'GET') return;                          // POST/PUT/DELETE → الشبكة دائماً
    if (req.headers.get('Authorization')) return;
    if (req.headers.get('X-Livewire')) return;
    if (req.cache === 'only-if-cached' && req.mode !== 'same-origin') return;

    const url = new URL(req.url);

    // CDN libs & fonts
    if (url.origin !== self.location.origin) {
        if (CDN_HOSTS.includes(url.hostname)) event.respondWith(staleWhileRevalidate(event, req, CACHE_CDN));
        return;
    }

    if (!url.pathname.startsWith(BASE) && BASE !== '/') return;
    const path = relPath(url);

    if (path === 'sw.js') return;
    if (BYPASS.some(re => re.test(path))) {
        // تنقل لصفحة محمية بدون اتصال → صفحة عدم الاتصال
        if (req.mode === 'navigate') event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(navigate(event, req, path));
        return;
    }

    // الأصول الثابتة (CSS/JS/صور/خطوط) → cache-first (مُصدَّرة بـ ?v=)
    if (STATIC_RE.test(url.pathname) && (path.startsWith('assets/') || /^(logo|favicon)\./.test(path) || path.startsWith('data/') || path.startsWith('vendor/'))) {
        if (path.startsWith('data/') && !/\.geojson$/i.test(path)) return; // ملفات Excel في data/ لا تُخزن
        event.respondWith(cacheFirst(req, CACHE_STATIC));
        return;
    }

    if (path === 'manifest.webmanifest') {
        event.respondWith(staleWhileRevalidate(event, req, CACHE_SHELL));
    }
    // أي شيء آخر (JSON، صفحات جزئية…) → الشبكة بدون تخزين
});
