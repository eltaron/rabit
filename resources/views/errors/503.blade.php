@extends('errors.layout')

@section('error-code', '503')
@section('error-title', 'المنصة قيد الصيانة')

@section('content')
<span class="eg-icon lg mb-3"><i class="fas fa-person-digging"></i></span>
<div class="err-code">503</div>
<h1 class="err-title">المنصة قيد الصيانة</h1>
<p class="err-desc">تخضع المنصة حالياً لأعمال صيانة وتحديث مجدولة. نعتذر عن الإزعاج، ويُرجى المحاولة لاحقاً.</p>
<div class="err-actions">
    <a href="javascript:location.reload()" class="btn btn-kfs"><i class="fas fa-rotate-right ml-2"></i>إعادة المحاولة</a>
    <a href="javascript:history.back()" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-2"></i>الرجوع</a>
</div>
@endsection
