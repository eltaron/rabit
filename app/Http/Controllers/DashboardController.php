<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FacilityData;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\JobPosition;
use App\Models\OrgUnit;
use App\Models\Report;
use App\Models\SectorData;
use App\Models\User;
use App\Support\DataScope;
use App\Support\RoleLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * لوحات التحكم حسب الدور — كل الأرقام مقيدة بنطاق بيانات المستخدم (DataScope).
 */
class DashboardController extends Controller
{
    /** حالات الإرسال مجمعة لمؤشرات القيادة */
    private const OPEN_STATUSES = ['draft', 'pending_assignment', 'assigned', 'data_entry', 'revision_required'];
    private const REVIEW_STATUSES = ['under_review'];
    private const DONE_STATUSES = ['approved', 'report_pending', 'report_draft', 'report_approved', 'report_sent'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $this->profileFor($user);

        $data = $this->common($user);
        $data += match ($profile) {
            'admin' => $this->adminData($user),
            'governor', 'leader' => $this->executiveData($user),
            'manager' => $this->managerData($user),
            'authority' => $this->authorityData($user),
            'employee' => $this->employeeData($user),
            default => [],
        };
        $data['profile'] = $profile;
        $data['user'] = $user;

        return view('dashboard.' . ($profile === 'general' ? 'index' : $profile), $data);
    }

    /** يحدد نوع اللوحة؛ الأدوار الجديدة/المخصصة ترجع للوحة العامة أو الأقرب حسب صلاحياتها */
    private function profileFor(User $user): string
    {
        return match (true) {
            $user->hasRole('super_admin') => 'admin',
            $user->hasRole('governor') => 'governor',
            $user->hasRole('leader') => 'leader',
            $user->can('view_governor_dashboard') => 'governor',
            $user->hasAnyRole(['manager', 'reviewer']) => 'manager',
            $user->hasRole('authority') => 'authority',
            $user->hasRole('employee') => 'employee',
            $user->can('review_submissions') || $user->can('approve_submissions') => 'manager',
            $user->can('submit_data') => 'employee',
            default => 'general',
        };
    }

    /* =================================================================== shared */

    private function subs(User $user): Builder
    {
        return DataScope::submissions(FormSubmission::query(), $user);
    }

    private function reports(User $user): Builder
    {
        $q = Report::query();
        $ids = DataScope::unitIds($user);
        if ($ids === null) return $q;

        return $q->where(function ($w) use ($user, $ids) {
            $w->where('reports.created_by', $user->id)->orWhere('reports.sent_to', $user->id);
            if ($ids) $w->orWhereHas('createdBy', fn ($u) => $u->whereIn('org_unit_id', $ids));
        });
    }

    private function common(User $user): array
    {
        $byStatus = $this->subs($user)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $total = (int) $byStatus->sum();
        $open = (int) $byStatus->only(self::OPEN_STATUSES)->sum();
        $review = (int) $byStatus->only(self::REVIEW_STATUSES)->sum();
        $done = (int) $byStatus->only(self::DONE_STATUSES)->sum();
        $rejected = (int) ($byStatus['rejected'] ?? 0);

        $unit = $user->orgUnit;
        $level = DataScope::level($user);

        return [
            'scopeLevel' => $level,
            'scopeLabel' => DataScope::LEVELS[$level] ?? '',
            'unit' => $unit,
            'roleLabel' => RoleLabels::label($user->getRoleNames()->first() ?? ''),
            'greeting' => now()->hour < 12 ? 'صباح الخير' : 'مساء الخير',
            'counts' => compact('total', 'open', 'review', 'done', 'rejected'),
            'completionRate' => $total ? round($done / $total * 100) : 0,
            'statusBreakdown' => $this->statusBreakdown($byStatus),
            'monthly' => $this->monthly($this->subs($user)),
            'recentSubmissions' => $this->subs($user)->with(['formTemplate', 'createdBy', 'orgUnit'])->latest()->take(8)->get(),
            'assignedToMe' => FormSubmission::with(['formTemplate', 'createdBy'])
                ->where('assigned_to', $user->id)
                ->whereIn('status', ['assigned', 'data_entry', 'revision_required'])
                ->latest()->take(8)->get(),
            'dueForms' => $this->dueForms($user),
            'recentActivity' => $this->recentActivity($user),
        ];
    }

    /** توزيع الحالات مجمعاً بثلاث فئات حالة + مرفوض */
    private function statusBreakdown($byStatus): array
    {
        $rows = [
            ['key' => 'open', 'label' => 'قيد الإدخال', 'class' => 'pending', 'value' => (int) $byStatus->only(self::OPEN_STATUSES)->sum()],
            ['key' => 'review', 'label' => 'قيد المراجعة', 'class' => 'info', 'value' => (int) $byStatus->only(self::REVIEW_STATUSES)->sum()],
            ['key' => 'done', 'label' => 'معتمد', 'class' => 'success', 'value' => (int) $byStatus->only(self::DONE_STATUSES)->sum()],
            ['key' => 'rejected', 'label' => 'مرفوض', 'class' => 'error', 'value' => (int) ($byStatus['rejected'] ?? 0)],
        ];
        $max = max(1, collect($rows)->sum('value'));
        foreach ($rows as &$r) $r['pct'] = round($r['value'] / $max * 100);
        return $rows;
    }

    /** آخر 6 أشهر (سلسلة واحدة) */
    private function monthly(Builder $q, string $col = 'created_at'): array
    {
        $from = now()->subMonths(5)->startOfMonth();
        $raw = $q->where($col, '>=', $from)
            ->select(DB::raw("DATE_FORMAT({$col}, '%Y-%m') as m"), DB::raw('count(*) as total'))
            ->groupBy('m')->pluck('total', 'm');

        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $out[] = ['label' => $d->locale('ar')->translatedFormat('F'), 'value' => (int) ($raw[$d->format('Y-m')] ?? 0)];
        }
        return $out;
    }

    /** النماذج الموجهة لجهة المستخدم (أو أسلافها مع شمول التابعين) ولها موعد */
    private function dueForms(User $user)
    {
        if (! $user->org_unit_id || ! Schema::hasTable('form_template_org_unit')) return collect();
        $unit = $user->orgUnit;
        $ancestors = $unit?->ancestorIds(false) ?? [];

        $rows = DB::table('form_template_org_unit as p')
            ->join('form_templates as t', 't.id', '=', 'p.form_template_id')
            ->whereNull('t.deleted_at')->where('t.is_active', true)
            ->where(function ($w) use ($unit, $ancestors) {
                $w->where('p.org_unit_id', $unit->id)
                  ->orWhere(fn ($x) => $x->whereIn('p.org_unit_id', $ancestors ?: [0])->where('p.include_children', true));
            })
            ->orderByRaw('p.due_date IS NULL, p.due_date')
            ->limit(8)
            ->get(['t.id', 't.name', 'p.due_date']);

        $mine = FormSubmission::whereIn('form_template_id', $rows->pluck('id'))
            ->where('org_unit_id', $user->org_unit_id)
            ->select('form_template_id', DB::raw('max(status) as status'), DB::raw('count(*) as c'))
            ->groupBy('form_template_id')->get()->keyBy('form_template_id');

        return $rows->map(function ($r) use ($mine) {
            $due = $r->due_date ? Carbon::parse($r->due_date) : null;
            $r->submitted = isset($mine[$r->id]);
            $r->days_left = $due ? (int) now()->startOfDay()->diffInDays($due, false) : null;
            $r->state = $r->submitted ? 'success' : ($due && $r->days_left < 0 ? 'error' : ($due && $r->days_left <= 3 ? 'pending' : 'info'));
            return $r;
        });
    }

    private function recentActivity(User $user): array
    {
        $q = AuditLog::with('user')->latest();
        $ids = DataScope::unitIds($user);
        if ($ids !== null) {
            $q->where(fn ($w) => $w->where('user_id', $user->id)
                ->orWhereHas('user', fn ($u) => $u->whereIn('org_unit_id', $ids ?: [0])));
        }

        $icons = [
            'login' => 'fa-right-to-bracket', 'logout' => 'fa-right-from-bracket', 'created' => 'fa-plus',
            'updated' => 'fa-pen', 'deleted' => 'fa-trash-can', 'approved' => 'fa-check', 'rejected' => 'fa-xmark',
            'submitted' => 'fa-paper-plane', 'imported' => 'fa-file-import', 'exported' => 'fa-file-export',
        ];

        return $q->take(8)->get()->map(fn ($log) => [
            'icon' => $icons[$log->event] ?? 'fa-circle-dot',
            'description' => $log->description ?? $log->event,
            'user' => $log->user?->name ?? 'النظام',
            'date' => $log->created_at?->diffForHumans(),
        ])->all();
    }

    /** أداء الجهات التابعة: عدد الإرسالات ونسبة الاعتماد لكل جهة مباشرة تحت نطاق المستخدم */
    private function unitPerformance(User $user, int $limit = 12)
    {
        $base = DataScope::level($user) === 'all' || ! $user->orgUnit ? OrgUnit::root() : $user->orgUnit;
        if (! $base) return collect();

        $children = OrgUnit::where('parent_id', $base->id)->active()->get(['id', 'name', 'type', 'path']);
        if ($children->isEmpty()) $children = collect([$base]);

        $counts = FormSubmission::query()
            ->join('org_units', 'org_units.id', '=', 'form_submissions.org_unit_id')
            ->where('org_units.path', 'like', $base->path . '%')
            ->select('org_units.path', 'form_submissions.status', DB::raw('count(*) as c'))
            ->groupBy('org_units.path', 'form_submissions.status')->get();

        return $children->map(function ($u) use ($counts) {
            $mine = $counts->filter(fn ($r) => str_starts_with($r->path, $u->path));
            $total = (int) $mine->sum('c');
            $done = (int) $mine->whereIn('status', self::DONE_STATUSES)->sum('c');
            $review = (int) $mine->whereIn('status', self::REVIEW_STATUSES)->sum('c');
            $rate = $total ? round($done / $total * 100) : 0;
            return (object) [
                'id' => $u->id, 'name' => $u->name, 'type' => OrgUnit::TYPES[$u->type] ?? $u->type,
                'total' => $total, 'done' => $done, 'review' => $review, 'rate' => $rate,
                'state' => $total === 0 ? 'draft' : ($rate >= 70 ? 'success' : ($rate >= 40 ? 'pending' : 'error')),
            ];
        })->sortByDesc('total')->take($limit)->values();
    }

    private function spatialVolume(User $user): array
    {
        return [
            'facilities' => DataScope::byUnit(FacilityData::query(), $user)->count(),
            'facilities_geo' => DataScope::byUnit(FacilityData::query(), $user)->whereNotNull('latitude')->count(),
            'sector' => DataScope::byUnit(SectorData::query(), $user)->count(),
            'layers' => FacilityData::distinct('module_code')->count('module_code') + SectorData::distinct('sector')->count('sector'),
        ];
    }

    /* =================================================================== profiles */

    private function adminData(User $user): array
    {
        $roleDistribution = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->select('roles.name', DB::raw('count(*) as total'))
            ->groupBy('roles.name')->orderByDesc('total')->get()
            ->map(fn ($r) => ['label' => RoleLabels::label($r->name), 'value' => (int) $r->total]);

        return [
            'admin' => [
                'users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'units' => OrgUnit::count(),
                'positions' => Schema::hasTable('job_positions') ? JobPosition::count() : 0,
                'vacant' => Schema::hasTable('job_positions') ? JobPosition::whereNull('user_id')->count() : 0,
                'templates' => FormTemplate::count(),
                'active_templates' => FormTemplate::where('is_active', true)->count(),
                'reports' => Report::count(),
                'roles' => DB::table('roles')->count(),
            ],
            'spatial' => $this->spatialVolume($user),
            'roleDistribution' => $roleDistribution,
            'recentUsers' => User::with(['orgUnit', 'roles'])->latest()->take(6)->get(),
            'topTemplates' => FormTemplate::withCount('formSubmissions')->orderByDesc('form_submissions_count')->take(6)->get(),
            'unitPerformance' => $this->unitPerformance($user, 8),
        ];
    }

    private function executiveData(User $user): array
    {
        $reports = $this->reports($user);

        return [
            'spatial' => $this->spatialVolume($user),
            'unitPerformance' => $this->unitPerformance($user),
            'reportsAwaiting' => (clone $reports)->with('createdBy')->where('status', 'pending_approval')->latest()->take(6)->get(),
            'reportsCount' => [
                'total' => (clone $reports)->count(),
                'awaiting' => (clone $reports)->where('status', 'pending_approval')->count(),
                'approved' => (clone $reports)->whereIn('status', ['approved', 'sent'])->count(),
            ],
            'approvedMonthly' => $this->monthly($this->subs($user)->whereNotNull('approved_at'), 'approved_at'),
            'activeUsers' => DataScope::unitIds($user) === null
                ? User::where('is_active', true)->count()
                : User::where('is_active', true)->whereIn('org_unit_id', DataScope::unitIds($user) ?: [0])->count(),
            'templatesCount' => DataScope::templates(FormTemplate::query(), $user)->where('is_active', true)->count(),
        ];
    }

    private function managerData(User $user): array
    {
        $reports = $this->reports($user);

        return [
            'pendingReview' => $this->subs($user)->with(['formTemplate', 'createdBy', 'orgUnit'])
                ->where('status', 'under_review')->oldest('submitted_at')->take(8)->get(),
            'myTemplates' => FormTemplate::where('created_by', $user->id)->count(),
            'reportsAwaiting' => (clone $reports)->where('status', 'pending_approval')->count(),
            'recentReports' => (clone $reports)->with('createdBy')->latest()->take(5)->get(),
            'unitPerformance' => $this->unitPerformance($user, 8),
        ];
    }

    private function authorityData(User $user): array
    {
        $reports = $this->reports($user);
        $byStatus = (clone $reports)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return [
            'reportStats' => [
                'total' => (int) $byStatus->sum(),
                'draft' => (int) ($byStatus['draft'] ?? 0),
                'awaiting' => (int) ($byStatus['pending_approval'] ?? 0),
                'approved' => (int) ($byStatus['approved'] ?? 0),
                'sent' => (int) ($byStatus['sent'] ?? 0),
                'rejected' => (int) ($byStatus['rejected'] ?? 0),
            ],
            'reportsQueue' => (clone $reports)->with('createdBy')->whereIn('status', ['pending_approval', 'approved'])->latest()->take(8)->get(),
            'reportsMonthly' => $this->monthly($this->reports($user)),
        ];
    }

    private function employeeData(User $user): array
    {
        $mine = FormSubmission::where('created_by', $user->id);

        return [
            'myCounts' => [
                'mine' => (clone $mine)->count(),
                'draft' => (clone $mine)->where('status', 'draft')->count(),
                'revision' => FormSubmission::where(fn ($w) => $w->where('created_by', $user->id)->orWhere('assigned_to', $user->id))
                    ->where('status', 'revision_required')->count(),
                'approved' => (clone $mine)->whereIn('status', self::DONE_STATUSES)->count(),
            ],
            'mySubmissions' => (clone $mine)->with('formTemplate')->latest()->take(6)->get(),
        ];
    }
}
