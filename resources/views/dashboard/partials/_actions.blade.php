{{-- إجراءات سريعة: تظهر حسب الصلاحيات فقط --}}
@php
    $all = [
        ['map.index', 'view_map', 'fa-map-location-dot', 'الخريطة التفاعلية', 'استعرض البيانات المكانية للمراكز والقرى والمنشآت', 'افتح الخريطة'],
        ['decision.index', 'use_decision_support', 'fa-chess-king', 'مساعد دعم القرار', 'تحليلات وتوصيات ذكية مبنية على بيانات المحافظة', 'اسأل المساعد'],
        ['submissions.index', null, 'fa-inbox', 'الإرسالات والبيانات', 'تابع إدخال البيانات ومراجعتها واعتمادها', 'عرض الإرسالات'],
        ['excel.center', 'import_export_excel', 'fa-file-excel', 'مركز ملفات Excel', 'حمّل قالب النموذج وأعد رفعه للإضافة أو التعديل', 'فتح المركز'],
        ['forms.templates.index', 'manage_form_templates', 'fa-file-lines', 'النماذج', 'صمّم النماذج ووجّهها للجهات والمراكز', 'إدارة النماذج'],
        ['reports.index', 'view_reports', 'fa-chart-column', 'التقارير', 'التقارير المعتمدة والمرسلة', 'عرض التقارير'],
        ['admin.org-units.index', 'manage_org_structure', 'fa-sitemap', 'الهيكل التنظيمي', 'المديريات والمراكز والمدن والقرى', 'إدارة الهيكل'],
        ['admin.positions.index', 'manage_positions', 'fa-people-group', 'الهيكل الوظيفي', 'الوظائف والتبعية والشاغلون', 'إدارة الوظائف'],
        ['admin.users.index', 'manage_users', 'fa-users-gear', 'الحسابات', 'إنشاء الحسابات وتسكينها على الجهات', 'إدارة الحسابات'],
        ['admin.roles.index', 'manage_roles', 'fa-user-shield', 'الأدوار والصلاحيات', 'تحكم في صلاحيات كل دور بسهولة', 'إدارة الأدوار'],
        ['manual', null, 'fa-book-open', 'دليل الاستخدام', 'خطوات استخدام المنصة لكل دور', 'قراءة الدليل'],
    ];
    $only = $only ?? null;
    $cards = collect($all)->filter(fn ($a) => Route::has($a[0]) && (! $a[1] || auth()->user()->can($a[1])) && (! $only || in_array($a[0], $only)))
        ->take($limit ?? 6);
@endphp
@if ($cards->isNotEmpty())
    <div class="row g-3 mb-4">
        @foreach ($cards as $i => $a)
            <div class="col-sm-6 col-xl-{{ $cards->count() >= 4 ? 4 : 12 / max(1, $cards->count()) }}" data-reveal data-delay="{{ $loop->index * 0.05 }}">
                <a href="{{ route($a[0]) }}" class="service-card">
                    <span class="eg-icon"><i class="fas {{ $a[2] }}"></i></span>
                    <h5>{{ $a[3] }}</h5>
                    <p>{{ $a[4] }}</p>
                    <span class="sc-link">{{ $a[5] }} <i class="fas fa-arrow-left mr-1"></i></span>
                </a>
            </div>
        @endforeach
    </div>
@endif
