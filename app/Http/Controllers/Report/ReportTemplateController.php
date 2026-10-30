<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $query = Report::with(['createdBy', 'formSubmission.formTemplate']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $reports = $query->latest()->paginate(15)->withQueryString();

        return view('reports.index', compact('reports'));
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'report_config' => ['required', 'array'],
            'report_config.template_type' => ['required', 'string'],
            'report_config.layout' => ['nullable', 'string'],
            'report_config.orientation' => ['nullable', 'string', 'in:portrait,landscape'],
            'report_config.font_size' => ['nullable', 'integer', 'min:8', 'max:24'],
            'report_config.header' => ['nullable', 'string'],
            'report_config.footer' => ['nullable', 'string'],
            'report_config.logo' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
        ], [
            'title.required' => 'عنوان القالب مطلوب.',
            'report_config.required' => 'إعدادات القالب مطلوبة.',
            'report_config.template_type.required' => 'نوع القالب مطلوب.',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'template';
        $validated['current_version'] = 1;

        $report = Report::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "إنشاء قالب تقرير: {$report->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'تم إنشاء قالب التقرير بنجاح.');
    }

    public function show(Report $report): View
    {
        return view('reports.show', ['template' => $report]);
    }

    public function edit(Report $report): View
    {
        return view('reports.edit', ['template' => $report]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'report_config' => ['required', 'array'],
            'content' => ['nullable', 'string'],
        ], [
            'title.required' => 'عنوان القالب مطلوب.',
            'report_config.required' => 'إعدادات القالب مطلوبة.',
        ]);

        $oldValues = $report->toArray();
        $report->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "تحديث قالب تقرير: {$report->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'تم تحديث قالب التقرير بنجاح.');
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        $title = $report->title;
        $report->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'event' => 'deleted',
            'old_values' => ['title' => $title],
            'description' => "حذف قالب تقرير: {$title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'تم حذف قالب التقرير بنجاح.');
    }
}
