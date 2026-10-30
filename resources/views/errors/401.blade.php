@extends('errors.layout')

@section('error-code', '401')
@section('error-title', 'يلزم تسجيل الدخول')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-user-lock"></i></span>
<div class="err-code">401</div>
<h1 class="err-title">يلزم تسجيل الدخول</h1>
<p class="err-desc">هذه الصفحة متاحة للمستخدمين المسجلين فقط. يُرجى تسجيل الدخول بحسابك للمتابعة.</p>
<div class="err-actions">
    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-kfs"><i class="fas fa-right-to-bracket ml-2"></i>تسجيل الدخول</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
