@extends('layouts.auth')

@section('title', $reconfigure ? 'تغيير جهاز المصادقة الثنائية' : 'تفعيل المصادقة الثنائية')

@section('content')
<div class="ac-head">
    <span class="eg-icon"><i class="fas fa-shield-halved"></i></span>
    <h2>{{ $reconfigure ? 'تغيير جهاز المصادقة' : 'تفعيل المصادقة الثنائية' }}</h2>
    <p>
        @if ($mandatory)
            المصادقة الثنائية <strong>إجبارية</strong> على كل حسابات المنصة لحماية البيانات الحكومية. أكمل الخطوات الثلاث للمتابعة.
        @elseif ($reconfigure)
            امسح الرمز الجديد بهاتفك الجديد. سيتوقف الجهاز القديم عن العمل بعد التأكيد.
        @else
            أضف طبقة حماية لحسابك في ثلاث خطوات.
        @endif
    </p>
</div>

<ol class="tfa-steps">
    <li>
        <strong>ثبّت تطبيق مصادقة على هاتفك</strong>
        <span>Google Authenticator أو Microsoft Authenticator (متاحان مجاناً على Android و iPhone).</span>
    </li>
    <li>
        <strong>امسح رمز QR من التطبيق</strong>
        <span>إن كان في التطبيق حساب سابق باسم «{{ config('app.two_factor.issuer') }}» لنفس البريد فاحذفه أولاً، ثم امسح هذا الرمز. الرمز ثابت لحسابك حتى تكمل التفعيل.</span>
        <div class="tfa-qr-wrap">
            <div id="tfaQr" class="tfa-qr" role="img" aria-label="رمز QR لإضافة الحساب إلى تطبيق المصادقة">
                <span class="text-muted small">جارٍ إنشاء الرمز…</span>
            </div>
        </div>
        <details class="tfa-manual">
            <summary>لا يمكنك المسح؟ أدخل المفتاح يدوياً</summary>
            <div class="input-group input-group-sm mt-2">
                <input type="text" id="secretKey" class="form-control text-center en" value="{{ $secretGrouped }}" readonly aria-label="المفتاح السري">
                <button class="btn btn-outline-navy" type="button" id="copySecret" aria-label="نسخ المفتاح"><i class="fas fa-copy"></i></button>
            </div>
            <div class="form-text">اسم الحساب: <span class="en">{{ auth()->user()->email }}</span> — النوع: حسب الوقت (TOTP)</div>
        </details>
    </li>
    <li>
        <strong>أدخل الرمز الظاهر في التطبيق</strong>
        <form method="POST" action="{{ route('2fa.enable') }}" novalidate class="mt-2">
            @csrf
            <input type="text" class="form-control otp-input @error('code') is-invalid @enderror" id="code" name="code"
                   maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required autofocus
                   aria-label="رمز التحقق المكوّن من 6 أرقام">
            @error('code')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn btn-kfs w-100 mt-3">
                <i class="fas fa-circle-check ml-2"></i>{{ $reconfigure ? 'تأكيد الجهاز الجديد' : 'تفعيل ومتابعة' }}
            </button>
        </form>
    </li>
</ol>

<div class="text-center mt-3">
    @if ($mandatory)
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link auth-back p-0"><i class="fas fa-right-from-bracket"></i>تسجيل الخروج والإكمال لاحقاً</button>
        </form>
    @else
        <a href="{{ route('profile.edit') }}" class="auth-back"><i class="fas fa-arrow-right"></i>العودة للملف الشخصي</a>
    @endif
</div>
@endsection

@push('styles')
<style>
    .tfa-steps { list-style: none; padding: 0; margin: 0; counter-reset: s; }
    .tfa-steps > li { counter-increment: s; position: relative; padding: 0 2.6rem 1.1rem 0; margin-bottom: 1.1rem; border-bottom: 1px dashed var(--line); }
    .tfa-steps > li:last-child { border-bottom: 0; margin-bottom: 0; }
    .tfa-steps > li::before {
        content: counter(s); position: absolute; right: 0; top: 0; width: 1.8rem; height: 1.8rem;
        border: 1px solid var(--egypt-gold); border-radius: var(--r); color: var(--egypt-green);
        display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem;
    }
    .tfa-steps strong { display: block; color: var(--egypt-green); font-family: var(--ff-ar); }
    .tfa-steps span { color: var(--text-soft); font-size: .85rem; }
    .tfa-qr-wrap { display: flex; justify-content: center; margin-top: .75rem; }
    .tfa-qr {
        width: 212px; height: 212px; padding: 10px; background: #fff;
        border: 1px solid var(--egypt-gold-line); border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
    }
    .tfa-qr svg { width: 100%; height: 100%; display: block; }
    .tfa-manual { margin-top: .75rem; font-size: .86rem; }
    .tfa-manual summary { cursor: pointer; color: var(--egypt-green-3); font-weight: 600; }
    #secretKey { letter-spacing: 1px; font-size: .85rem; }
</style>
@endpush

@push('scripts')
{{-- QR يُرسم محلياً داخل المتصفح؛ المفتاح السري لا يُرسل لأي خدمة خارجية --}}
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    (function () {
        const url = @json($otpauthUrl);
        const box = document.getElementById('tfaQr');
        try {
            const qr = qrcode(0, 'M');
            qr.addData(url);
            qr.make();
            box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
        } catch (e) {
            box.innerHTML = '<span class="text-muted small text-center">تعذّر عرض الرمز. استخدم المفتاح اليدوي بالأسفل.</span>';
            document.querySelector('.tfa-manual').open = true;
        }
        document.getElementById('copySecret').addEventListener('click', function () {
            const v = document.getElementById('secretKey').value.replace(/\s+/g, '');
            (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(
                () => { this.innerHTML = '<i class="fas fa-check"></i>'; },
                () => { document.getElementById('secretKey').select(); }
            );
        });
        const code = document.getElementById('code');
        // منع الإرسال المزدوج (إرسال تلقائي عند 6 أرقام + Enter/الزر/الملء التلقائي)
        let sent = false;
        code.form.addEventListener('submit', (e) => {
            if (sent) { e.preventDefault(); return; }
            sent = true;
            code.form.querySelectorAll('button[type=submit]').forEach(b => b.disabled = true);
        });
        code.addEventListener('input', () => {
            code.value = code.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 6);
            if (code.value.length === 6 && !sent) code.form.requestSubmit();
        });
    })();
</script>
@endpush
