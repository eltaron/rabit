<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\WorkflowLog;
use App\Services\FormExcelService;
use App\Support\DataScope;
use App\Support\ExcelSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * مركز ملفات Excel: تحميل قالب كل نموذج، تصدير البيانات، وإعادة الرفع للإضافة أو التعديل.
 */
class FormExcelController extends Controller
{
    public function __construct(private FormExcelService $excel)
    {
        // الملفات الكبيرة (آلاف الصفوف) تحتاج وقتاً وذاكرة أكبر
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');
    }

    public function center(Request $request): View
    {
        $user = $request->user();

        $templates = DataScope::templates(FormTemplate::query(), $user)
            ->where('is_active', true)
            ->with('orgUnit')
            ->withCount(['formFields' => fn ($q) => $q->where('is_visible', true)])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->orderBy('name')
            ->paginate(18, ['*'], 'tpage')->withQueryString();

        $submissions = DataScope::submissions(FormSubmission::query(), $user)
            ->with(['formTemplate:id,name', 'orgUnit:id,name'])
            ->withCount(['submissionData as rows_count' => fn ($q) => $q->select(\DB::raw('count(distinct row_index)'))])
            ->latest('updated_at')
            ->limit(12)->get();

        return view('excel.center', [
            'templates' => $templates,
            'submissions' => $submissions,
            'modes' => FormExcelService::MODES,
        ]);
    }

    public function downloadTemplate(Request $request, FormTemplate $formTemplate): StreamedResponse
    {
        abort_unless(DataScope::canUseTemplate($request->user(), $formTemplate), 403, 'هذا النموذج غير متاح لجهتك.');

        $this->audit($request, FormTemplate::class, $formTemplate->id, 'excel_template_downloaded', "تحميل قالب Excel للنموذج: {$formTemplate->name}");

        return ExcelSheet::download($this->excel->blankTemplate($formTemplate), 'قالب - ' . $formTemplate->name);
    }

    public function exportSubmission(Request $request, FormSubmission $formSubmission): StreamedResponse
    {
        abort_unless(DataScope::canSeeSubmission($request->user(), $formSubmission), 403);
        $formSubmission->load('formTemplate');

        $this->audit($request, FormSubmission::class, $formSubmission->id, 'excel_exported', "تصدير بيانات الإرسال {$formSubmission->reference_number} إلى Excel");

        return ExcelSheet::download(
            $this->excel->exportSubmission($formSubmission),
            $formSubmission->formTemplate->name . ' - ' . $formSubmission->reference_number
        );
    }

    public function importSubmission(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        $user = $request->user();
        abort_unless(DataScope::canSeeSubmission($user, $formSubmission), 403);

        if (! self::canEditData($user, $formSubmission)) {
            return back()->with('error', 'لا يمكن تعديل بيانات هذا الإرسال في حالته الحالية. يلزم صلاحية الاعتماد لتعديل بيانات معتمدة أو قيد المراجعة.');
        }

        $data = $this->validateUpload($request, true);
        $formSubmission->load('formTemplate');

        $parsed = $this->excel->parse($request->file('file')->getRealPath(), $formSubmission->formTemplate);
        if ($fail = $this->checkParsed($parsed, $data['skip_invalid'])) {
            return $fail;
        }

        $stats = $this->excel->apply($formSubmission, $parsed['rows'], $data['mode'], $user->id);

        $oldStatus = $formSubmission->status;
        if (in_array($oldStatus, ['draft', 'pending_assignment', 'assigned'], true)) {
            $formSubmission->update(['status' => 'data_entry']);
        } else {
            $formSubmission->touch();
        }

        $summary = $this->summary($stats, $parsed);
        WorkflowLog::create([
            'form_submission_id' => $formSubmission->id,
            'user_id' => $user->id,
            'action' => 'excel_import',
            'from_status' => $oldStatus,
            'to_status' => $formSubmission->status,
            'notes' => 'استيراد من Excel (' . FormExcelService::MODES[$data['mode']] . '): ' . $summary,
            'metadata' => $stats + ['mode' => $data['mode'], 'file' => $request->file('file')->getClientOriginalName()],
        ]);
        $this->audit($request, FormSubmission::class, $formSubmission->id, 'excel_imported', "استيراد Excel للإرسال {$formSubmission->reference_number}: {$summary}", $stats);

        return $this->done($formSubmission, $summary, $parsed);
    }

    public function importNew(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $user = $request->user();
        abort_unless(DataScope::canUseTemplate($user, $formTemplate), 403, 'هذا النموذج غير متاح لجهتك.');
        abort_unless($user->can('create_submissions') || $user->can('submit_data'), 403);

        $data = $this->validateUpload($request, false);
        $parsed = $this->excel->parse($request->file('file')->getRealPath(), $formTemplate);
        if ($fail = $this->checkParsed($parsed, $data['skip_invalid'])) {
            return $fail;
        }

        $submission = \DB::transaction(function () use ($formTemplate, $user, $parsed, $request) {
            $s = FormSubmission::create([
                'reference_number' => 'KFS-' . strtoupper(Str::random(8)),
                'form_template_id' => $formTemplate->id,
                'created_by' => $user->id,
                'assigned_to' => $user->id,
                'org_unit_id' => $user->org_unit_id,
                'status' => 'data_entry',
                'current_step' => 1,
                'metadata' => ['source' => 'excel', 'file' => $request->file('file')->getClientOriginalName()],
            ]);
            $stats = $this->excel->apply($s, $parsed['rows'], 'append', $user->id);

            WorkflowLog::create([
                'form_submission_id' => $s->id,
                'user_id' => $user->id,
                'action' => 'excel_import',
                'from_status' => null,
                'to_status' => 'data_entry',
                'notes' => 'إنشاء إرسال من ملف Excel: ' . $this->summary($stats, $parsed),
                'metadata' => $stats,
            ]);
            return [$s, $stats];
        });
        [$s, $stats] = $submission;

        $this->audit($request, FormSubmission::class, $s->id, 'created_from_excel', "إنشاء إرسال {$s->reference_number} من Excel للنموذج {$formTemplate->name}", $stats);

        return $this->done($s, $this->summary($stats, $parsed), $parsed);
    }

    /* ------------------------------------------------------------------ helpers */

    public static function canEditData($user, FormSubmission $s): bool
    {
        if (! DataScope::canSeeSubmission($user, $s)) return false;
        if ($user->can('approve_submissions')) return true;
        if (! $user->can('submit_data')) return false;
        return in_array($s->status, FormExcelService::EDITABLE_STATUSES, true);
    }

    private function validateUpload(Request $request, bool $withMode): array
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'extensions:xlsx,xls,csv'],
            'mode' => [$withMode ? 'required' : 'nullable', 'in:' . implode(',', array_keys(FormExcelService::MODES))],
        ], [
            'file.required' => 'اختر ملف Excel للرفع.',
            'file.extensions' => 'الملف يجب أن يكون بصيغة xlsx أو xls أو csv.',
            'file.max' => 'أقصى حجم للملف 20 ميجابايت.',
            'mode.required' => 'حدد طريقة الاستيراد.',
            'mode.in' => 'طريقة الاستيراد غير صحيحة.',
        ]);
        $data['mode'] ??= 'append';
        $data['skip_invalid'] = $request->boolean('skip_invalid');
        return $data;
    }

    private function checkParsed(array $parsed, bool $skipInvalid): ?RedirectResponse
    {
        if ($parsed['matched'] === 0) {
            return back()->with('error', 'لم يتم التعرف على أي عمود من أعمدة النموذج في الملف. استخدم القالب المحمّل من النظام دون تعديل صف العناوين.');
        }
        if ($parsed['missing']) {
            return back()->with('import_errors', array_slice($parsed['errors'], 0, 300))
                ->with('error', 'الملف ينقصه أعمدة إلزامية: ' . implode('، ', $parsed['missing']));
        }
        if ($parsed['errors'] && ! $skipInvalid) {
            return back()->with('import_errors', array_slice($parsed['errors'], 0, 300))
                ->with('error', "لم يتم حفظ أي بيانات: يوجد {$parsed['invalid_rows']} صف به أخطاء من أصل {$parsed['total']}. صحّح الأخطاء الموضحة وأعد الرفع، أو فعّل خيار «تجاهل الصفوف غير الصحيحة».");
        }
        if (! $parsed['rows']) {
            return back()->with('import_errors', array_slice($parsed['errors'], 0, 300))
                ->with('error', 'لا توجد صفوف صحيحة في الملف لحفظها.');
        }
        return null;
    }

    private function summary(array $stats, array $parsed): string
    {
        $parts = [];
        if ($stats['added']) $parts[] = "إضافة {$stats['added']} صف";
        if ($stats['updated']) $parts[] = "تحديث {$stats['updated']} صف";
        if ($stats['removed']) $parts[] = "استبدال {$stats['removed']} صف سابق";
        if ($parsed['invalid_rows']) $parts[] = "تجاهل {$parsed['invalid_rows']} صف غير صحيح";
        return $parts ? implode('، ', $parts) : 'لا تغييرات';
    }

    private function done(FormSubmission $s, string $summary, array $parsed): RedirectResponse
    {
        $r = redirect()->route('submissions.show', $s)->with('success', 'تم استيراد ملف Excel بنجاح: ' . $summary . '.');
        if ($parsed['errors']) {
            $r->with('import_errors', array_slice($parsed['errors'], 0, 300))
              ->with('warning', 'تم تجاهل الصفوف التالية لاحتوائها على أخطاء.');
        }
        return $r;
    }

    private function audit(Request $request, string $type, int $id, string $event, string $desc, array $new = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'event' => $event,
            'new_values' => $new ?: null,
            'description' => $desc,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
