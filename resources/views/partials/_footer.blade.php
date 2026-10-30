<footer class="footer" role="contentinfo">
    <div class="egypt-cornice" aria-hidden="true"></div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2 text-center text-md-start">
            <span>جمهورية مصر العربية — محافظة كفر الشيخ</span>
            <span class="footer-divider d-none d-md-inline-block"></span>
            <span>&copy; {{ date('Y') }} جميع الحقوق محفوظة</span>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap justify-content-center">
            <a href="{{ route('dashboard') }}">الرئيسية</a>
            <div class="footer-divider"></div>
            <a href="{{ route('manual') }}">دليل الاستخدام</a>
            <div class="footer-divider"></div>
            <a href="{{ route('profile.edit') }}">الملف الشخصي</a>
            <div class="footer-divider"></div>
            <span><i class="fas fa-shield-halved ml-1" style="color:var(--egypt-gold)"></i>الإصدار <span class="en">{{ config('app.version', '2.0.0') }}</span></span>
        </div>
    </div>
</footer>
