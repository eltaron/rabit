@extends('layouts.app')

@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')
@section('page-subtitle', 'تنبيهات سير العمل والتكليفات والاعتمادات الخاصة بك')
@section('breadcrumb')
    <li class="breadcrumb-item active">الإشعارات</li>
@endsection
@section('page-actions')
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-kfs-gold"><i class="fas fa-check-double ml-1"></i>تعليم الكل كمقروء</button>
    </form>
@endsection

@section('content')
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ request()->boolean('unread_only') ? '' : 'active' }}" href="{{ route('notifications.index') }}">الكل</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->boolean('unread_only') ? 'active' : '' }}" href="{{ route('notifications.index', ['unread_only' => 1]) }}">غير المقروءة</a></li>
    </ul>

    <div class="card-kfs">
        @if ($notifications->isEmpty())
            <div class="empty-state">
                <span class="eg-icon lg"><i class="fas fa-bell-slash"></i></span>
                <h5>لا توجد إشعارات</h5>
                <p class="mb-0">ستظهر هنا التنبيهات عند تكليفك بمهمة أو تغيّر حالة إرسالاتك وتقاريرك.</p>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($notifications as $n)
                    @php
                        $data = json_decode($n->data ?? '[]', true) ?: [];
                        $url = $data['url'] ?? null;
                        $urgent = in_array($n->priority ?? '', ['high', 'urgent']);
                    @endphp
                    <li class="list-group-item d-flex gap-3 align-items-start py-3" style="{{ $n->read_at ? '' : 'background:var(--papyrus)' }}">
                        <span class="eg-icon sm" style="{{ $urgent ? 'color:var(--egypt-red)' : '' }}"><i class="fas {{ $urgent ? 'fa-circle-exclamation' : 'fa-bell' }}"></i></span>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="d-flex justify-content-between gap-2 flex-wrap">
                                <strong class="{{ $n->read_at ? 'fw-semibold' : '' }}" style="color:var(--charcoal)">{{ $n->title ?? ($data['title'] ?? 'إشعار') }}</strong>
                                <small class="text-muted">{{ \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans() }}</small>
                            </div>
                            @if ($n->message ?? ($data['message'] ?? null))
                                <p class="mb-1 small text-muted">{{ $n->message ?? $data['message'] }}</p>
                            @endif
                            <div class="d-flex gap-3 small">
                                @if ($url)<a href="{{ $url }}">فتح <i class="fas fa-arrow-left mr-1 text-gold"></i></a>@endif
                                @unless ($n->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="d-inline">@csrf
                                        <button class="btn btn-link btn-sm p-0">تعليم كمقروء</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                        @if ($urgent)<span class="badge-status status-error">عاجل</span>@endif
                    </li>
                @endforeach
            </ul>
            @if ($notifications->hasPages())<div class="card-footer">{{ $notifications->withQueryString()->links() }}</div>@endif
        @endif
    </div>
@endsection
