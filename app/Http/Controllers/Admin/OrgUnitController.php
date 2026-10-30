<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\WritesAudit;
use App\Http\Controllers\Controller;
use App\Models\FacilityData;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\JobPosition;
use App\Models\OrgUnit;
use App\Models\SectorData;
use App\Models\User;
use App\Services\OrgStructureService;
use App\Support\ExcelSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * الهيكل التنظيمي الموحد: المحافظة ← القطاعات/المديريات/الهيئات/المراكز ← المدن/القرى/العزب ← الإدارات/الأقسام.
 */
class OrgUnitController extends Controller
{
    use WritesAudit;

    public function index(Request $request): View
    {
        $typeCounts = OrgUnit::selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type');
        $root = OrgUnit::root();

        return view('admin.org-units.index', [
            'root' => $root,
            'typeCounts' => $typeCounts,
            'total' => $typeCounts->sum(),
            'usersCount' => User::whereNotNull('org_unit_id')->count(),
            'positionsCount' => JobPosition::count(),
            'selected' => $request->integer('unit') ?: $root?->id,
        ]);
    }

    /**
     * عقد الشجرة بشكل كسول: ?parent=ID يعيد الأبناء، ?q= بحث يعيد النتائج مع مساراتها.
     */
    public function tree(Request $request): JsonResponse
    {
        if ($q = trim((string) $request->input('q'))) {
            $units = OrgUnit::where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                ->orderBy('depth')->limit(60)->get();
            $parentNames = OrgUnit::whereIn('id', $units->flatMap(fn ($u) => $u->ancestorIds(false))->unique())->pluck('name', 'id');

            return response()->json(['results' => $units->map(fn ($u) => $this->node($u) + [
                'trail' => collect($u->ancestorIds(false))->map(fn ($id) => $parentNames[$id] ?? null)->filter()->implode(' / '),
                'ancestors' => $u->ancestorIds(false),
            ])]);
        }

        $parentId = $request->input('parent');
        $units = OrgUnit::query()
            ->when($parentId, fn ($q) => $q->where('parent_id', $parentId), fn ($q) => $q->whereNull('parent_id'))
            ->withCount(['children', 'users', 'positions'])
            ->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['nodes' => $units->map(fn ($u) => $this->node($u))]);
    }

    private function node(OrgUnit $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'type' => $u->type,
            'type_label' => $u->type_label,
            'icon' => $u->icon,
            'code' => $u->code,
            'active' => $u->is_active,
            'children' => $u->children_count ?? OrgUnit::where('parent_id', $u->id)->count(),
            'users' => $u->users_count ?? null,
            'positions' => $u->positions_count ?? null,
            'url' => route('admin.org-units.show', $u),
        ];
    }

    public function create(Request $request): View
    {
        $unit = new OrgUnit(['parent_id' => $request->integer('parent') ?: OrgUnit::root()?->id, 'is_active' => true, 'type' => $request->input('type', 'department')]);

        return view('admin.org-units.form', ['unit' => $unit, 'parents' => OrgUnit::treeOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $unit = OrgUnit::create($data);
        $this->audit('created', $unit, "إنشاء جهة: {$unit->name}", null, $data);

        return redirect()->route('admin.org-units.show', $unit)->with('success', 'تمت إضافة الجهة بنجاح.');
    }

    public function show(OrgUnit $orgUnit): View
    {
        $orgUnit->load('parent');
        $subtree = OrgUnit::subtreeOf($orgUnit);
        $subtreeIds = (clone $subtree)->pluck('id');

        $stats = [
            'descendants' => $subtreeIds->count() - 1,
            'users' => User::whereIn('org_unit_id', $subtreeIds)->count(),
            'positions' => JobPosition::whereIn('org_unit_id', $subtreeIds)->count(),
            'vacant' => JobPosition::whereIn('org_unit_id', $subtreeIds)->whereNull('user_id')->count(),
            'templates' => FormTemplate::whereIn('org_unit_id', $subtreeIds)->count(),
            'submissions' => FormSubmission::whereIn('org_unit_id', $subtreeIds)->count(),
            'facilities' => FacilityData::whereIn('org_unit_id', $subtreeIds)->count(),
            'sector_data' => SectorData::whereIn('org_unit_id', $subtreeIds)->count(),
        ];
        $typeBreakdown = (clone $subtree)->where('id', '!=', $orgUnit->id)
            ->selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type');

        return view('admin.org-units.show', [
            'unit' => $orgUnit,
            'ancestors' => $orgUnit->ancestors(),
            'children' => $orgUnit->children()->withCount(['children', 'users'])->paginate(30, ['*'], 'children_page'),
            'users' => User::where('org_unit_id', $orgUnit->id)->with('roles', 'jobPosition')->limit(30)->get(),
            'positions' => JobPosition::where('org_unit_id', $orgUnit->id)->with('holder')->orderBy('sort_order')->get(),
            'stats' => $stats,
            'typeBreakdown' => $typeBreakdown,
        ]);
    }

    public function edit(OrgUnit $orgUnit): View
    {
        $exclude = $orgUnit->descendantIds();
        $parents = collect(OrgUnit::treeOptions())->reject(fn ($v, $id) => $exclude->contains($id))->all();

        return view('admin.org-units.form', ['unit' => $orgUnit, 'parents' => $parents]);
    }

    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $data = $this->validated($request, $orgUnit);
        if (! empty($data['parent_id']) && $orgUnit->descendantIds()->contains((int) $data['parent_id'])) {
            return back()->withInput()->with('error', 'لا يمكن نقل الجهة تحت إحدى الجهات التابعة لها.');
        }
        $old = $orgUnit->only(array_keys($data));
        $orgUnit->update($data);
        $this->audit('updated', $orgUnit, "تحديث جهة: {$orgUnit->name}", $old, $data);

        return redirect()->route('admin.org-units.show', $orgUnit)->with('success', 'تم تحديث بيانات الجهة.');
    }

    public function move(Request $request, OrgUnit $orgUnit): RedirectResponse|JsonResponse
    {
        $request->validate(['parent_id' => ['required', 'exists:org_units,id']]);
        $target = (int) $request->input('parent_id');
        if ($orgUnit->descendantIds()->contains($target)) {
            $msg = 'لا يمكن نقل الجهة تحت إحدى الجهات التابعة لها.';
            return $request->wantsJson() ? response()->json(['message' => $msg], 422) : back()->with('error', $msg);
        }
        $old = $orgUnit->parent_id;
        $orgUnit->update(['parent_id' => $target]);
        $this->audit('moved', $orgUnit, "نقل جهة: {$orgUnit->name}", ['parent_id' => $old], ['parent_id' => $target]);

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('success', 'تم نقل الجهة.');
    }

    public function destroy(OrgUnit $orgUnit): RedirectResponse
    {
        if (! $orgUnit->parent_id) {
            return back()->with('error', 'لا يمكن حذف الجهة الجذرية (المحافظة).');
        }
        if ($orgUnit->children()->exists()) {
            return back()->with('error', 'لا يمكن حذف جهة لها جهات تابعة. انقل أو احذف الجهات التابعة أولاً.');
        }
        if ($orgUnit->users()->exists()) {
            return back()->with('error', 'لا يمكن حذف جهة مرتبط بها مستخدمون. انقل المستخدمين أولاً.');
        }
        $parent = $orgUnit->parent_id;
        $name = $orgUnit->name;
        JobPosition::where('org_unit_id', $orgUnit->id)->delete();
        $orgUnit->delete();
        $this->audit('deleted', $orgUnit, "حذف جهة: {$name}", ['name' => $name]);

        return redirect()->route('admin.org-units.index', ['unit' => $parent])->with('success', 'تم حذف الجهة.');
    }

    /* ------------------------------------------------------------------ Excel */

    private function excelColumns(): array
    {
        return [
            ['key' => 'id', 'label' => 'رقم الجهة (للتعديل)', 'readonly' => true, 'width' => 14, 'note' => 'اتركه فارغاً عند الإضافة'],
            ['key' => 'name', 'label' => 'اسم الجهة', 'required' => true, 'width' => 34],
            ['key' => 'type', 'label' => 'النوع', 'required' => true, 'options' => array_values(OrgUnit::TYPES), 'width' => 22],
            ['key' => 'code', 'label' => 'الكود', 'width' => 14],
            ['key' => 'parent_code', 'label' => 'كود الجهة الأم', 'width' => 16, 'note' => 'يكفي الكود أو الاسم'],
            ['key' => 'parent_name', 'label' => 'اسم الجهة الأم', 'width' => 30],
            ['key' => 'latitude', 'label' => 'خط العرض', 'width' => 12],
            ['key' => 'longitude', 'label' => 'خط الطول', 'width' => 12],
            ['key' => 'population', 'label' => 'عدد السكان', 'width' => 12],
            ['key' => 'area_km2', 'label' => 'المساحة كم²', 'width' => 12],
            ['key' => 'head_name', 'label' => 'اسم رئيس الجهة', 'width' => 22],
            ['key' => 'head_title', 'label' => 'صفة رئيس الجهة', 'width' => 18],
            ['key' => 'phone', 'label' => 'الهاتف', 'width' => 14],
            ['key' => 'email', 'label' => 'البريد الإلكتروني', 'width' => 22],
            ['key' => 'address', 'label' => 'العنوان', 'width' => 30],
            ['key' => 'is_active', 'label' => 'نشطة', 'options' => ['نعم', 'لا'], 'width' => 8],
        ];
    }

    public function template(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $ss = ExcelSheet::build('الهيكل التنظيمي — قالب إضافة جهات', $this->excelColumns(), [],
            'لإضافة جهة: اترك "رقم الجهة" فارغاً واكتب الاسم والنوع وكود أو اسم الجهة الأم. لتعديل جهة: استخدم ملف التصدير واحتفظ برقمها.');

        return ExcelSheet::download($ss, 'قالب-الهيكل-التنظيمي.xlsx');
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $q = OrgUnit::query()->with('parent:id,name,code')->orderBy('depth')->orderBy('parent_id')->orderBy('sort_order');
        if ($rootId = $request->integer('unit')) $q->subtreeOf($rootId);
        if ($types = array_filter((array) $request->input('types'))) $q->whereIn('type', $types);

        $rows = $q->get()->map(fn (OrgUnit $u) => [
            'id' => $u->id, 'name' => $u->name, 'type' => $u->type_label, 'code' => $u->code,
            'parent_code' => $u->parent?->code, 'parent_name' => $u->parent?->name,
            'latitude' => $u->latitude, 'longitude' => $u->longitude, 'population' => $u->population, 'area_km2' => $u->area_km2,
            'head_name' => $u->head_name, 'head_title' => $u->head_title, 'phone' => $u->phone, 'email' => $u->email,
            'address' => $u->address, 'is_active' => $u->is_active ? 'نعم' : 'لا',
        ])->all();

        $ss = ExcelSheet::build('الهيكل التنظيمي', $this->excelColumns(), $rows,
            'عدّل البيانات ثم أعد رفع الملف من شاشة الهيكل التنظيمي. لا تغيّر عمود "رقم الجهة". أضف جهات جديدة في صفوف جديدة برقم فارغ.');

        return ExcelSheet::download($ss, 'الهيكل-التنظيمي-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480']], ['file.required' => 'اختر ملف Excel.']);

        $data = ExcelSheet::read($request->file('file')->getRealPath(), $this->excelColumns());
        $typeByLabel = array_flip(OrgUnit::TYPES);
        $created = $updated = 0;
        $errors = [];

        DB::transaction(function () use ($data, $typeByLabel, &$created, &$updated, &$errors) {
            foreach ($data['rows'] as $row) {
                $line = $row['__row'];
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') { $errors[] = "صف {$line}: اسم الجهة مطلوب."; continue; }

                $typeRaw = trim((string) ($row['type'] ?? ''));
                $type = $typeByLabel[$typeRaw] ?? (isset(OrgUnit::TYPES[$typeRaw]) ? $typeRaw : null);

                $parentId = null;
                if (! empty($row['parent_code'])) $parentId = OrgUnit::where('code', trim((string) $row['parent_code']))->value('id');
                if (! $parentId && ! empty($row['parent_name'])) {
                    $pn = OrgStructureService::norm($row['parent_name']);
                    $parentId = OrgUnit::where('name', 'like', '%' . mb_substr(trim((string) $row['parent_name']), 0, 30) . '%')->get(['id', 'name'])
                        ->first(fn ($u) => OrgStructureService::norm($u->name) === $pn)?->id;
                }

                $attrs = array_filter([
                    'name' => $name,
                    'type' => $type,
                    'code' => isset($row['code']) && $row['code'] !== '' ? (string) $row['code'] : null,
                    'latitude' => is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
                    'longitude' => is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
                    'population' => is_numeric($row['population'] ?? null) ? (int) $row['population'] : null,
                    'area_km2' => is_numeric($row['area_km2'] ?? null) ? (float) $row['area_km2'] : null,
                    'head_name' => $row['head_name'] ?? null, 'head_title' => $row['head_title'] ?? null,
                    'phone' => isset($row['phone']) ? (string) $row['phone'] : null, 'email' => $row['email'] ?? null,
                    'address' => $row['address'] ?? null,
                ], fn ($v) => $v !== null && $v !== '');
                if (isset($row['is_active']) && $row['is_active'] !== '') $attrs['is_active'] = in_array(trim((string) $row['is_active']), ['نعم', '1', 'yes', 'true'], true);

                if (! empty($attrs['code'])) {
                    $dup = OrgUnit::withTrashed()->where('code', $attrs['code'])->when(! empty($row['id']), fn ($q) => $q->where('id', '!=', (int) $row['id']))->exists();
                    if ($dup) { $errors[] = "صف {$line}: الكود {$attrs['code']} مستخدم لجهة أخرى."; continue; }
                }

                if (! empty($row['id'])) {
                    $unit = OrgUnit::find((int) $row['id']);
                    if (! $unit) { $errors[] = "صف {$line}: رقم الجهة {$row['id']} غير موجود."; continue; }
                    if ($parentId && $parentId !== $unit->parent_id) {
                        if ($unit->descendantIds()->contains($parentId)) { $errors[] = "صف {$line}: لا يمكن نقل الجهة تحت جهة تابعة لها."; continue; }
                        $attrs['parent_id'] = $parentId;
                    }
                    $unit->update($attrs);
                    $updated++;
                } else {
                    if (! $type) { $errors[] = "صف {$line}: النوع «{$typeRaw}» غير معروف."; continue; }
                    if (! $parentId) { $errors[] = "صف {$line}: لم يتم العثور على الجهة الأم."; continue; }
                    OrgUnit::create($attrs + ['parent_id' => $parentId, 'is_active' => true]);
                    $created++;
                }
            }
        });

        $this->audit('imported', null, "استيراد الهيكل التنظيمي من Excel: {$created} جديدة، {$updated} معدلة", null, ['errors' => count($errors)]);

        $msg = "تمت المعالجة: {$created} جهة جديدة، {$updated} جهة معدلة.";
        $redirect = redirect()->route('admin.org-units.index')->with('success', $msg);
        if ($errors) $redirect->with('warning', "صفوف لم تُعالج (" . count($errors) . "):\n" . implode("\n", array_slice($errors, 0, 15)));

        return $redirect;
    }

    public function importAdministrative(Request $request, OrgStructureService $svc): RedirectResponse
    {
        $request->validate(['file' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:20480']]);
        try {
            $svc->seedEntities();
            $svc->importAdministrative($request->file('file')?->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر استيراد التقسيم الإداري: ' . $e->getMessage());
        }
        $stats = collect($svc->stats());
        $this->audit('imported', null, 'استيراد التقسيم الإداري للمحافظة', null, $stats->all());
        $summary = $stats->isEmpty() ? 'لا توجد جهات جديدة — الهيكل محدّث بالفعل.'
            : $stats->map(fn ($n, $t) => (OrgUnit::TYPES[$t] ?? $t) . ': ' . $n)->implode('، ');

        return redirect()->route('admin.org-units.index')->with('success', 'تم تحديث التقسيم الإداري. ' . $summary);
    }

    private function validated(Request $request, ?OrgUnit $unit = null): array
    {
        $data = $request->validate([
            'parent_id' => [$unit && ! $unit->parent_id ? 'nullable' : 'required', 'nullable', 'exists:org_units,id'],
            'type' => ['required', Rule::in(array_keys(OrgUnit::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('org_units', 'code')->ignore($unit?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:29,33'],
            'longitude' => ['nullable', 'numeric', 'between:29,33'],
            'population' => ['nullable', 'integer', 'min:0'],
            'area_km2' => ['nullable', 'numeric', 'min:0'],
            'head_name' => ['nullable', 'string', 'max:255'],
            'head_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ], [
            'parent_id.required' => 'اختر الجهة الأم.',
            'name.required' => 'اسم الجهة مطلوب.',
            'type.required' => 'نوع الجهة مطلوب.',
            'code.unique' => 'الكود مستخدم لجهة أخرى.',
            'latitude.between' => 'خط العرض خارج نطاق المحافظة.',
            'longitude.between' => 'خط الطول خارج نطاق المحافظة.',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if ($unit && ! $unit->parent_id) unset($data['parent_id']);

        return $data;
    }
}
