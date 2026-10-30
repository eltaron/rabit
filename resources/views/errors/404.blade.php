@extends('errors.layout')

@section('error-code', '404')
@section('error-title', 'الصفحة غير موجودة')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-map-location-dot"></i></span>
<div class="err-code">404</div>
<h1 class="err-title">الصفحة غير موجودة</h1>
<p class="err-desc">تعذّر العثور على الصفحة المطلوبة؛ ربما نُقلت أو أن الرابط غير صحيح.</p>
<div class="err-actions">
    <a href="{{ auth()->check() ? url('/dashboard') : url('/') }}" class="btn btn-kfs"><i class="fas fa-house ml-2"></i>{{ auth()->check() ? 'لوحة التحكم' : 'الصفحة الرئيسية' }}</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
