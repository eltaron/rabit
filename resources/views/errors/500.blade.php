@extends('errors.layout')

@section('error-code', '500')
@section('error-title', 'خطأ في الخادم')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-screwdriver-wrench"></i></span>
<div class="err-code">500</div>
<h1 class="err-title">خطأ في الخادم</h1>
<p class="err-desc">حدث خطأ غير متوقع أثناء معالجة طلبك. تم تسجيل الخطأ وسيعمل الفريق الفني على معالجته.</p>
<div class="err-actions">
    <a href="{{ auth()->check() ? url('/dashboard') : url('/') }}" class="btn btn-kfs"><i class="fas fa-house ml-2"></i>{{ auth()->check() ? 'لوحة التحكم' : 'الصفحة الرئيسية' }}</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
