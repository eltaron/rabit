/* KFS Royal Egyptian — app shell behaviour (sidebar, accessibility, motion) */
(function () {
    'use strict';

    const root = document.documentElement;
    const store = {
        get(k, d) { try { const v = localStorage.getItem('kfs.' + k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
        set(k, v) { try { localStorage.setItem('kfs.' + k, JSON.stringify(v)); } catch (e) { /* storage unavailable */ } }
    };

    /* ---------- Accessibility preferences (applied early) ---------- */
    const prefs = { scale: store.get('scale', 1), hc: store.get('hc', false), motion: store.get('motion', false) };
    function applyPrefs() {
        root.style.setProperty('--font-scale', prefs.scale);
        root.classList.toggle('hc', !!prefs.hc);
        root.classList.toggle('reduce-motion', !!prefs.motion);
        const lbl = document.getElementById('a11yScaleLabel');
        if (lbl) lbl.textContent = Math.round(prefs.scale * 100) + '%';
        const hc = document.getElementById('a11yContrast');
        if (hc) hc.checked = !!prefs.hc;
        const mo = document.getElementById('a11yMotion');
        if (mo) mo.checked = !!prefs.motion;
    }
    applyPrefs();

    window.kfsA11y = {
        font(delta) {
            prefs.scale = delta === 0 ? 1 : Math.min(1.3, Math.max(.85, +(prefs.scale + delta).toFixed(2)));
            store.set('scale', prefs.scale); applyPrefs();
        },
        contrast(on) { prefs.hc = on; store.set('hc', on); applyPrefs(); },
        motion(on) { prefs.motion = on; store.set('motion', on); applyPrefs(); },
        toggle() {
            const p = document.getElementById('a11yPanel');
            if (!p) return;
            p.classList.toggle('open');
            const btn = document.getElementById('a11yBtn');
            if (btn) btn.setAttribute('aria-expanded', p.classList.contains('open'));
        },
        reset() { prefs.scale = 1; prefs.hc = false; prefs.motion = false; store.set('scale', 1); store.set('hc', false); store.set('motion', false); applyPrefs(); }
    };

    /* ---------- Sidebar ---------- */
    window.toggleSidebar = function () {
        const s = document.querySelector('.sidebar');
        const o = document.getElementById('sidebarOverlay');
        if (!s) return;
        s.classList.toggle('show');
        if (o) o.classList.toggle('show');
        document.body.style.overflow = s.classList.contains('show') && window.innerWidth < 1200 ? 'hidden' : '';
    };

    /* ---------- Delete confirmation ---------- */
    window.confirmDelete = function (url, name) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const run = () => {
            const form = document.createElement('form');
            form.method = 'POST'; form.action = url;
            form.innerHTML = '<input type="hidden" name="_token" value="' + token + '"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form); form.submit();
        };
        if (!window.Swal) { if (confirm('سيتم حذف "' + name + '" نهائياً. هل أنت متأكد؟')) run(); return; }
        Swal.fire({
            title: 'تأكيد الحذف', text: 'سيتم حذف "' + name + '" نهائياً', icon: 'warning',
            iconColor: '#A93232', showCancelButton: true, confirmButtonText: 'نعم، احذف', cancelButtonText: 'إلغاء', reverseButtons: true
        }).then(r => { if (r.isConfirmed) run(); });
    };

    document.addEventListener('DOMContentLoaded', function () {
        applyPrefs();

        /* Loader */
        const loader = document.getElementById('kfsLoader');
        if (loader) setTimeout(() => loader.classList.add('hide'), 250);

        /* Scroll progress + back to top */
        const bar = document.querySelector('.scroll-progress');
        const toTop = document.querySelector('.float-btn.to-top');
        const onScroll = () => {
            const h = document.documentElement.scrollHeight - window.innerHeight;
            const p = h > 0 ? window.scrollY / h : 0;
            if (bar) bar.style.transform = 'scaleX(' + p + ')';
            if (toTop) toTop.classList.toggle('show', window.scrollY > 500);
        };
        window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
        if (toTop) toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

        /* Reveal */
        const items = document.querySelectorAll('[data-reveal]');
        if ('IntersectionObserver' in window && items.length) {
            const io = new IntersectionObserver(entries => entries.forEach(en => {
                if (en.isIntersecting) {
                    const d = +(en.target.dataset.delay || 0);
                    setTimeout(() => en.target.classList.add('is-in'), d * 1000);
                    io.unobserve(en.target);
                }
            }), { threshold: .12 });
            items.forEach(el => io.observe(el));
        } else items.forEach(el => el.classList.add('is-in'));

        /* Counters (government statistics only) */
        const counters = document.querySelectorAll('[data-count]');
        const fmt = new Intl.NumberFormat('ar-EG');
        const animate = el => {
            const target = parseFloat(el.dataset.count) || 0;
            if (root.classList.contains('reduce-motion') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = fmt.format(target); return; }
            const start = performance.now(), dur = 1200;
            const step = t => {
                const k = Math.min(1, (t - start) / dur), e = 1 - Math.pow(1 - k, 3);
                el.textContent = fmt.format(Math.round(target * e));
                if (k < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        if ('IntersectionObserver' in window) {
            const co = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { animate(e.target); co.unobserve(e.target); } }), { threshold: .4 });
            counters.forEach(c => co.observe(c));
        } else counters.forEach(animate);

        /* Close a11y panel on outside click / Esc */
        document.addEventListener('click', e => {
            const p = document.getElementById('a11yPanel');
            if (p && p.classList.contains('open') && !p.contains(e.target) && !e.target.closest('#a11yBtn')) p.classList.remove('open');
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.getElementById('a11yPanel')?.classList.remove('open');
                const s = document.querySelector('.sidebar.show'); if (s) window.toggleSidebar();
            }
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                const q = document.getElementById('globalSearch'); if (q) { e.preventDefault(); q.focus(); }
            }
        });

        /* Desktop cursor: small gold dot, ring on interactive elements */
        if (window.matchMedia('(pointer: fine)').matches && !root.classList.contains('reduce-motion') && document.body.dataset.cursor === 'on') {
            const dot = document.createElement('div'); dot.className = 'cursor-dot'; document.body.appendChild(dot);
            window.addEventListener('mousemove', e => { dot.style.opacity = 1; dot.style.transform = 'translate(' + (e.clientX - 3) + 'px,' + (e.clientY - 3) + 'px)'; }, { passive: true });
            document.addEventListener('mouseover', e => dot.classList.toggle('ring', !!e.target.closest('a,button,[role=button],input,select,textarea,label')));
        }

        /* Drop zones */
        document.querySelectorAll('.drop-zone').forEach(z => {
            const input = z.querySelector('input[type=file]');
            const name = z.querySelector('.dz-name');
            ['dragenter', 'dragover'].forEach(ev => z.addEventListener(ev, e => { e.preventDefault(); z.classList.add('is-over'); }));
            ['dragleave', 'drop'].forEach(ev => z.addEventListener(ev, e => { e.preventDefault(); z.classList.remove('is-over'); }));
            z.addEventListener('drop', e => { if (input && e.dataTransfer.files.length) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); } });
            z.addEventListener('click', e => { if (input && e.target !== input) input.click(); });
            if (input && name) input.addEventListener('change', () => { name.textContent = input.files[0] ? input.files[0].name : ''; });
        });
    });
})();
