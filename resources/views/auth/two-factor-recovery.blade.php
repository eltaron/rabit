@extends('layouts.auth')

@section('title', 'أكواد الاسترداد')

@section('content')
<div class="ac-head">
    <span class="eg-icon" style="color:var(--success)"><i class="fas fa-circle-check"></i></span>
    <h2>تم تفعيل المصادقة الثنائية</h2>
    <p>احفظ أكواد الاسترداد التالية الآن. تُستخدم للدخول إذا فقدت هاتفك، وكل كود يصلح <strong>مرة واحدة</strong>.</p>
</div>

<div class="alert alert-warning py-2" role="alert" style="font-size:.86rem">
    <i class="fas fa-triangle-exclamation ml-1"></i>هذه الأكواد لن تُعرض مرة أخرى. احفظها في مكان آمن بعيداً عن هاتفك.
</div>

<div class="recovery-grid" id="codes">
    @foreach ($codes as $c)
        <code class="en">{{ $c }}</code>
    @endforeach
</div>

<div class="d-flex gap-2 mt-3 flex-wrap">
    <button type="button" class="btn btn-outline-navy flex-fill" id="copyCodes"><i class="fas fa-copy ml-1"></i>نسخ</button>
    <button type="button" class="btn btn-outline-navy flex-fill" id="downloadCodes"><i class="fas fa-download ml-1"></i>تنزيل</button>
    <button type="button" class="btn btn-outline-navy flex-fill" onclick="window.print()"><i class="fas fa-print ml-1"></i>طباعة</button>
</div>

<div class="form-check mt-4">
    <input class="form-check-input" type="checkbox" id="saved">
    <label class="form-check-label" for="saved">حفظت أكواد الاسترداد في مكان آمن</label>
</div>
<a href="{{ session('url.intended', route('dashboard')) }}" class="btn btn-kfs w-100 mt-3 disabled" id="continueBtn" aria-disabled="true">
    <i class="fas fa-arrow-left ml-2"></i>متابعة إلى المنصة
</a>
@endsection

@push('styles')
<style>
    .recovery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; }
    .recovery-grid code {
        background: var(--papyrus); border: 1px solid var(--egypt-gold-line); border-radius: var(--r);
        padding: .5rem; text-align: center; color: var(--egypt-green); font-size: .95rem; letter-spacing: 1px; direction: ltr;
    }
    @media print { .btn, .form-check, .alert { display: none !important; } }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const codes = @json($codes);
        const text = 'أكواد استرداد المصادقة الثنائية — منصة رابط، محافظة كفر الشيخ\n'
            + 'الحساب: {{ $user->email }}\nالتاريخ: {{ now()->format('Y-m-d H:i') }}\n\n' + codes.join('\n')
            + '\n\nكل كود يُستخدم مرة واحدة فقط.\n';
        document.getElementById('copyCodes').addEventListener('click', function () {
            navigator.clipboard?.writeText(codes.join('\n')).then(() => { this.innerHTML = '<i class="fas fa-check ml-1"></i>تم النسخ'; });
        });
        document.getElementById('downloadCodes').addEventListener('click', () => {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob(['﻿' + text], { type: 'text/plain;charset=utf-8' }));
            a.download = 'rabet-recovery-codes.txt'; a.click(); URL.revokeObjectURL(a.href);
        });
        const saved = document.getElementById('saved'), cont = document.getElementById('continueBtn');
        saved.addEventListener('change', () => { cont.classList.toggle('disabled', !saved.checked); cont.setAttribute('aria-disabled', !saved.checked); });
    })();
</script>
@endpush
