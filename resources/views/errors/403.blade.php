@extends('errors.layout')

@section('error-code', '403')
@section('error-title', 'غير مصرح بالوصول')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-shield-halved"></i></span>
<div class="err-code">403</div>
<h1 class="err-title">غير مصرح بالوصول</h1>
<p class="err-desc">ليس لديك صلاحية للاطلاع على هذه الصفحة. إذا كنت ترى أن ذلك خطأ، تواصل مع مسؤول النظام بجهتك.</p>
<div class="err-actions">
    <a href="{{ auth()->check() ? url('/dashboard') : url('/') }}" class="btn btn-kfs"><i class="fas fa-house ml-2"></i>{{ auth()->check() ? 'لوحة التحكم' : 'الصفحة الرئيسية' }}</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
