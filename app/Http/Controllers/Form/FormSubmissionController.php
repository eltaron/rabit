<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionData;
use App\Models\FormTemplate;
use App\Models\OrgUnit;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkflowLog;
use App\Services\FormExcelService;
use App\Support\DataScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FormSubmissionController extends Controller
{
    public const STATUS_LABELS = [
        'draft' => 'مسودة',
        'pending_assignment' => 'بانتظار الإسناد',
        'assigned' => 'مُسند',
        'data_entry' => 'جاري الإدخال',
        'under_review' => 'قيد المراجعة',
        'revision_required' => 'مطلوب تعديل',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'report_pending' => 'بانتظار التقرير',
        'report_draft' => 'تقرير مسودة',
        'report_approved' => 'تقرير معتمد',
        'report_sent' => 'تقرير مُرسل',
    ];

    public function index(Request $request): View
    {
        $user = Auth::user();
        $base = DataScope::submissions(FormSubmission::query(), $user);

        $query = (clone $base)->with(['formTemplate:id,name', 'createdBy:id,name', 'assignedTo:id,name', 'orgUnit:id,name']);

        $currentTab = $request->input('tab', 'all');
        match ($currentTab) {
            'mine' => $query->where(fn ($q) => $q->where('created_by', $user->id)->orWhere('assigned_to', $user->id)),
            'assigned' => $query->where('assigned_to', $user->id)->whereIn('status', FormExcelService::EDITABLE_STATUSES),
            'in_review' => $query->where('status', 'under_review'),
            'revision' => $query->where('status', 'revision_required'),
            'approved' => $query->whereIn('status', ['approved', 'report_pending', 'report_draft', 'report_approved', 'report_sent']),
            'rejected' => $query->where('status', 'rejected'),
            'all' => null,
            default => array_key_exists($currentTab, self::STATUS_LABELS) ? $query->where('status', $currentTab) : null,
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('reference_number', 'like', "%{$search}%")
                ->orWhereHas('formTemplate', fn ($t) => $t->where('name', 'like', "%{$search}%")));
        }
        if ($templateId = $request->input('form_template_id')) {
            $query->where('form_template_id', $templateId);
        }
        if ($unit = $request->integer('org_unit_id')) {
            $query->whereIn('org_unit_id', OrgUnit::find($unit)?->descendantIds()->all() ?? [0]);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();
        $templates = DataScope::templates(FormTemplate::query(), $user)->orderBy('name')->pluck('name', 'id');

        $scopeIds = DataScope::unitIds($user);
        $units = OrgUnit::treeOptions($scopeIds === null ? null : collect($scopeIds));

        $counts = [
            'all' => (clone $base)->count(),
            'mine' => (clone $base)->where(fn ($q) => $q->where('created_by', $user->id)->orWhere('assigned_to', $user->id))->count(),
            'assigned' => (clone $base)->where('assigned_to', $user->id)->whereIn('status', FormExcelService::EDITABLE_STATUSES)->count(),
            'in_review' => (clone $base)->where('status', 'under_review')->count(),
            'revision' => (clone $base)->where('status', 'revision_required')->count(),
            'approved' => (clone $base)->whereIn('status', ['approved', 'report_pending', 'report_draft', 'report_approved', 'report_sent'])->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        return view('forms.submissions.index', compact('submissions', 'templates', 'currentTab', 'counts', 'units'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('create_submissions'), 403);

        $templates = DataScope::templates(FormTemplate::query(), $request->user())
            ->with(['formFields' => fn ($q) => $q->where('is_visible', true), 'orgUnit:id,name'])
            ->where('is_active', true)->orderBy('name')->get();

        return view('forms.submissions.create', [
            'templates' => $templates,
            'users' => $this->assignableUsers($request->user()),
            'selected' => $request->input('form_template_id', $request->input('template_id')),
            'modes' => FormExcelService::MODES,
        ]);
    }

    public function show(FormSubmission $formSubmission): View
    {
        $this->authorizeView($formSubmission);

        $formSubmission->load([
            'formTemplate', 'orgUnit',
            'createdBy', 'assignedTo', 'reviewedBy', 'approvedBy',
        ]);

        // معاينة أول 300 صف فقط (الإرسالات الكبيرة تُعرض كاملة عبر تصدير Excel)
        $dataQ = FormSubmissionData::where('form_submission_id', $formSubmission->id);
        $totalRows = (clone $dataQ)->distinct()->count('row_index');
        $rowIdx = (clone $dataQ)->select('row_index')->distinct()->orderBy('row_index')->limit(300)->pluck('row_index');
        $groupedData = (clone $dataQ)->whereIn('row_index', $rowIdx)
            ->get(['row_index', 'form_field_id', 'value'])
            ->groupBy('row_index')->sortKeys();

        $fields = $formSubmission->formTemplate->formFields()
            ->where(fn ($q) => $q->where('is_visible', true)
                ->orWhereIn('id', (clone $dataQ)->select('form_field_id')->distinct()))
            ->orderBy('sort_order')->get();

        return view('forms.submissions.show', [
            'submission' => $formSubmission,
            'groupedData' => $groupedData,
            'totalRows' => $totalRows,
            'fields' => $fields,
            'reviewers' => $this->assignableUsers(Auth::user()),
            'canEdit' => FormExcelController::canEditData(Auth::user(), $formSubmission),
            'modes' => FormExcelService::MODES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create_submissions'), 403);

        $validated = $request->validate([
            'form_template_id' => ['required', 'exists:form_templates,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ], [
            'form_template_id.required' => 'النموذج مطلوب.',
        ]);

        $template = FormTemplate::findOrFail($validated['form_template_id']);
        abort_unless(DataScope::canUseTemplate($request->user(), $template), 403, 'هذا النموذج غير متاح لجهتك.');

        $assignee = ($validated['assigned_to'] ?? null) && $this->canManage($request->user()) ? $validated['assigned_to'] : Auth::id();

        $submission = FormSubmission::create([
            'reference_number' => 'KFS-' . strtoupper(Str::random(8)),
            'form_template_id' => $validated['form_template_id'],
            'created_by' => Auth::id(),
            'assigned_to' => $assignee,
            'org_unit_id' => $assignee !== Auth::id() ? User::whereKey($assignee)->value('org_unit_id') : Auth::user()->org_unit_id,
            'status' => 'assigned',
            'current_step' => 1,
        ]);

        WorkflowLog::create([
            'form_submission_id' => $submission->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'from_status' => null,
            'to_status' => $submission->status,
            'notes' => 'إنشاء الإرسال',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $submission->id,
            'event' => 'created',
            'new_values' => ['reference_number' => $submission->reference_number],
            'description' => "إنشاء إرسال: {$submission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route($assignee === Auth::id() ? 'submissions.data-entry' : 'submissions.show', $submission)
            ->with('success', 'تم إنشاء الإرسال بنجاح.');
    }

    /* ------------------------------------------------------------------ authorization helpers */

    private function canManage(User $u): bool
    {
        return $u->can('approve_submissions') || $u->can('review_submissions');
    }

    private function authorizeView(FormSubmission $s): void
    {
        abort_unless(DataScope::canSeeSubmission(Auth::user(), $s), 403, 'هذا الإرسال خارج نطاق جهتك.');
    }

    private function authorizeAction(FormSubmission $s, string $permission): void
    {
        $this->authorizeView($s);
        abort_unless(Auth::user()->can($permission), 403, 'ليست لديك صلاحية تنفيذ هذا الإجراء.');
    }

    /** المستخدمون المتاح إسناد الإرسال إليهم ضمن نطاق المستخدم */
    private function assignableUsers(User $user)
    {
        $ids = DataScope::unitIds($user);
        return User::where('is_active', true)
            ->when($ids !== null, fn ($q) => $q->whereIn('org_unit_id', $ids ?: [0]))
            ->with('orgUnit:id,name')
            ->orderBy('name')->get(['id', 'name', 'org_unit_id']);
    }

    public function assign(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $this->authorizeView($formSubmission);
        abort_unless($this->canManage(Auth::user()), 403, 'ليست لديك صلاحية الإسناد.');

        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ], [
            'assigned_to.required' => 'المستخدم المخصص مطلوب.',
            'assigned_to.exists' => 'المستخدم المحدد غير موجود.',
        ]);

        $oldAssigned = $formSubmission->assigned_to;
        $formSubmission->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'assigned',
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'assigned',
            'from_status' => $formSubmission->getOriginal('status'),
            'to_status' => 'assigned',
            'notes' => "تعيين للمستخدم #" . $validated['assigned_to'],
            'metadata' => ['old_assigned_to' => $oldAssigned, 'new_assigned_to' => $validated['assigned_to']],
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'assigned',
            'old_values' => ['assigned_to' => $oldAssigned],
            'new_values' => ['assigned_to' => $validated['assigned_to']],
            'description' => "تعيين المرفوع: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تم تعيين المرفوع بنجاح.');
    }

    public function submitData(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        abort_unless(FormExcelController::canEditData(Auth::user(), $formSubmission), 403, 'لا يمكن تعديل بيانات هذا الإرسال في حالته الحالية.');

        $request->validate([
            'data' => ['required', 'array'],
        ], [
            'data.required' => 'بيانات النموذج مطلوبة.',
        ]);

        $fields = $formSubmission->formTemplate->formFields()->orderBy('sort_order')->get();

        foreach ($request->input('data') as $fieldId => $value) {
            $field = $fields->firstWhere('id', $fieldId);

            if ($field) {
                FormSubmissionData::updateOrCreate(
                    [
                        'form_submission_id' => $formSubmission->id,
                        'form_field_id' => $fieldId,
                        'row_index' => 0,
                    ],
                    [
                        'value' => $value,
                        'raw_value' => ['original' => $value],
                        'entered_by' => Auth::id(),
                    ]
                );
            }
        }

        $oldStatus = $formSubmission->status;
        $formSubmission->update([
            'status' => 'under_review',
            'submitted_at' => now(),
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'submitted',
            'from_status' => $oldStatus,
            'to_status' => 'under_review',
            'notes' => 'تم تقديم البيانات',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'submitted',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'under_review'],
            'description' => "تقديم المرفوع: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('submissions.show', $formSubmission)
            ->with('success', 'تم تقديم البيانات بنجاح.');
    }

    public function review(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $this->authorizeAction($formSubmission, 'review_submissions');

        $oldStatus = $formSubmission->status;
        $formSubmission->update([
            'status' => 'under_review',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'reviewed',
            'from_status' => $oldStatus,
            'to_status' => 'under_review',
            'notes' => $request->input('notes', 'تمت المراجعة'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'reviewed',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'under_review'],
            'description' => "مراجعة المرفوع: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تمت المراجعة بنجاح.');
    }

    public function approve(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $this->authorizeAction($formSubmission, 'approve_submissions');

        $oldStatus = $formSubmission->status;
        $formSubmission->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'approved',
            'from_status' => $oldStatus,
            'to_status' => 'approved',
            'notes' => $request->input('notes', 'تمت الموافقة'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'approved',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'approved'],
            'description' => "موافقة على المرفوع: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تمت الموافقة على المرفوع.');
    }

    public function reject(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $this->authorizeAction($formSubmission, 'approve_submissions');

        if (! $request->filled('reason') && $request->filled('notes')) {
            $request->merge(['reason' => $request->input('notes')]);
        }
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'سبب الرفض مطلوب.',
        ]);

        $oldStatus = $formSubmission->status;
        $formSubmission->update([
            'status' => 'rejected',
            'revision_notes' => $request->input('reason'),
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'rejected',
            'from_status' => $oldStatus,
            'to_status' => 'rejected',
            'notes' => $request->input('reason'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'rejected',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'rejected', 'revision_notes' => $request->input('reason')],
            'description' => "رفض المرفوع: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تم رفض المرفوع.');
    }

    public function sendBackForRevision(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $this->authorizeView($formSubmission);
        abort_unless($this->canManage(Auth::user()), 403);

        $request->validate([
            'revision_notes' => ['required', 'string', 'max:1000'],
        ], [
            'revision_notes.required' => 'ملاحظات التعديل مطلوبة.',
        ]);

        $oldStatus = $formSubmission->status;
        $formSubmission->update([
            'status' => 'revision_required',
            'revision_notes' => $request->input('revision_notes'),
            'revision_count' => $formSubmission->revision_count + 1,
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'revision_required',
            'from_status' => $oldStatus,
            'to_status' => 'revision_required',
            'notes' => $request->input('revision_notes'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'revision_required',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'revision_required'],
            'description' => "إعادة للتعديل: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تمت الإعادة للتعديل.');
    }

    public function approveAndCreateReport(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $this->authorizeAction($formSubmission, 'approve_submissions');

        $oldStatus = $formSubmission->status;
        $formSubmission->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => Auth::id(),
            'action' => 'approved',
            'from_status' => $oldStatus,
            'to_status' => 'approved',
            'notes' => 'تمت الموافقة وإنشاء التقرير',
        ]);

        $report = Report::create([
            'title' => 'تقرير: ' . $formSubmission->formTemplate->name,
            'slug' => Str::slug('report-' . $formSubmission->reference_number),
            'form_submission_id' => $formSubmission->id,
            'created_by' => Auth::id(),
            'status' => 'draft',
            'current_version' => 1,
            'selected_fields' => $formSubmission->formTemplate->formFields()->pluck('id')->toArray(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormSubmission::class,
            'auditable_id' => $formSubmission->id,
            'event' => 'approved_with_report',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'approved', 'report_id' => $report->id],
            'description' => "موافقة وإنشاء تقرير للمرفوع: {$formSubmission->reference_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.edit', $report)
            ->with('success', 'تمت الموافقة وإنشاء التقرير بنجاح.');
    }

    public function dataEntry(FormSubmission $formSubmission): View|RedirectResponse
    {
        $this->authorizeView($formSubmission);
        if (! FormExcelController::canEditData(Auth::user(), $formSubmission)) {
            return redirect()->route('submissions.show', $formSubmission)
                ->with('warning', 'لا يمكن تعديل بيانات هذا الإرسال في حالته الحالية.');
        }

        $formSubmission->load([
            'formTemplate',
            'submissionData' => function ($query) {
                $query->with('formField');
            },
        ]);

        $fields = $formSubmission->formTemplate->formFields()->orderBy('sort_order')->get();
        $existingData = $formSubmission->submissionData->pluck('value', 'form_field_id')->toArray();

        return view('forms.submissions.data-entry', [
            'submission' => $formSubmission,
            'fields' => $fields,
            'existingData' => $existingData,
            'modes' => FormExcelService::MODES,
        ]);
    }
}
