<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public const EVENTS = [
        'created' => 'إنشاء', 'updated' => 'تعديل', 'deleted' => 'حذف', 'moved' => 'نقل',
        'imported' => 'استيراد', 'exported' => 'تصدير', 'assigned' => 'تسكين / إسناد',
        'role_assigned' => 'تعيين دور', 'activated' => 'تفعيل', 'deactivated' => 'تعطيل',
        'password_reset' => 'إعادة تعيين كلمة مرور', 'password_changed' => 'تغيير كلمة مرور',
        'login' => 'تسجيل دخول', 'logout' => 'تسجيل خروج', 'profile_updated' => 'تحديث الملف الشخصي',
        'submitted' => 'تقديم', 'reviewed' => 'مراجعة', 'approved' => 'اعتماد', 'approved_with_report' => 'اعتماد مع تقرير',
        'rejected' => 'رفض', 'revision_required' => 'طلب تعديل', 'sent' => 'إرسال',
        'ai_content_generated' => 'توليد محتوى ذكي', 'decision_query' => 'استعلام دعم القرار', 'version_reverted' => 'استرجاع نسخة',
    ];

    public static function eventLabel(string $event): string
    {
        return self::EVENTS[$event] ?? str_replace('_', ' ', $event);
    }

    public static function typeLabel(?string $type): string
    {
        return [
            \App\Models\User::class => 'حساب', \App\Models\OrgUnit::class => 'جهة', \App\Models\JobPosition::class => 'وظيفة',
            \Spatie\Permission\Models\Role::class => 'دور', \App\Models\FormTemplate::class => 'نموذج', \App\Models\FormSubmission::class => 'إرسال',
            \App\Models\Report::class => 'تقرير',
        ][$type] ?? ($type ? class_basename($type) : '—');
    }

    public function index(Request $request): View
    {
        $query = AuditLog::with('user:id,name,email');

        if ($userId = $request->input('user_id')) $query->where('user_id', $userId);
        if ($event = $request->input('event')) $query->where('event', $event);
        if ($type = $request->input('auditable_type')) $query->where('auditable_type', $type);
        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%"));
        }
        if ($from = $request->input('from_date')) $query->where('created_at', '>=', $from);
        if ($to = $request->input('to_date')) $query->where('created_at', '<=', $to . ' 23:59:59');

        $logs = $query->latest()->paginate(30)->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'users' => User::whereIn('id', AuditLog::query()->select('user_id')->distinct())->orderBy('name')->pluck('name', 'id'),
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'types' => AuditLog::query()->whereNotNull('auditable_type')->distinct()->pluck('auditable_type'),
            'today' => AuditLog::whereDate('created_at', today())->count(),
            'total' => AuditLog::count(),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');
        $old = (array) ($auditLog->old_values ?? []);
        $new = (array) ($auditLog->new_values ?? []);
        $keys = collect(array_keys($old))->merge(array_keys($new))->unique()
            ->reject(fn ($k) => in_array($k, ['updated_at', 'created_at', 'remember_token', 'two_factor_secret'], true))->values();

        return view('admin.audit-logs.show', ['log' => $auditLog, 'old' => $old, 'new' => $new, 'keys' => $keys]);
    }
}
