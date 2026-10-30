@extends('errors.layout')

@section('error-code', '419')
@section('error-title', 'انتهت صلاحية الجلسة')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-hourglass-end"></i></span>
<div class="err-code">419</div>
<h1 class="err-title">انتهت صلاحية الجلسة</h1>
<p class="err-desc">انتهت صلاحية الصفحة لدواعٍ أمنية. يُرجى تحديث الصفحة والمحاولة مرة أخرى.</p>
<div class="err-actions">
    <a href="javascript:location.reload()" class="btn btn-kfs"><i class="fas fa-rotate-right ml-2"></i>إعادة المحاولة</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
