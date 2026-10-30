@php
    $u = auth()->user();
    $nav = [
        ['section' => 'الرئيسية'],
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'fa-gauge-high', 'label' => 'لوحة التحكم'],
        ['route' => 'map.index', 'match' => 'map.*', 'icon' => 'fa-map-location-dot', 'label' => 'خريطة كفر الشيخ التفاعلية', 'can' => 'view_map'],

        ['section' => 'القيادات', 'can' => 'use_decision_support'],
        ['route' => 'decision.index', 'match' => 'decision.*', 'icon' => 'fa-chess-king', 'label' => 'مساعد دعم القرار', 'can' => 'use_decision_support'],

        ['section' => 'تداول البيانات'],
        ['route' => 'forms.templates.index', 'match' => 'forms.templates*', 'icon' => 'fa-file-lines', 'label' => 'النماذج', 'can' => 'manage_form_templates'],
        ['route' => 'submissions.index', 'match' => 'submissions.*', 'icon' => 'fa-inbox', 'label' => 'الإرسالات والبيانات'],
        ['route' => 'excel.center', 'match' => 'excel.*', 'icon' => 'fa-file-excel', 'label' => 'مركز ملفات Excel', 'can' => 'import_export_excel'],
        ['route' => 'excel-upload.index', 'match' => 'excel-upload.*', 'icon' => 'fa-database', 'label' => 'قواعد البيانات القطاعية', 'can' => 'manage_data_uploads'],
        ['route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'fa-chart-column', 'label' => 'التقارير', 'can' => 'view_reports'],

        ['section' => 'الهيكل', 'can' => ['manage_org_structure', 'manage_positions']],
        ['route' => 'admin.org-units.index', 'match' => 'admin.org-units.*', 'icon' => 'fa-sitemap', 'label' => 'الهيكل التنظيمي', 'can' => 'manage_org_structure'],
        ['route' => 'admin.positions.index', 'match' => 'admin.positions.*', 'icon' => 'fa-people-group', 'label' => 'الهيكل الوظيفي', 'can' => 'manage_positions'],

        ['section' => 'الحسابات والأمان', 'can' => ['manage_users', 'manage_roles', 'view_audit_logs']],
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'fa-users-gear', 'label' => 'الحسابات', 'can' => 'manage_users'],
        ['route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'icon' => 'fa-user-shield', 'label' => 'الأدوار والصلاحيات', 'can' => 'manage_roles'],
        ['route' => 'admin.audit-logs.index', 'match' => 'admin.audit-logs.*', 'icon' => 'fa-clock-rotate-left', 'label' => 'سجل التدقيق', 'can' => 'view_audit_logs'],

        ['section' => 'المساعدة'],
        ['route' => 'chatbot.index', 'match' => 'chatbot.*', 'icon' => 'fa-robot', 'label' => 'المساعد الذكي', 'can' => 'use_chatbot'],
        ['route' => 'manual', 'match' => 'manual', 'icon' => 'fa-book-open', 'label' => 'دليل الاستخدام'],
        ['route' => 'profile.edit', 'match' => 'profile.*', 'icon' => 'fa-id-card', 'label' => 'الملف الشخصي'],
    ];
    $allowed = function ($item) use ($u) {
        if (empty($item['can'])) return true;
        foreach ((array) $item['can'] as $p) { if ($u?->can($p)) return true; }
        return false;
    };
@endphp
<aside class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" aria-label="رابط — الرئيسية"><img src="{{ asset('logo.png') }}" alt="رابط"></a>
        <div class="brand-sub">منصة تداول البيانات المكانية والوصفية<br>محافظة كفر الشيخ</div>
    </div>
    <div class="egypt-cornice is-soft" aria-hidden="true"></div>

    <nav>
        <ul class="nav flex-column">
            @foreach ($nav as $item)
                @continue(! $allowed($item))
                @if (isset($item['section']))
                    <li class="nav-section">{{ $item['section'] }}</li>
                @elseif (Route::has($item['route']))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}"
                           @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                            <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>

    <div class="user-panel">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar">{{ mb_substr($u->name ?? 'م', 0, 1, 'UTF-8') }}</div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="text-truncate" style="color:var(--white-soft);font-size:.83rem;font-weight:600;">{{ $u->name ?? 'مستخدم' }}</div>
                <small class="d-block text-truncate" style="color:var(--egypt-gold-2);font-size:.7rem;">
                    {{ \App\Support\RoleLabels::label($u?->getRoleNames()->first() ?? '') }}
                    @if ($u?->orgUnit) · {{ $u->orgUnit->name }} @endif
                </small>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm" style="color:rgba(254,253,252,.55)" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                    <i class="fas fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
