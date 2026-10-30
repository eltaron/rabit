<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use App\Models\ReportVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportVersionController extends Controller
{
    public function index(Request $request, Report $report): View
    {
        $this->authorizeView($report);
        $report->load(['formSubmission' => function ($q) {
            $q->with(['formTemplate', 'submissionData.formField']);
        }]);

        $submissionData = $report->formSubmission->submissionData ?? collect();
        $rows = $submissionData->groupBy('row_index')->map(function ($group) {
            return $group->mapWithKeys(function ($item) {
                $label = $item->formField->label ?? "field_{$item->form_field_id}";
                return [$label => $item->value ?? '-'];
            });
        })->values();
        $headers = $submissionData->pluck('formField.label')->filter()->unique()->values()->all();

        $versions = ReportVersion::where('report_id', $report->id)
            ->with('createdBy')
            ->orderByDesc('version_number')
            ->paginate(15);

        return view('reports.versions', compact('report', 'versions', 'rows', 'headers'));
    }

    public function show(Report $report, ReportVersion $version): \Illuminate\Http\JsonResponse
    {
        $this->authorizeView($report);
        abort_unless($version->report_id === $report->id, 404);
        $version->load('createdBy');

        return response()->json([
            'id' => $version->id,
            'version_number' => $version->version_number,
            'content' => $version->content ?? '',
            'ai_generated_content' => $version->ai_generated_content ?? '',
            'change_notes' => $version->change_notes ?? '',
            'selected_fields' => $version->selected_fields ?? [],
            'created_by' => $version->createdBy->name ?? '',
            'created_at' => $version->created_at->format('d/m/Y H:i'),
            'is_current' => $version->is_current,
        ]);
    }

    public function compare(Request $request, Report $report): View
    {
        $request->validate([
            'version_a' => ['required', 'exists:report_versions,id'],
            'version_b' => ['required', 'exists:report_versions,id'],
        ]);

        $versionA = ReportVersion::where('id', $request->input('version_a'))
            ->where('report_id', $report->id)
            ->firstOrFail();

        $versionB = ReportVersion::where('id', $request->input('version_b'))
            ->where('report_id', $report->id)
            ->firstOrFail();

        // نفس شاشة السجل مع لوحة مقارنة بين الإصدارين
        $view = $this->index($request, $report);

        return $view->with(compact('versionA', 'versionB'));
    }

    public function revert(Request $request, Report $report, ReportVersion $version): RedirectResponse
    {
        abort_unless(Auth::user()->can('manage_reports'), 403, 'لا تملك صلاحية استرجاع الإصدارات.');
        $oldVersion = $report->current_version;

        $report->update([
            'content' => $version->content,
            'ai_generated_content' => $version->ai_generated_content,
            'selected_fields' => $version->selected_fields,
            'report_config' => $version->report_config,
            'current_version' => $oldVersion + 1,
        ]);

        $newVersion = ReportVersion::create([
            'report_id' => $report->id,
            'version_number' => $oldVersion + 1,
            'content' => $version->content,
            'ai_generated_content' => $version->ai_generated_content,
            'selected_fields' => $version->selected_fields,
            'report_config' => $version->report_config,
            'created_by' => Auth::id(),
            'change_notes' => "استعادة من الإصدار {$version->version_number}",
            'is_current' => true,
        ]);

        ReportVersion::where('report_id', $report->id)
            ->where('is_current', true)
            ->where('id', '!=', $newVersion->id)
            ->update(['is_current' => false]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'version_reverted',
            'old_values' => ['version' => $oldVersion],
            'new_values' => ['version' => $report->current_version, 'reverted_to' => $version->version_number],
            'description' => "استعادة التقرير: {$report->title} إلى الإصدار {$version->version_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', "تمت الاستعادة إلى الإصدار {$version->version_number} بنجاح.");
    }

    private function authorizeView(Report $report): void
    {
        $user = Auth::user();
        $ids = \App\Support\DataScope::unitIds($user);
        if ($ids === null) return;
        $ok = $report->created_by === $user->id || $report->sent_to === $user->id
            || in_array($report->createdBy?->org_unit_id, $ids, true);
        abort_unless($ok, 403, 'هذا التقرير خارج نطاق صلاحياتك.');
    }
}
