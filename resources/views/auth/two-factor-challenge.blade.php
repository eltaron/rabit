@extends('layouts.auth')

@section('title', 'المصادقة الثنائية')

@section('content')
@php $recoveryMode = session('use_recovery') || $errors->has('recovery_code'); @endphp
<div class="ac-head">
    <span class="eg-icon"><i class="fas fa-shield-halved"></i></span>
    <h2>التحقق بخطوتين</h2>
    <p id="tfaHint">{{ $recoveryMode ? 'أدخل أحد أكواد الاسترداد التي حفظتها عند التفعيل.' : 'افتح تطبيق المصادقة على هاتفك وأدخل الرمز المكوّن من 6 أرقام.' }}</p>
</div>

<form method="POST" action="{{ route('2fa.verify') }}" novalidate id="otpForm" @if ($recoveryMode) hidden @endif>
    @csrf
    <label for="code" class="form-label">رمز التحقق</label>
    <input type="text" class="form-control otp-input @error('code') is-invalid @enderror" id="code" name="code"
           maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required @unless ($recoveryMode) autofocus @endunless>
    @error('code')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <button type="submit" class="btn btn-kfs w-100 mt-3"><i class="fas fa-circle-check ml-2"></i>تحقق ومتابعة</button>
</form>

<form method="POST" action="{{ route('2fa.verify') }}" novalidate id="recoveryForm" @unless ($recoveryMode) hidden @endunless>
    @csrf
    <label for="recovery_code" class="form-label">كود الاسترداد</label>
    <input type="text" class="form-control text-center en @error('recovery_code') is-invalid @enderror" id="recovery_code" name="recovery_code"
           placeholder="XXXXX-XXXXX" autocomplete="off" style="letter-spacing:2px" @if ($recoveryMode) autofocus @endif>
    @error('recovery_code')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <div class="form-text">كل كود يُستخدم مرة واحدة فقط. المتبقي لديك: {{ $remaining }}.</div>
    <button type="submit" class="btn btn-kfs w-100 mt-3"><i class="fas fa-key ml-2"></i>دخول بكود الاسترداد</button>
</form>

<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
    <button type="button" class="btn btn-link auth-back p-0" id="toggleMode">
        <i class="fas fa-key"></i><span>{{ $recoveryMode ? 'استخدام رمز التطبيق' : 'فقدت هاتفك؟ استخدم كود استرداد' }}</span>
    </button>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link auth-back p-0"><i class="fas fa-right-from-bracket"></i>حساب آخر</button>
    </form>
</div>

<p class="text-muted text-center mt-4 mb-0" style="font-size:.8rem">
    <i class="fas fa-circle-info ml-1 text-gold"></i>إن فقدت هاتفك وأكواد الاسترداد، اطلب من مدير النظام إعادة ضبط المصادقة الثنائية لحسابك.
</p>
@endsection

@push('scripts')
<script>
    (function () {
        const otp = document.getElementById('otpForm'), rec = document.getElementById('recoveryForm');
        const btn = document.getElementById('toggleMode'), hint = document.getElementById('tfaHint');
        btn.addEventListener('click', () => {
            const toRecovery = !otp.hidden;
            otp.hidden = toRecovery; rec.hidden = !toRecovery;
            btn.querySelector('span').textContent = toRecovery ? 'استخدام رمز التطبيق' : 'فقدت هاتفك؟ استخدم كود استرداد';
            hint.textContent = toRecovery ? 'أدخل أحد أكواد الاسترداد التي حفظتها عند التفعيل.' : 'افتح تطبيق المصادقة على هاتفك وأدخل الرمز المكوّن من 6 أرقام.';
            (toRecovery ? rec : otp).querySelector('input:not([type=hidden])').focus();
        });
        const code = document.getElementById('code');
        code.addEventListener('input', () => {
            code.value = code.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 6);
            if (code.value.length === 6) otp.requestSubmit();
        });
    })();
</script>
@endpush
