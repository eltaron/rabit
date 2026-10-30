@extends('layouts.auth')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="ac-head">
    <span class="eg-icon"><i class="fas fa-right-to-bracket"></i></span>
    <h2>تسجيل الدخول</h2>
    <p>أدخل بيانات حسابك الحكومي للمتابعة.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger py-2" role="alert" aria-live="assertive">
        <i class="fas fa-triangle-exclamation ml-1"></i>{{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="login" class="form-label">البريد الإلكتروني أو الرقم القومي</label>
        <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login"
               value="{{ old('login') }}" placeholder="name@kfs.gov.eg أو 14 رقماً" required autofocus
               autocomplete="username" inputmode="email" aria-describedby="loginHelp">
        <div id="loginHelp" class="form-text">يمكنك الدخول بالبريد الإلكتروني أو بالرقم القومي.</div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">كلمة المرور</label>
        <div class="pw-wrap">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                   placeholder="أدخل كلمة المرور" required autocomplete="current-password">
            <button type="button" class="pw-toggle" onclick="togglePassword(this)" aria-label="إظهار كلمة المرور" aria-pressed="false">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember" style="font-size:.88rem">تذكرني على هذا الجهاز</label>
        </div>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" style="font-size:.85rem;font-weight:600">نسيت كلمة المرور؟</a>
        @endif
    </div>

    <button type="submit" class="btn btn-kfs w-100">
        <i class="fas fa-lock ml-2"></i>دخول آمن
    </button>
</form>

@if (! empty($demoAccounts) && $demoAccounts->isNotEmpty())
    <section class="demo-accounts mt-4" aria-labelledby="demoTitle">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h3 id="demoTitle" class="m-0" style="font-size:.92rem"><i class="fas fa-user-gear ml-1 text-gold"></i>حسابات تجريبية لكل الأدوار</h3>
            <span class="badge badge-gold">كلمة المرور: <span class="en">password</span></span>
        </div>
        <p class="text-muted mb-2" style="font-size:.78rem">اضغط على أي حساب لتعبئة بيانات الدخول تلقائياً.@if (config('app.two_factor.required')) سيُطلب إعداد المصادقة الثنائية عند أول دخول لكل حساب.@endif</p>
        <div class="demo-grid">
            @foreach ($demoAccounts as $acc)
                <button type="button" class="demo-acc" data-email="{{ $acc['email'] }}" onclick="fillDemo(this)">
                    <span class="demo-role">{{ $acc['role'] }}</span>
                    <span class="demo-email en">{{ $acc['email'] }}</span>
                </button>
            @endforeach
        </div>
    </section>
@endif

<p class="text-muted text-center mt-4 mb-0" style="font-size:.8rem">
    <i class="fas fa-circle-info ml-1 text-gold"></i>للحصول على حساب أو استعادة كلمة المرور يُرجى التواصل مع مسؤول النظام بجهتك.
</p>
@endsection

@push('styles')
<style>
    .demo-accounts { border-top: 1px solid var(--line); padding-top: 1rem; }
    .demo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .5rem; }
    .demo-acc {
        display: flex; flex-direction: column; align-items: flex-start; gap: .1rem; text-align: right;
        background: var(--white-soft); border: 1px solid var(--line); border-right: 2px solid var(--egypt-gold-line);
        border-radius: var(--r); padding: .5rem .65rem; cursor: pointer; min-width: 0;
        transition: border-color .4s var(--ease), background .4s var(--ease);
    }
    .demo-acc:hover, .demo-acc:focus-visible, .demo-acc.is-active { border-color: var(--egypt-gold); background: var(--papyrus); }
    .demo-role { font-family: var(--ff-ar); font-weight: 700; color: var(--egypt-green); font-size: .82rem; }
    .demo-email { color: var(--text-soft); font-size: .72rem; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; direction: ltr; }
</style>
@endpush

@push('scripts')
<script>
    function fillDemo(btn) {
        document.getElementById('login').value = btn.dataset.email;
        document.getElementById('password').value = 'password';
        document.querySelectorAll('.demo-acc').forEach(b => b.classList.toggle('is-active', b === btn));
        document.querySelector('form button[type=submit]').focus();
    }
    function togglePassword(btn) {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
    }
</script>
@endpush
