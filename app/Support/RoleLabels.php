<?php

namespace App\Support;

/**
 * كتالوج الصلاحيات والأدوار بالعربية (المصدر الوحيد لأسماء العرض والتجميع).
 */
class RoleLabels
{
    /** مجموعات الصلاحيات: group => [label, icon, permissions => [name => label]] */
    public const PERMISSION_GROUPS = [
        'dashboard' => ['لوحات المعلومات', 'fa-gauge-high', [
            'view_dashboard' => 'عرض لوحة التحكم',
            'view_governor_dashboard' => 'لوحة القيادة العليا (المحافظ)',
        ]],
        'map' => ['الخريطة والبيانات المكانية', 'fa-map-location-dot', [
            'view_map' => 'عرض الخريطة التفاعلية',
            'manage_spatial_data' => 'إدارة الطبقات والبيانات المكانية',
        ]],
        'decision' => ['القيادات ودعم القرار', 'fa-chess-king', [
            'use_decision_support' => 'استخدام مساعد دعم القرار الذكي',
            'use_chatbot' => 'استخدام المساعد الذكي العام',
        ]],
        'forms' => ['النماذج وتداول البيانات', 'fa-file-lines', [
            'manage_form_templates' => 'إنشاء وتعديل النماذج',
            'distribute_forms' => 'توجيه النماذج للجهات',
            'create_submissions' => 'إنشاء إرسالات',
            'submit_data' => 'إدخال البيانات',
            'review_submissions' => 'مراجعة الإرسالات',
            'approve_submissions' => 'اعتماد / رفض الإرسالات',
            'import_export_excel' => 'تصدير واستيراد Excel للنماذج',
            'view_all_data' => 'الاطلاع على بيانات كل المحافظة',
        ]],
        'uploads' => ['قواعد البيانات القطاعية', 'fa-database', [
            'manage_data_uploads' => 'رفع قواعد البيانات القطاعية',
        ]],
        'reports' => ['التقارير', 'fa-chart-column', [
            'view_reports' => 'عرض التقارير',
            'manage_reports' => 'إنشاء وتعديل التقارير',
            'approve_reports' => 'اعتماد التقارير',
            'send_reports' => 'إرسال التقارير',
        ]],
        'structure' => ['الهيكل التنظيمي والوظيفي', 'fa-sitemap', [
            'manage_org_structure' => 'إدارة الهيكل التنظيمي (الجهات والمراكز والقرى)',
            'manage_positions' => 'إدارة الهيكل الوظيفي',
            'manage_governorates' => 'إدارة المحافظة (قديم)',
            'manage_sectors' => 'إدارة القطاعات (قديم)',
            'manage_departments' => 'إدارة المديريات (قديم)',
            'manage_centers' => 'إدارة المراكز (قديم)',
            'manage_divisions' => 'إدارة الأقسام (قديم)',
        ]],
        'admin' => ['الحسابات والأمان', 'fa-user-shield', [
            'manage_users' => 'إدارة الحسابات',
            'manage_roles' => 'إدارة الأدوار والصلاحيات',
            'view_audit_logs' => 'عرض سجل التدقيق',
            'view_notifications' => 'عرض الإشعارات',
        ]],
    ];

    public const ROLES = [
        'super_admin' => 'مدير النظام',
        'governor' => 'السيد المحافظ',
        'leader' => 'القيادات التنفيذية',
        'manager' => 'مدير / رئيس جهة',
        'reviewer' => 'مراجع بيانات',
        'employee' => 'مدخل بيانات',
        'authority' => 'السلطة المختصة',
        'gis_officer' => 'مسؤول نظم المعلومات الجغرافية',
        'viewer' => 'مستعرض (قراءة فقط)',
    ];

    private static ?array $custom = null;

    /** أسماء عرض مخصصة للأدوار المضافة من الواجهة (storage/app/role_labels.json) */
    public static function customLabels(): array
    {
        if (self::$custom === null) {
            $f = storage_path('app/role_labels.json');
            self::$custom = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
        }
        return self::$custom;
    }

    public static function saveCustomLabel(string $role, ?string $label): void
    {
        $labels = self::customLabels();
        if ($label && $label !== (self::ROLES[$role] ?? null)) $labels[$role] = $label;
        else unset($labels[$role]);
        file_put_contents(storage_path('app/role_labels.json'), json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        self::$custom = $labels;
    }

    public static function label(string $role): string
    {
        return self::customLabels()[$role] ?? self::ROLES[$role] ?? str_replace('_', ' ', $role);
    }

    public static function permissionLabel(string $perm): string
    {
        foreach (self::PERMISSION_GROUPS as [$g, $i, $perms]) {
            if (isset($perms[$perm])) return $perms[$perm];
        }
        return str_replace('_', ' ', $perm);
    }

    public static function allPermissions(): array
    {
        return collect(self::PERMISSION_GROUPS)->flatMap(fn ($g) => array_keys($g[2]))->values()->all();
    }

    /** الصلاحيات الافتراضية لكل دور */
    public static function defaults(): array
    {
        $all = self::allPermissions();
        $views = ['view_dashboard', 'view_map', 'view_reports', 'view_notifications'];

        return [
            'super_admin' => $all,
            'governor' => array_merge($views, ['view_governor_dashboard', 'use_decision_support', 'use_chatbot', 'approve_reports', 'view_all_data']),
            'leader' => array_merge($views, ['view_governor_dashboard', 'use_decision_support', 'use_chatbot', 'approve_reports']),
            'manager' => array_merge($views, [
                'manage_form_templates', 'distribute_forms', 'create_submissions', 'submit_data', 'review_submissions',
                'approve_submissions', 'import_export_excel', 'manage_reports', 'approve_reports', 'send_reports',
                'manage_positions', 'use_chatbot', 'use_decision_support', 'manage_data_uploads',
            ]),
            'reviewer' => array_merge($views, ['create_submissions', 'submit_data', 'review_submissions', 'import_export_excel', 'use_chatbot']),
            'employee' => array_merge($views, ['create_submissions', 'submit_data', 'import_export_excel', 'use_chatbot']),
            'authority' => array_merge($views, ['send_reports', 'review_submissions', 'use_chatbot']),
            'gis_officer' => array_merge($views, ['manage_spatial_data', 'manage_data_uploads', 'import_export_excel']),
            'viewer' => $views,
        ];
    }

    /** نطاق البيانات الافتراضي لكل دور */
    public const DEFAULT_SCOPE = [
        'super_admin' => 'all', 'governor' => 'all', 'leader' => 'subtree', 'manager' => 'subtree',
        'reviewer' => 'subtree', 'employee' => 'unit', 'authority' => 'subtree', 'gis_officer' => 'all', 'viewer' => 'unit',
    ];
}
