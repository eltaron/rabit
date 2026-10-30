<nav class="top-navbar" aria-label="شريط الأدوات">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
        <button class="icon-btn d-xl-none" onclick="toggleSidebar()" aria-label="فتح القائمة" aria-controls="sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <form class="top-search" role="search" action="{{ route('search') }}" method="GET">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" id="globalSearch" value="{{ request('q') }}" placeholder="ابحث في الجهات والنماذج والبيانات…" aria-label="بحث شامل" autocomplete="off">
            <kbd class="d-none d-lg-inline en" style="font-size:.65rem;background:var(--papyrus);color:var(--text-soft);border:1px solid var(--line)">Ctrl K</kbd>
        </form>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('search') }}" class="icon-btn d-md-none" aria-label="بحث"><i class="fas fa-magnifying-glass"></i></a>
        @can('view_map')
            <a href="{{ route('map.index') }}" class="icon-btn d-none d-sm-inline-flex" title="الخريطة التفاعلية" aria-label="الخريطة التفاعلية"><i class="fas fa-map-location-dot"></i></a>
        @endcan
        @livewire('notification-dropdown')

        <div class="dropdown">
            <a href="#" class="user-chip" data-bs-toggle="dropdown" aria-expanded="false" aria-label="قائمة الحساب">
                <div class="d-none d-md-block text-end lh-sm">
                    <div style="font-size:.82rem;font-weight:700;color:var(--egypt-green)">{{ auth()->user()->name ?? 'مستخدم' }}</div>
                    <small style="font-size:.68rem;color:var(--text-soft)">{{ \App\Support\RoleLabels::label(auth()->user()?->getRoleNames()->first() ?? '') }}</small>
                </div>
                <div class="avatar">{{ mb_substr(auth()->user()->name ?? 'م', 0, 1, 'UTF-8') }}</div>
            </a>
            <ul class="dropdown-menu dropdown-menu-start" style="min-width:240px">
                <li class="px-3 py-2">
                    <div class="fw-bold" style="color:var(--egypt-green)">{{ auth()->user()->name ?? '' }}</div>
                    <small class="text-muted en">{{ auth()->user()->email ?? '' }}</small>
                    @if (auth()->user()?->orgUnit)
                        <div><small class="text-muted"><i class="fas fa-building ml-1 text-gold"></i>{{ auth()->user()->orgUnit->name }}</small></div>
                    @endif
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-id-card ml-2 text-gold"></i>الملف الشخصي</a></li>
                @if (auth()->user()?->two_factor_enabled)
                    <li><span class="dropdown-item text-success"><i class="fas fa-shield-halved ml-2"></i>المصادقة الثنائية مفعلة</span></li>
                @else
                    <li><a class="dropdown-item" href="{{ route('2fa.setup') }}" style="color:var(--warning)"><i class="fas fa-shield-halved ml-2"></i>تفعيل المصادقة الثنائية</a></li>
                @endif
                <li><a class="dropdown-item" href="{{ route('manual') }}"><i class="fas fa-book-open ml-2 text-gold"></i>دليل الاستخدام</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-right-from-bracket ml-2"></i>تسجيل الخروج</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
