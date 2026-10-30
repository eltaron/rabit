<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\WritesAudit;
use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Models\OrgUnit;
use App\Models\User;
use App\Services\OrgStructureService;
use App\Support\DataScope;
use App\Support\ExcelSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * الهيكل الوظيفي: وظائف كل جهة وتبعيتها الوظيفية وشاغلوها.
 */
class JobPositionController extends Controller
{
    use WritesAudit;

    /** الجهات المسموح بإدارتها (حسب نطاق المستخدم) — بدون العزب */
    private function allowedUnitIds(Request $request): ?array
    {
        return DataScope::unitIds($request->user());
    }

    private function authorizeUnit(Request $request, int $unitId): void
    {
        $ids = $this->allowedUnitIds($request);
        abort_if($ids !== null && ! in_array($unitId, $ids, true), 403, 'هذه الجهة خارج نطاق صلاحياتك.');
    }

    public function index(Request $request): View
    {
        $allowed = $this->allowedUnitIds($request);
        $root = OrgUnit::root();
        $unitId = $request->integer('unit') ?: ($allowed === null ? ($root?->id) : ($request->user()->org_unit_id ?: ($allowed[0] ?? 0)));
        $unit = OrgUnit::findOrFail($unitId);
        $this->authorizeUnit($request, $unit->id);

        $positions = JobPosition::where('org_unit_id', $unit->id)
            ->with(['holder:id,name,email', 'parent:id,title,org_unit_id', 'parent.orgUnit:id,name'])
            ->withCount('occupants')
            ->orderBy('sort_order')->orderBy('id')->get();

        // بناء شجرة الوظائف داخل الجهة
        $ids = $positions->pluck('id')->all();
        $byParent = $positions->groupBy(fn ($p) => in_array($p->parent_id, $ids, true) ? $p->parent_id : 0);

        // قائمة الجهات الجانبية (بدون العزب) مع عدد الوظائف
        $positionCounts = JobPosition::selectRaw('org_unit_id, count(*) c, sum(user_id is null) v')->groupBy('org_unit_id')->get()->keyBy('org_unit_id');
        $unitsQuery = OrgUnit::whereNotIn('type', ['hamlet', 'village'])->orderBy('path');
        if ($allowed !== null) $unitsQuery->whereIn('id', $allowed ?: [0]);
        $unitOptions = OrgUnit::treeOptions($unitsQuery->pluck('id'));

        // المرشحون للتسكين: مستخدمو الجهة وفروعها أولاً
        $subtreeIds = $unit->descendantIds();
        $candidates = User::where('is_active', true)
            ->when($allowed !== null, fn ($q) => $q->whereIn('org_unit_id', $allowed ?: [0]))
            ->orderByRaw('org_unit_id in (' . ($subtreeIds->implode(',') ?: '0') . ') desc')
            ->orderBy('name')->limit(500)->get(['id', 'name', 'org_unit_id', 'job_position_id']);

        // الوظائف التي يمكن التبعية لها: وظائف الجهة + وظائف الجهات الأعلى
        $parentChoices = JobPosition::whereIn('org_unit_id', $unit->ancestorIds())
            ->with('orgUnit:id,name')->orderBy('org_unit_id')->orderBy('sort_order')->get();

        $stats = [
            'total' => $positions->count(),
            'filled' => $positions->whereNotNull('user_id')->count(),
            'vacant' => $positions->whereNull('user_id')->count(),
            'headcount' => $positions->sum('headcount'),
            'leadership' => $positions->where('category', 'leadership')->count(),
        ];

        return view('admin.positions.index', compact(
            'unit', 'positions', 'byParent', 'unitOptions', 'positionCounts', 'candidates', 'parentChoices', 'stats'
        ) + ['ancestors' => $unit->ancestors()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->authorizeUnit($request, (int) $data['org_unit_id']);
        $position = JobPosition::create($data);
        if (! empty($data['user_id'])) $this->syncHolder($position, (int) $data['user_id'], $request->boolean('move_user'));
        $this->audit('created', $position, "إنشاء وظيفة: {$position->title}", null, $data);

        return redirect()->route('admin.positions.index', ['unit' => $position->org_unit_id])->with('success', 'تمت إضافة الوظيفة.');
    }

    public function update(Request $request, JobPosition $position): RedirectResponse
    {
        $this->authorizeUnit($request, $position->org_unit_id);
        $data = $this->validated($request, $position);
        $this->authorizeUnit($request, (int) $data['org_unit_id']);
        if (! empty($data['parent_id']) && $this->isDescendant($position, (int) $data['parent_id'])) {
            return back()->with('error', 'لا يمكن أن تتبع الوظيفة إحدى الوظائف التابعة لها.');
        }
        $old = $position->only(array_keys($data));
        $holder = $data['user_id'] ?? null;
        unset($data['user_id']);
        $position->update($data);
        $this->syncHolder($position, $holder ? (int) $holder : null, $request->boolean('move_user'));
        $this->audit('updated', $position, "تحديث وظيفة: {$position->title}", $old, $data);

        return redirect()->route('admin.positions.index', ['unit' => $position->org_unit_id])->with('success', 'تم تحديث الوظيفة.');
    }

    public function assign(Request $request, JobPosition $position): RedirectResponse
    {
        $this->authorizeUnit($request, $position->org_unit_id);
        $request->validate(['user_id' => ['nullable', 'exists:users,id']]);
        $old = $position->user_id;
        $this->syncHolder($position, $request->integer('user_id') ?: null, $request->boolean('move_user', true));
        $this->audit('assigned', $position, "تسكين وظيفة: {$position->title}", ['user_id' => $old], ['user_id' => $position->user_id]);

        return back()->with('success', $position->user_id ? 'تم تسكين الوظيفة.' : 'تم إخلاء الوظيفة.');
    }

    public function destroy(Request $request, JobPosition $position): RedirectResponse
    {
        $this->authorizeUnit($request, $position->org_unit_id);
        $unitId = $position->org_unit_id;
        DB::transaction(function () use ($position) {
            // الوظائف التابعة تنتقل لرئيس الوظيفة المحذوفة
            JobPosition::where('parent_id', $position->id)->update(['parent_id' => $position->parent_id]);
            User::where('job_position_id', $position->id)->update(['job_position_id' => null]);
            $position->delete();
        });
        $this->audit('deleted', $position, "حذف وظيفة: {$position->title}", ['title' => $position->title]);

        return redirect()->route('admin.positions.index', ['unit' => $unitId])->with('success', 'تم حذف الوظيفة ونقل الوظائف التابعة لها إلى المستوى الأعلى.');
    }

    /** يحافظ على الاتساق بين positions.user_id و users.job_position_id */
    private function syncHolder(JobPosition $position, ?int $userId, bool $moveUser = true): void
    {
        DB::transaction(function () use ($position, $userId, $moveUser) {
            if ($position->user_id && $position->user_id !== $userId) {
                User::whereKey($position->user_id)->where('job_position_id', $position->id)->update(['job_position_id' => null]);
            }
            if ($userId) {
                // المستخدم يشغل وظيفة واحدة رئيسية
                JobPosition::where('user_id', $userId)->where('id', '!=', $position->id)->update(['user_id' => null]);
                $user = User::find($userId);
                $updates = ['job_position_id' => $position->id, 'job_title' => $position->title];
                if ($moveUser) $updates['org_unit_id'] = $position->org_unit_id;
                $user->update($updates);
                DataScope::forget($user);
            }
            $position->update(['user_id' => $userId]);
        });
    }

    private function isDescendant(JobPosition $position, int $candidateId): bool
    {
        if ($candidateId === $position->id) return true;
        $seen = [];
        $id = $candidateId;
        while ($id && ! isset($seen[$id])) {
            $seen[$id] = true;
            $id = JobPosition::whereKey($id)->value('parent_id');
            if ($id === $position->id) return true;
        }
        return false;
    }

    private function validated(Request $request, ?JobPosition $position = null): array
    {
        $data = $request->validate([
            'org_unit_id' => ['required', 'exists:org_units,id'],
            'parent_id' => ['nullable', 'exists:job_positions,id'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('job_positions', 'code')->ignore($position?->id)],
            'grade' => ['nullable', 'string', 'max:60'],
            'category' => ['required', Rule::in(array_keys(JobPosition::CATEGORIES))],
            'user_id' => ['nullable', 'exists:users,id'],
            'headcount' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer'],
        ], [
            'title.required' => 'المسمى الوظيفي مطلوب.',
            'code.unique' => 'كود الوظيفة مستخدم.',
        ]);
        $data['headcount'] = $data['headcount'] ?? 1;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /* ------------------------------------------------------------------ Excel */

    private function columns(): array
    {
        return [
            ['key' => 'id', 'label' => 'رقم الوظيفة (للتعديل)', 'readonly' => true, 'width' => 14],
            ['key' => 'title', 'label' => 'المسمى الوظيفي', 'required' => true, 'width' => 30],
            ['key' => 'code', 'label' => 'كود الوظيفة', 'width' => 14],
            ['key' => 'unit_code', 'label' => 'كود الجهة', 'width' => 14, 'note' => 'يكفي الكود أو اسم الجهة'],
            ['key' => 'unit_name', 'label' => 'اسم الجهة', 'width' => 30],
            ['key' => 'parent_code', 'label' => 'كود الوظيفة الأعلى', 'width' => 16],
            ['key' => 'parent_title', 'label' => 'الوظيفة الأعلى (المسمى)', 'width' => 26],
            ['key' => 'grade', 'label' => 'الدرجة', 'options' => JobPosition::GRADES, 'width' => 12],
            ['key' => 'category', 'label' => 'الفئة', 'options' => array_values(JobPosition::CATEGORIES), 'width' => 12],
            ['key' => 'headcount', 'label' => 'عدد الدرجات', 'width' => 10],
            ['key' => 'holder', 'label' => 'الشاغل (البريد أو الرقم القومي)', 'width' => 28],
            ['key' => 'description', 'label' => 'الوصف / المهام', 'width' => 36],
        ];
    }

    public function template()
    {
        return ExcelSheet::download(ExcelSheet::build('الهيكل الوظيفي — قالب', $this->columns(), [],
            'كل صف وظيفة. حدد الجهة بالكود أو الاسم، والوظيفة الأعلى بالكود أو المسمى داخل نفس الجهة أو جهة أعلى. الشاغل بالبريد أو الرقم القومي.'), 'قالب-الهيكل-الوظيفي.xlsx');
    }

    public function export(Request $request)
    {
        $allowed = $this->allowedUnitIds($request);
        $q = JobPosition::with(['orgUnit:id,name,code', 'parent:id,title,code', 'holder:id,email,national_id'])->orderBy('org_unit_id')->orderBy('sort_order');
        if ($u = $request->integer('unit')) {
            $this->authorizeUnit($request, $u);
            $q->whereIn('org_unit_id', OrgUnit::findOrFail($u)->descendantIds());
        } elseif ($allowed !== null) {
            $q->whereIn('org_unit_id', $allowed ?: [0]);
        }
        $rows = $q->get()->map(fn ($p) => [
            'id' => $p->id, 'title' => $p->title, 'code' => $p->code,
            'unit_code' => $p->orgUnit?->code, 'unit_name' => $p->orgUnit?->name,
            'parent_code' => $p->parent?->code, 'parent_title' => $p->parent?->title,
            'grade' => $p->grade, 'category' => $p->category_label, 'headcount' => $p->headcount,
            'holder' => $p->holder?->email ?? $p->holder?->national_id, 'description' => $p->description,
        ])->all();

        return ExcelSheet::download(ExcelSheet::build('الهيكل الوظيفي', $this->columns(), $rows), 'الهيكل-الوظيفي-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480']], ['file.required' => 'اختر ملف Excel.']);
        $data = ExcelSheet::read($request->file('file')->getRealPath(), $this->columns());
        $catByLabel = array_flip(JobPosition::CATEGORIES);
        $allowed = $this->allowedUnitIds($request);
        $created = $updated = 0;
        $errors = [];
        $deferredParents = [];

        DB::transaction(function () use ($data, $catByLabel, $allowed, &$created, &$updated, &$errors, &$deferredParents) {
            foreach ($data['rows'] as $row) {
                $line = $row['__row'];
                $title = trim((string) ($row['title'] ?? ''));
                if ($title === '') { $errors[] = "صف {$line}: المسمى مطلوب."; continue; }

                $unitId = null;
                if (! empty($row['unit_code'])) $unitId = OrgUnit::where('code', (string) $row['unit_code'])->value('id');
                if (! $unitId && ! empty($row['unit_name'])) {
                    $n = OrgStructureService::norm($row['unit_name']);
                    $unitId = OrgUnit::where('name', 'like', '%' . mb_substr(trim((string) $row['unit_name']), 0, 25) . '%')->get(['id', 'name'])->first(fn ($u) => OrgStructureService::norm($u->name) === $n)?->id;
                }
                $existing = ! empty($row['id']) ? JobPosition::find((int) $row['id']) : null;
                if (! empty($row['id']) && ! $existing) { $errors[] = "صف {$line}: رقم الوظيفة غير موجود."; continue; }
                $unitId ??= $existing?->org_unit_id;
                if (! $unitId) { $errors[] = "صف {$line}: الجهة غير معروفة."; continue; }
                if ($allowed !== null && ! in_array($unitId, $allowed, true)) { $errors[] = "صف {$line}: الجهة خارج نطاق صلاحياتك."; continue; }

                $code = isset($row['code']) && $row['code'] !== '' ? (string) $row['code'] : null;
                if ($code && JobPosition::where('code', $code)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                    $errors[] = "صف {$line}: كود الوظيفة {$code} مستخدم."; continue;
                }

                $attrs = array_filter([
                    'org_unit_id' => $unitId, 'title' => $title, 'code' => $code,
                    'grade' => $row['grade'] ?? null,
                    'category' => $catByLabel[trim((string) ($row['category'] ?? ''))] ?? ($existing ? null : 'executive'),
                    'headcount' => is_numeric($row['headcount'] ?? null) ? max(1, (int) $row['headcount']) : null,
                    'description' => $row['description'] ?? null,
                ], fn ($v) => $v !== null && $v !== '');

                $position = $existing ? tap($existing)->update($attrs) : JobPosition::create($attrs);
                $existing ? $updated++ : $created++;

                if (! empty($row['parent_code']) || ! empty($row['parent_title'])) {
                    $deferredParents[] = [$position, $row, $line];
                }
                if (! empty($row['holder'])) {
                    $h = trim((string) $row['holder']);
                    $user = User::where('email', $h)->orWhere('national_id', $h)->first();
                    if ($user) $this->syncHolder($position, $user->id, true);
                    else $errors[] = "صف {$line}: الشاغل «{$h}» غير موجود.";
                }
            }

            // الربط بالوظائف الأعلى بعد إنشاء كل الصفوف
            foreach ($deferredParents as [$position, $row, $line]) {
                $unit = OrgUnit::find($position->org_unit_id);
                $parent = null;
                if (! empty($row['parent_code'])) $parent = JobPosition::where('code', (string) $row['parent_code'])->first();
                if (! $parent && ! empty($row['parent_title'])) {
                    $parent = JobPosition::whereIn('org_unit_id', $unit->ancestorIds())->where('title', trim((string) $row['parent_title']))
                        ->where('id', '!=', $position->id)->orderByDesc('org_unit_id')->first();
                }
                if (! $parent) { $errors[] = "صف {$line}: الوظيفة الأعلى غير موجودة."; continue; }
                if ($this->isDescendant($position, $parent->id)) { $errors[] = "صف {$line}: تبعية دائرية."; continue; }
                $position->update(['parent_id' => $parent->id]);
            }
        });

        $this->audit('imported', null, "استيراد الهيكل الوظيفي: {$created} جديدة، {$updated} معدلة");
        $r = back()->with('success', "تمت المعالجة: {$created} وظيفة جديدة، {$updated} معدلة.");
        if ($errors) $r->with('warning', 'ملاحظات (' . count($errors) . "):\n" . implode("\n", array_slice($errors, 0, 15)));

        return $r;
    }
}
