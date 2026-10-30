@extends('layouts.app')

@section('title', 'المساعد الذكي')
@section('page-title', 'المساعد الذكي')
@section('page-subtitle', 'إرشاد لاستخدام المنصة وإجابات من بيانات المحافظة ضمن صلاحياتك')
@section('breadcrumb')
    <li class="breadcrumb-item active">المساعد الذكي</li>
@endsection

@can('use_decision_support')
    @section('page-actions')
        <a href="{{ route('decision.index') }}" class="btn btn-kfs-gold"><i class="fas fa-chess-king ml-1"></i>مساعد دعم القرار</a>
    @endsection
@endcan

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            @livewire('chat-interface')
        </div>
    </div>
@endsection
