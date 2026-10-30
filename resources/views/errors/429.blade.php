@extends('errors.layout')

@section('error-code', '429')
@section('error-title', 'طلبات كثيرة')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-gauge-high"></i></span>
<div class="err-code">429</div>
<h1 class="err-title">طلبات كثيرة</h1>
<p class="err-desc">تم استقبال عدد كبير من الطلبات خلال وقت قصير. يُرجى الانتظار قليلاً ثم إعادة المحاولة.</p>
<div class="err-actions">
    <a href="javascript:location.reload()" class="btn btn-kfs"><i class="fas fa-rotate-right ml-2"></i>إعادة المحاولة</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
