<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormSubmission;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Report::with(['formSubmission.formTemplate', 'createdBy', 'approvedBy']);

        $user = Auth::user();

        $this->scope($query, $user);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $reports = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'pending_approval' => (clone $query)->where('status', 'pending_approval')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        return view('reports.index', compact('reports', 'stats'));
    }

    public function exportExcel(Request $request)
    {
        $query = Report::with(['formSubmission.formTemplate', 'createdBy']);

        $user = Auth::user();
        $this->scope($query, $user);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $reports = $query->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('التقارير');

        $headers = ['#', 'عنوان التقرير', 'القالب', 'الحالة', 'الإصدار', 'أنشأه', 'تاريخ الإنشاء'];

        foreach ($headers as $col => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        }

        $statusLabels = [
            'draft' => 'مسودة',
            'pending_approval' => 'بانتظار الاعتماد',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'sent' => 'مرسل',
        ];

        foreach ($reports as $index => $report) {
            $row = $index + 2;
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $report->title);
            $sheet->setCellValue('C' . $row, $report->formSubmission->formTemplate->name ?? '-');
            $sheet->setCellValue('D' . $row, $statusLabels[$report->status] ?? $report->status);
            $sheet->setCellValue('E' . $row, 'v' . ($report->current_version ?? 1));
            $sheet->setCellValue('F' . $row, $report->createdBy->name ?? '-');
            $sheet->setCellValue('G' . $row, $report->created_at->format('d/m/Y H:i'));
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'reports_' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $query = Report::with(['formSubmission.formTemplate', 'createdBy']);

        $user = Auth::user();
        $this->scope($query, $user);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $reports = $query->latest()->get();

        $pdf = Pdf::loadView('reports.pdf-index', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to']),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->getDomPDF()->setOptions(new \Dompdf\Options(['isRemoteEnabled' => true]));

        $fileName = 'reports_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    public function create(): View
    {
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية إنشاء التقارير.');
        $submissions = \App\Support\DataScope::submissions(FormSubmission::query(), Auth::user())->with('formTemplate.formFields')
            ->whereIn('status', ['draft', 'data_entry', 'under_review', 'approved'])
            ->latest()
            ->get();

        return view('reports.create', compact('submissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية إنشاء التقارير.');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'form_submission_id' => ['required', 'exists:form_submissions,id'],
            'selected_fields' => ['nullable', 'array'],
            'selected_fields.*' => ['exists:form_fields,id'],
            'report_config' => ['nullable', 'array'],
        ], [
            'title.required' => 'عنوان التقرير مطلوب.',
            'form_submission_id.required' => 'المرفوع مطلوب.',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['created_by'] = Auth::id();
        $validated['status'] = $request->input('action') === 'submit' ? 'pending_approval' : 'draft';
        $validated['current_version'] = 1;

        $report = Report::create($validated);

        ReportVersion::create([
            'report_id' => $report->id,
            'version_number' => 1,
            'content' => $report->content,
            'ai_generated_content' => $report->ai_generated_content,
            'selected_fields' => $validated['selected_fields'],
            'report_config' => $validated['report_config'] ?? [],
            'created_by' => Auth::id(),
            'change_notes' => 'إنشاء التقرير',
            'is_current' => true,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "إنشاء تقرير: {$report->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.edit', $report)
            ->with('success', 'تم إنشاء التقرير بنجاح.');
    }

    public function show(Report $report): View
    {
        $this->authorizeView($report);
        $report->load(['formSubmission' => function ($q) {
            $q->with(['formTemplate', 'submissionData.formField']);
        }, 'createdBy', 'approvedBy', 'versions' => function ($query) {
            $query->with('createdBy')->latest();
        }]);

        $visualizer = new \App\Services\ReportVisualizer();
        $visuals = $visualizer->analyze($report->formSubmission);

        $rows = $visuals['table']['rows'];
        $headers = $visuals['table']['headers'];

        $recipients = Auth::user()->can('send_reports')
            ? User::where('is_active', true)->where('id', '!=', Auth::id())->orderBy('name')->get(['id', 'name', 'job_title'])
            : collect();

        return view('reports.show', compact('report', 'rows', 'headers', 'visuals', 'recipients'));
    }

    public function edit(Report $report): View
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $report->load(['formSubmission' => function ($query) {
            $query->with(['formTemplate', 'submissionData.formField']);
        }]);

        return view('reports.edit', compact('report'));
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'selected_fields' => ['nullable', 'array'],
            'report_config' => ['nullable', 'array'],
        ], [
            'title.required' => 'عنوان التقرير مطلوب.',
        ]);

        $oldValues = $report->toArray();

        $newVersion = $report->current_version + 1;

        $validated['current_version'] = $newVersion;
        // إرسال للاعتماد من شاشة التعديل (مسودة/مرفوض ← بانتظار الاعتماد)
        if ($request->input('action') === 'submit' && in_array($report->status, ['draft', 'rejected'], true)) {
            $validated['status'] = 'pending_approval';
        }
        $report->update($validated);

        $oldVersion = ReportVersion::where('report_id', $report->id)->where('is_current', true)->first();
        if ($oldVersion) {
            $oldVersion->update(['is_current' => false]);
        }

        ReportVersion::create([
            'report_id' => $report->id,
            'version_number' => $newVersion,
            'content' => $report->content,
            'ai_generated_content' => $report->ai_generated_content,
            'selected_fields' => $validated['selected_fields'] ?? $report->selected_fields,
            'report_config' => $validated['report_config'] ?? $report->report_config,
            'created_by' => Auth::id(),
            'change_notes' => $request->input('change_notes', 'تحديث التقرير'),
            'is_current' => true,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'updated',
            'old_values' => ['version' => $oldValues['current_version']],
            'new_values' => ['version' => $newVersion],
            'description' => "تحديث تقرير: {$report->title} إلى الإصدار {$newVersion}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', 'تم تحديث التقرير بنجاح.');
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $title = $report->title;
        $report->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'deleted',
            'old_values' => ['title' => $title],
            'description' => "حذف تقرير: {$title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'تم حذف التقرير بنجاح.');
    }

    public function generateAiContent(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $submission = $report->formSubmission;
        $data = $submission->submissionData->mapWithKeys(function ($item) {
            return [$item->formField->label => $item->value];
        });

        try {
            $response = Http::timeout(120)->post('http://localhost:11434/api/chat', [
                'model' => 'qwen2.5:7b',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد ذكي لكتابة التقارير الحكومية. قم بكتابة تقرير رسمي مفصل بناءً على البيانات المقدمة. استخدم اللغة العربية الفصحى.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "عنوان التقرير: {$report->title}\n\nالبيانات:\n" . $data->implode("\n") . "\n\nقم بكتابة التقرير بشكل رسمي ومفصل.",
                    ],
                ],
                'stream' => false,
            ]);

            $content = $response->json('message.content', 'لا يمكن إنشاء المحتوى في الوقت الحالي.');

            $report->update([
                'ai_generated_content' => $content,
                'is_ai_generated' => true,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => Report::class,
                'auditable_id' => $report->id,
                'event' => 'ai_content_generated',
                'new_values' => ['is_ai_generated' => true],
                'description' => "توليد محتوى بالذكاء الاصطناعي للتقرير: {$report->title}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->with('success', 'تم توليد المحتوى بالذكاء الاصطناعي بنجاح.');
        } catch (\Exception $e) {
            return back()->with('error', 'فشل الاتصال بنموذج الذكاء الاصطناعي. يرجى المحاولة مرة أخرى.');
        }
    }

    public function approveReport(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('approve_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $oldStatus = $report->status;
        $report->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'approved',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'approved'],
            'description' => "موافقة على التقرير: {$report->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تمت الموافقة على التقرير.');
    }

    public function rejectReport(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('approve_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $request->validate([
            'rejection_notes' => ['required', 'string', 'max:1000'],
        ], [
            'rejection_notes.required' => 'سبب الرفض مطلوب.',
        ]);

        $oldStatus = $report->status;
        $report->update([
            'status' => 'rejected',
            'rejection_notes' => $request->input('rejection_notes'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'rejected',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'rejected', 'rejection_notes' => $request->input('rejection_notes')],
            'description' => "رفض التقرير: {$report->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تم رفض التقرير.');
    }

    public function sendReport(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeView($report);
        abort_unless(Auth::user()->can('send_reports'), 403, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        $request->validate([
            'sent_to' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'sent_to.required' => 'المستلم مطلوب.',
            'sent_to.exists' => 'المستخدم المستلم غير موجود.',
        ]);

        $report->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_to' => $request->input('sent_to'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'sent',
            'new_values' => ['sent_to' => $request->input('sent_to')],
            'description' => "إرسال التقرير: {$report->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'تم إرسال التقرير بنجاح.');
    }

    public function showPdf(Report $report)
    {
        $this->authorizeView($report);
        $report->load(['formSubmission' => function ($query) {
            $query->with(['submissionData.formField']);
        }]);

        $submissionData = $report->formSubmission->submissionData ?? collect();
        $rows = $submissionData->groupBy('row_index')->map(function ($group) {
            return $group->mapWithKeys(function ($item) {
                $label = $item->formField->label ?? "field_{$item->form_field_id}";
                return [$label => $item->value ?? '-'];
            });
        })->values();
        $headers = $submissionData->pluck('formField.label')->filter()->unique()->values()->all();

        return view('reports.pdf-show', [
            'report' => $report,
            'rows' => $rows,
            'headers' => $headers,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    public function downloadExcel(Report $report)
    {
        $this->authorizeView($report);
        $report->load(['formSubmission' => function ($query) {
            $query->with(['submissionData.formField']);
        }]);

        $submissionData = $report->formSubmission->submissionData ?? collect();
        $rows = $submissionData->groupBy('row_index')->map(function ($group) {
            return $group->mapWithKeys(function ($item) {
                $label = $item->formField->label ?? "field_{$item->form_field_id}";
                return [$label => $item->value ?? '-'];
            });
        });
        $headers = $submissionData->pluck('formField.label')->filter()->unique()->values()->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($report->title, 0, 31));

        $sheet->setCellValue('A1', '#');
        foreach ($headers as $col => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        }

        foreach ($rows as $rowIndex => $row) {
            $r = $rowIndex + 2;
            $sheet->setCellValue('A' . $r, $rowIndex + 1);
            foreach ($headers as $col => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2);
                $sheet->setCellValue($colLetter . $r, $row[$header] ?? '-');
            }
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) + 1);
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'report_' . $report->id . '_' . Str::slug($report->title) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** يقيد التقارير بنطاق بيانات المستخدم (منشئها/مستلمها أو جهات نطاقه) */
    private function scope($query, User $user)
    {
        $ids = \App\Support\DataScope::unitIds($user);
        if ($ids === null) return $query;

        return $query->where(function ($w) use ($user, $ids) {
            $w->where('reports.created_by', $user->id)->orWhere('reports.sent_to', $user->id);
            if ($ids) $w->orWhereHas('createdBy', fn ($u) => $u->whereIn('org_unit_id', $ids));
        });
    }

    private function authorizeView(Report $report): void
    {
        $visible = $this->scope(Report::whereKey($report->id), Auth::user())->exists();
        abort_unless($visible, 403, 'هذا التقرير خارج نطاق صلاحياتك.');
    }
}
