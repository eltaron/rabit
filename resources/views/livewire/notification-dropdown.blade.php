<div class="dropdown">
    <button type="button" class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
            aria-label="الإشعارات{{ $unreadCount ? ' — ' . $unreadCount . ' غير مقروء' : '' }}">
        <i class="fas fa-bell"></i>
        @if ($unreadCount > 0)
            <span class="notification-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-start p-0" style="width:min(360px,92vw);max-height:460px;overflow-y:auto">
        <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--line);position:sticky;top:0;background:var(--white-soft);z-index:1">
            <strong style="color:var(--egypt-green);font-size:.9rem">الإشعارات</strong>
            @if ($unreadCount > 0)
                <button type="button" class="btn btn-sm btn-link p-0 fw-bold" style="color:var(--egypt-gold)" wire:click="markAllAsRead">تعليم الكل كمقروء</button>
            @endif
        </div>

        @forelse ($notifications as $n)
            <button type="button" wire:click="open('{{ $n['id'] }}')" class="dropdown-item d-flex gap-2 py-2 px-3 text-wrap"
                    style="border-radius:0;border-bottom:1px solid var(--line);{{ $n['is_read'] ? '' : 'background:var(--papyrus);' }}">
                <span class="eg-icon sm" style="{{ $n['priority'] === 'urgent' || $n['priority'] === 'high' ? 'color:var(--egypt-red)' : '' }}"><i class="fas {{ $n['icon'] }}"></i></span>
                <span class="flex-grow-1 text-start" style="min-width:0">
                    <span class="d-block {{ $n['is_read'] ? '' : 'fw-bold' }}" style="font-size:.84rem;color:var(--charcoal)">{{ $n['title'] }}</span>
                    @if ($n['message'])<small class="d-block text-muted text-truncate">{{ $n['message'] }}</small>@endif
                    <small class="text-muted" style="font-size:.7rem">{{ $n['time'] }}</small>
                </span>
                @unless ($n['is_read'])
                    <span class="flex-shrink-0 mt-2" style="width:7px;height:7px;border-radius:50%;background:var(--egypt-gold)" aria-label="غير مقروء"></span>
                @endunless
            </button>
        @empty
            <div class="empty-state py-4">
                <span class="eg-icon"><i class="fas fa-bell-slash"></i></span>
                <p class="mb-0 small">لا توجد إشعارات</p>
            </div>
        @endforelse

        <div class="text-center py-2" style="position:sticky;bottom:0;background:var(--white-soft);border-top:1px solid var(--line)">
            <a href="{{ route('notifications.index') }}" class="small fw-bold">عرض جميع الإشعارات <i class="fas fa-arrow-left mr-1 text-gold"></i></a>
        </div>
    </div>
</div>
