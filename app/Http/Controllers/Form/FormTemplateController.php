<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\OrgUnit;
use App\Support\DataScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * النماذج + تداولها بين الجهات (الجهة المالكة، الجهات الموجه إليها، ربط الإحداثيات).
 */
class FormTemplateController extends Controller
{
    public const DISTRIBUTIONS = [
        'all' => 'متاح لكل جهات المحافظة',
        'targeted' => 'موجه لجهات محددة',
        'owner' => 'للجهة المالكة والتابعين لها فقط',
    ];

    public const FIELD_TYPES = [
        'text' => 'نص', 'textarea' => 'نص طويل', 'number' => 'رقم', 'email' => 'بريد إلكتروني',
        'phone' => 'هاتف', 'national_id' => 'رقم قومي', 'date' => 'تاريخ', 'select' => 'قائمة منسدلة',
        'multiselect' => 'اختيار متعدد', 'radio' => 'زر اختيار', 'checkbox' => 'مربع اختيار',
        'file' => 'ملف', 'image' => 'صورة', 'latitude' => 'خط العرض', 'longitude' => 'خط الطول',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $this->scoped($user)
            ->with(['createdBy:id,name', 'orgUnit:id,name'])
            ->withCount(['formFields', 'formSubmissions', 'targetUnits']);

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('distribution')) {
            $query->where('distribution', $request->input('distribution'));
        }
        if ($unit = $request->integer('org_unit_id')) {
            $query->whereIn('org_unit_id', OrgUnit::find($unit)?->descendantIds()->all() ?? [0]);
        }

        $templates = $query->latest()->paginate(18)->withQueryString();
        $view = $request->input('view', 'grid') === 'table' ? 'table' : 'grid';

        $stats = [
            'total' => $this->scoped($user)->count(),
            'active' => $this->scoped($user)->where('is_active', true)->count(),
            'targeted' => $this->scoped($user)->where('distribution', 'targeted')->count(),
            'submissions' => DataScope::submissions(FormSubmission::query(), $user)->count(),
        ];

        return view('forms.templates.index', [
            'templates' => $templates,
            'view' => $view,
            'stats' => $stats,
            'units' => OrgUnit::treeOptions(),
            'distributions' => self::DISTRIBUTIONS,
        ]);
    }

    public function create(): View
    {
        return view('forms.templates.create', $this->formData(new FormTemplate([
            'is_active' => true,
            'requires_approval' => true,
            'distribution' => 'all',
            'org_unit_id' => Auth::user()->org_unit_id,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['created_by'] = Auth::id();

        $template = DB::transaction(function () use ($validated, $request) {
            $template = FormTemplate::create($validated);
            $this->syncFields($template, $request->input('fields'));
            $this->syncTargets($template, $request);
            return $template;
        });

        $this->audit($request, $template->id, 'created', "إنشاء نموذج: {$template->name}", [], $validated);

        return redirect()->route('forms.templates.show', $template)
            ->with('success', 'تم إنشاء النموذج بنجاح. يمكنك الآن تحميل قالب Excel الخاص به أو توجيهه للجهات.');
    }

    public function show(Request $request, FormTemplate $formTemplate): View
    {
        $this->authorizeTemplate($formTemplate);

        $formTemplate->load([
            'formFields' => fn ($q) => $q->orderBy('sort_order'),
            'createdBy', 'orgUnit', 'targetUnits',
        ]);

        $subs = DataScope::submissions(FormSubmission::where('form_template_id', $formTemplate->id), $request->user());
        $statusCounts = (clone $subs)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        // متابعة الاستجابة لكل جهة موجه إليها النموذج
        $coverage = $formTemplate->targetUnits->map(function (OrgUnit $unit) use ($formTemplate) {
            $ids = $unit->pivot->include_children ? $unit->descendantIds()->all() : [$unit->id];
            $q = FormSubmission::where('form_template_id', $formTemplate->id)->whereIn('org_unit_id', $ids);
            $total = (clone $q)->count();
            $submitted = (clone $q)->whereNotIn('status', ['draft', 'pending_assignment', 'assigned', 'data_entry'])->count();
            $approved = (clone $q)->whereIn('status', ['approved', 'report_pending', 'report_draft', 'report_approved', 'report_sent'])->count();
            $due = $unit->pivot->due_date ? \Carbon\Carbon::parse($unit->pivot->due_date) : null;
            return [
                'unit' => $unit,
                'include_children' => (bool) $unit->pivot->include_children,
                'due' => $due,
                'overdue' => $due && $due->isPast() && $approved === 0,
                'total' => $total,
                'submitted' => $submitted,
                'approved' => $approved,
                'state' => $approved ? 'success' : ($submitted ? 'info' : ($total ? 'pending' : 'error')),
            ];
        });

        $recent = (clone $subs)->with(['createdBy:id,name', 'orgUnit:id,name'])->latest()->limit(8)->get();

        return view('forms.templates.show', [
            'template' => $formTemplate,
            'statusCounts' => $statusCounts,
            'coverage' => $coverage,
            'recent' => $recent,
            'totalSubmissions' => $statusCounts->sum(),
            'distributions' => self::DISTRIBUTIONS,
            'fieldTypes' => self::FIELD_TYPES,
            'modes' => \App\Services\FormExcelService::MODES,
        ]);
    }

    public function edit(FormTemplate $formTemplate): View
    {
        $this->authorizeTemplate($formTemplate);
        $formTemplate->load('targetUnits', 'formFields');

        return view('forms.templates.edit', $this->formData($formTemplate));
    }

    public function update(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $this->authorizeTemplate($formTemplate);
        $validated = $this->validated($request, $formTemplate);
        $old = $formTemplate->only(array_keys($validated));

        DB::transaction(function () use ($formTemplate, $validated, $request) {
            $formTemplate->update($validated);
            if ($request->has('fields')) {
                $this->syncFields($formTemplate, $request->input('fields'));
            }
            $this->syncTargets($formTemplate, $request);
        });

        $this->audit($request, $formTemplate->id, 'updated', "تحديث نموذج: {$formTemplate->name}", $old, $validated);

        return redirect()->route('forms.templates.show', $formTemplate)
            ->with('success', 'تم تحديث النموذج بنجاح.');
    }

    public function destroy(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $this->authorizeTemplate($formTemplate);

        if ($formTemplate->formSubmissions()->exists()) {
            return back()->with('error', 'لا يمكن حذف النموذج لارتباطه بإرسالات. يمكنك إيقاف تفعيله بدلاً من ذلك.');
        }

        $name = $formTemplate->name;
        $formTemplate->delete();
        $this->audit($request, $formTemplate->id, 'deleted', "حذف نموذج: {$name}", ['name' => $name]);

        return redirect()->route('forms.templates.index')->with('success', 'تم حذف النموذج بنجاح.');
    }

    public function duplicate(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $this->authorizeTemplate($formTemplate);

        $newTemplate = DB::transaction(function () use ($formTemplate) {
            $new = $formTemplate->replicate(['slug']);
            $new->name = $formTemplate->name . ' - نسخة';
            $new->slug = null;
            $new->created_by = Auth::id();
            $new->is_active = false;
            $new->save();

            foreach ($formTemplate->formFields as $field) {
                $f = $field->replicate();
                $f->form_template_id = $new->id;
                $f->save();
            }
            $new->targetUnits()->sync($formTemplate->targetUnits->mapWithKeys(fn ($u) => [
                $u->id => ['include_children' => $u->pivot->include_children, 'due_date' => $u->pivot->due_date],
            ])->all());
            return $new;
        });

        $this->audit($request, $newTemplate->id, 'duplicated', "نسخ نموذج: {$formTemplate->name}", [], ['from_template_id' => $formTemplate->id]);

        return redirect()->route('forms.templates.edit', $newTemplate)->with('success', 'تم نسخ النموذج بنجاح (غير مفعل حتى تراجعه).');
    }

    /* ------------------------------------------------------------------ helpers */

    /** النماذج التي يديرها المستخدم: الكل لنطاق المحافظة، وإلا نماذج جهته/التابعين أو التي أنشأها */
    private function scoped($user)
    {
        $q = FormTemplate::query();
        $ids = DataScope::unitIds($user);
        if ($ids === null) return $q;

        return $q->where(fn ($w) => $w->where('created_by', $user->id)
            ->orWhereIn('org_unit_id', $ids ?: [0])
            ->orWhere(fn ($x) => DataScope::templates($x, $user)));
    }

    private function authorizeTemplate(FormTemplate $t): void
    {
        abort_unless($this->scoped(Auth::user())->whereKey($t->id)->exists(), 403, 'هذا النموذج خارج نطاق جهتك.');
    }

    private function formData(FormTemplate $template): array
    {
        $template->loadMissing('targetUnits', 'formFields');
        return [
            'template' => $template,
            'units' => OrgUnit::treeOptions(),
            'distributions' => self::DISTRIBUTIONS,
            'targets' => $template->targetUnits->mapWithKeys(fn ($u) => [$u->id => [
                'include_children' => (bool) $u->pivot->include_children,
                'due_date' => $u->pivot->due_date,
            ]])->all(),
            'fieldNames' => $template->formFields->sortBy('sort_order')->mapWithKeys(fn ($f) => [$f->name => $f->label ?: $f->name])->all(),
        ];
    }

    private function validated(Request $request, ?FormTemplate $t = null): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'org_unit_id' => ['nullable', 'exists:org_units,id'],
            'distribution' => ['required', 'in:' . implode(',', array_keys(self::DISTRIBUTIONS))],
            'targets' => ['nullable', 'array'],
            'targets.*' => ['integer', 'exists:org_units,id'],
            'target_children' => ['nullable', 'array'],
            'target_due' => ['nullable', 'array'],
            'target_due.*' => ['nullable', 'date'],
            'geo_lat_field' => ['nullable', 'string', 'max:255'],
            'geo_lng_field' => ['nullable', 'string', 'max:255'],
            'fields' => ['nullable', 'string'],
        ], [
            'name.required' => 'اسم النموذج مطلوب.',
            'distribution.required' => 'حدد طريقة تداول النموذج.',
            'targets.*.exists' => 'إحدى الجهات المحددة غير موجودة.',
        ]);

        if ($v['distribution'] === 'targeted' && empty($v['targets'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['targets' => 'اختر جهة واحدة على الأقل لتوجيه النموذج إليها.']);
        }

        return [
            'name' => $v['name'],
            'description' => $v['description'] ?? null,
            'org_unit_id' => $v['org_unit_id'] ?? null,
            'distribution' => $v['distribution'],
            'geo_lat_field' => $v['geo_lat_field'] ?? null,
            'geo_lng_field' => $v['geo_lng_field'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'requires_approval' => $request->boolean('requires_approval'),
        ];
    }

    private function syncTargets(FormTemplate $template, Request $request): void
    {
        if ($template->distribution !== 'targeted') {
            $template->targetUnits()->detach();
            return;
        }
        $children = (array) $request->input('target_children', []);
        $due = (array) $request->input('target_due', []);
        $template->targetUnits()->sync(collect((array) $request->input('targets', []))->unique()->mapWithKeys(fn ($id) => [
            (int) $id => [
                'include_children' => array_key_exists($id, $children) ? (bool) $children[$id] : true,
                'due_date' => $due[$id] ?? null ?: null,
            ],
        ])->all());
    }

    /** يحفظ حقول منشئ النماذج (JSON من مكون FormBuilder) */
    private function syncFields(FormTemplate $template, ?string $json): void
    {
        if ($json === null) return;
        $fields = json_decode($json, true);
        if (! is_array($fields)) return;

        $keep = [];
        $used = [];
        foreach (array_values($fields) as $i => $f) {
            $label = trim((string) ($f['label'] ?? ''));
            if ($label === '') continue;

            $type = array_key_exists($f['type'] ?? 'text', self::FIELD_TYPES) ? $f['type'] : 'text';
            $name = Str::slug((string) ($f['name'] ?? ''), '_') ?: 'field_' . ($i + 1);
            while (in_array($name, $used, true)) $name .= '_' . ($i + 1);
            $used[] = $name;

            $options = null;
            if (in_array($type, ['select', 'multiselect', 'radio', 'checkbox'], true)) {
                $text = $f['options_text'] ?? (is_array($f['options'] ?? null) ? implode("\n", $f['options']) : '');
                $options = array_values(array_filter(array_map('trim', preg_split('/\r?\n|،|,/u', (string) $text)), fn ($o) => $o !== ''));
                $options = $options ?: null;
            }

            $data = [
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'options' => $options,
                'validation_rules' => ! empty($f['accepted_types']) ? ['accepted_types' => $f['accepted_types']] : ($f['validation_rules'] ?? null),
                'is_required' => (bool) ($f['is_required'] ?? false),
                'is_readonly' => (bool) ($f['is_readonly'] ?? false),
                'is_visible' => (bool) ($f['is_visible'] ?? true),
                'sort_order' => $i,
                'placeholder' => $f['placeholder'] ?? null,
                'help_text' => $f['help_text'] ?? null,
                'default_value' => $f['default_value'] ?? null,
            ];

            $existing = ! empty($f['id']) ? FormField::where('form_template_id', $template->id)->find($f['id']) : null;
            if ($existing) {
                $existing->update($data);
                $keep[] = $existing->id;
            } else {
                $keep[] = $template->formFields()->create($data)->id;
            }
        }

        // حذف الحقول المحذوفة من المنشئ فقط إذا لم تكن بها بيانات
        $template->formFields()->whereNotIn('id', $keep ?: [0])
            ->whereDoesntHave('submissionData')->delete();
        $template->formFields()->whereNotIn('id', $keep ?: [0])->update(['is_visible' => false]);
    }

    private function audit(Request $request, int $id, string $event, string $desc, array $old = [], array $new = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormTemplate::class,
            'auditable_id' => $id,
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'description' => $desc,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
