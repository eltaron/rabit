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
use App\Support\RoleLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * إدارة الحسابات: الجهة، الوظيفة، الأدوار، نطاق البيانات، الصلاحيات الإضافية، التفعيل، Excel.
 */
class UserController extends Controller
{
    use WritesAudit;

    public function index(Request $request): View
    {
        $query = User::with(['roles:id,name', 'orgUnit:id,name,type', 'jobPosition:id,title']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('national_id', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }
        if ($role = $request->input('role')) {
            $query->role($role);
        }
        if ($unitId = $request->integer('unit')) {
            $request->boolean('subtree')
                ? $query->whereIn('org_unit_id', OrgUnit::findOrFail($unitId)->descendantIds())
                : $query->where('org_unit_id', $unitId);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }
        if ($scope = $request->input('scope')) {
            $query->where('data_scope', $scope);
        }

        $users = $query->orderByDesc('is_active')->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'twofa' => User::where('two_factor_enabled', true)->count(),
            'no_unit' => User::whereNull('org_unit_id')->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'roles' => Role::orderBy('id')->pluck('name'),
            'units' => $this->unitOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = new User(['is_active' => true, 'data_scope' => 'unit', 'org_unit_id' => $request->integer('unit') ?: null]);

        return view('admin.users.form', $this->formData($user));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $roles = $data['roles'];
        $perms = $data['permissions'] ?? [];
        unset($data['roles'], $data['permissions']);
        $data['password'] = Hash::make($data['password']);
        $data += $this->legacyFields($data['org_unit_id'] ?? null);

        $user = DB::transaction(function () use ($data, $roles, $perms) {
            $user = User::create($data);
            $user->syncRoles($roles);
            $user->syncPermissions($perms);
            $this->syncPosition($user, $data['job_position_id'] ?? null);
            return $user;
        });

        $this->audit('created', $user, "إنشاء حساب: {$user->name}", null, array_merge($data, ['password' => '***', 'roles' => $roles]));

        return redirect()->route('admin.users.index')->with('success', 'تم إنشاء الحساب بنجاح.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', $this->formData($user));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $roles = $data['roles'];
        $perms = $data['permissions'] ?? [];
        unset($data['roles'], $data['permissions']);

        if ($user->id === Auth::id() && ! in_array('super_admin', $roles, true) && $user->hasRole('super_admin')) {
            return back()->withInput()->with('error', 'لا يمكنك إزالة دور مدير النظام عن حسابك الحالي.');
        }
        if ($user->id === Auth::id() && empty($data['is_active'])) {
            return back()->withInput()->with('error', 'لا يمكنك تعطيل حسابك الحالي.');
        }

        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        $data += $this->legacyFields($data['org_unit_id'] ?? null);

        $old = $user->only(['name', 'email', 'org_unit_id', 'job_position_id', 'data_scope', 'is_active']) + ['roles' => $user->getRoleNames()->all()];
        DataScope::forget($user);

        DB::transaction(function () use ($user, $data, $roles, $perms) {
            $user->update($data);
            $user->syncRoles($roles);
            $user->syncPermissions($perms);
            $this->syncPosition($user, $data['job_position_id'] ?? null);
        });
        DataScope::forget($user->fresh());

        $this->audit('updated', $user, "تحديث حساب: {$user->name}", $old, array_merge(collect($data)->except('password')->all(), ['roles' => $roles, 'password' => isset($data['password']) ? '***' : null]));

        return redirect()->route('admin.users.index')->with('success', 'تم تحديث الحساب بنجاح.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'لا يمكن حذف حسابك الخاص.');
        }
        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return back()->with('error', 'لا يمكن حذف آخر مدير للنظام.');
        }
        $name = $user->name;
        try {
            DB::transaction(function () use ($user) {
                JobPosition::where('user_id', $user->id)->update(['user_id' => null]);
                $user->syncRoles([]);
                $user->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // الحساب مرتبط بسجلات (نماذج/إرسالات): نعطله بدلاً من حذفه للحفاظ على السجل التاريخي
            $user->update(['is_active' => false]);
            $this->audit('deactivated', $user, "تعطيل حساب مرتبط بسجلات بدلاً من حذفه: {$name}");
            return back()->with('warning', 'الحساب مرتبط بسجلات في المنصة، لذلك تم تعطيله بدلاً من حذفه.');
        }
        $this->audit('deleted', $user, "حذف حساب: {$name}", ['name' => $name]);

        return redirect()->route('admin.users.index')->with('success', 'تم حذف الحساب.');
    }

    public function toggle(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الحالي.');
        }
        $user->update(['is_active' => ! $user->is_active]);
        $this->audit($user->is_active ? 'activated' : 'deactivated', $user, ($user->is_active ? 'تفعيل' : 'تعطيل') . " حساب: {$user->name}");

        return back()->with('success', $user->is_active ? 'تم تفعيل الحساب.' : 'تم تعطيل الحساب.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $password = self::generatePassword();
        $user->update(['password' => Hash::make($password)]);
        $this->audit('password_reset', $user, "إعادة تعيين كلمة مرور: {$user->name}");

        return back()->with('new_password', ['name' => $user->name, 'email' => $user->email, 'password' => $password]);
    }

    public function assignRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ], ['role.required' => 'الدور مطلوب.', 'role.exists' => 'الدور المحدد غير موجود.']);

        $oldRoles = $user->getRoleNames()->toArray();
        $user->syncRoles([$validated['role']]);
        DataScope::forget($user);
        $this->audit('role_assigned', $user, "تعيين دور {$validated['role']} للمستخدم: {$user->name}", ['roles' => $oldRoles], ['roles' => [$validated['role']]]);

        return back()->with('success', 'تم تعيين الدور بنجاح.');
    }

    /* ------------------------------------------------------------------ helpers */

    public static function generatePassword(): string
    {
        return 'Kfs@' . Str::upper(Str::random(3)) . random_int(1000, 9999) . Str::lower(Str::random(2));
    }

    private function unitOptions(): array
    {
        return OrgUnit::treeOptions(OrgUnit::whereNotIn('type', ['hamlet'])->pluck('id'));
    }

    private function formData(User $user): array
    {
        $positions = JobPosition::with('orgUnit:id,name')->orderBy('org_unit_id')->orderBy('sort_order')
            ->get(['id', 'title', 'org_unit_id', 'user_id']);

        return [
            'user' => $user,
            'units' => $this->unitOptions(),
            'positions' => $positions,
            'roles' => Role::orderBy('id')->pluck('name'),
            'assignedRoles' => $user->exists ? $user->getRoleNames()->all() : [],
            'directPermissions' => $user->exists ? $user->getDirectPermissions()->pluck('name')->all() : [],
            'groups' => RoleLabels::PERMISSION_GROUPS,
            'scopes' => DataScope::LEVELS,
            'defaultScopes' => RoleLabels::DEFAULT_SCOPE,
        ];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'national_id' => ['nullable', 'digits:14', Rule::unique('users', 'national_id')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'org_unit_id' => ['required', 'exists:org_units,id'],
            'job_position_id' => ['nullable', 'exists:job_positions,id'],
            'data_scope' => ['required', Rule::in(array_keys(DataScope::LEVELS))],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'locale' => ['nullable', 'in:ar,en'],
        ], [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'national_id.digits' => 'الرقم القومي 14 رقماً.',
            'national_id.unique' => 'الرقم القومي مسجل لحساب آخر.',
            'org_unit_id.required' => 'اختر جهة الحساب.',
            'roles.required' => 'اختر دوراً واحداً على الأقل.',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['job_position_id'] = $data['job_position_id'] ?? null;

        return $data;
    }

    /** يملأ الحقول القديمة (center_id…) من الجهة للتوافق مع الشاشات القديمة */
    private function legacyFields(?int $unitId): array
    {
        $out = ['sector_id' => null, 'department_id' => null, 'center_id' => null, 'division_id' => null];
        if (! $unitId || ! ($unit = OrgUnit::find($unitId))) return $out;
        $chain = $unit->ancestors()->push($unit)->reverse();
        foreach (['division_id' => 'legacy_division_id', 'center_id' => 'legacy_center_id', 'department_id' => 'legacy_department_id', 'sector_id' => 'legacy_sector_id'] as $field => $legacy) {
            $out[$field] = $chain->first(fn ($u) => $u->{$legacy})?->{$legacy};
        }
        $out['governorate_id'] = \App\Models\Governorate::orderBy('id')->value('id');

        return $out;
    }

    private function syncPosition(User $user, ?int $positionId): void
    {
        JobPosition::where('user_id', $user->id)->when($positionId, fn ($q) => $q->where('id', '!=', $positionId))->update(['user_id' => null]);
        if ($positionId) {
            $p = JobPosition::find($positionId);
            if ($p->user_id && $p->user_id !== $user->id) {
                User::whereKey($p->user_id)->where('job_position_id', $p->id)->update(['job_position_id' => null]);
            }
            $p->update(['user_id' => $user->id]);
            if (! $user->job_title) $user->update(['job_title' => $p->title]);
        }
    }

    /* ------------------------------------------------------------------ Excel */

    private function columns(): array
    {
        $roleLabels = Role::orderBy('id')->pluck('name')->map(fn ($r) => RoleLabels::label($r))->all();

        return [
            ['key' => 'id', 'label' => 'رقم الحساب (للتعديل)', 'readonly' => true, 'width' => 12],
            ['key' => 'name', 'label' => 'الاسم', 'required' => true, 'width' => 28],
            ['key' => 'email', 'label' => 'البريد الإلكتروني', 'required' => true, 'width' => 28],
            ['key' => 'national_id', 'label' => 'الرقم القومي', 'width' => 18],
            ['key' => 'phone', 'label' => 'الهاتف', 'width' => 14],
            ['key' => 'job_title', 'label' => 'المسمى الوظيفي', 'width' => 22],
            ['key' => 'role', 'label' => 'الدور', 'required' => true, 'options' => $roleLabels, 'width' => 22],
            ['key' => 'unit_code', 'label' => 'كود الجهة', 'width' => 14],
            ['key' => 'unit_name', 'label' => 'اسم الجهة', 'width' => 30],
            ['key' => 'data_scope', 'label' => 'نطاق البيانات', 'options' => array_values(DataScope::LEVELS), 'width' => 20],
            ['key' => 'is_active', 'label' => 'نشط', 'options' => ['نعم', 'لا'], 'width' => 8],
            ['key' => 'password', 'label' => 'كلمة المرور (للجديد)', 'width' => 18, 'note' => 'اتركها فارغة لتوليد كلمة مرور تلقائياً'],
        ];
    }

    public function template()
    {
        return ExcelSheet::download(ExcelSheet::build('الحسابات — قالب', $this->columns(), [],
            'حساب في كل صف. يُطابَق الحساب الموجود بالبريد أو الرقم القومي ويُحدَّث، وإلا يُنشأ جديد. الجهة بالكود أو الاسم.'), 'قالب-الحسابات.xlsx');
    }

    public function export(Request $request)
    {
        $users = User::with(['roles:id,name', 'orgUnit:id,name,code'])->orderBy('name')->get();
        $rows = $users->map(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'national_id' => $u->national_id, 'phone' => $u->phone,
            'job_title' => $u->job_title, 'role' => RoleLabels::label($u->roles->first()->name ?? ''),
            'unit_code' => $u->orgUnit?->code, 'unit_name' => $u->orgUnit?->name,
            'data_scope' => DataScope::LEVELS[$u->data_scope] ?? '', 'is_active' => $u->is_active ? 'نعم' : 'لا',
        ])->all();
        $this->audit('exported', null, 'تصدير الحسابات إلى Excel', null, ['count' => count($rows)]);

        return ExcelSheet::download(ExcelSheet::build('الحسابات', $this->columns(), $rows), 'الحسابات-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']], ['file.required' => 'اختر ملف Excel.']);
        $data = ExcelSheet::read($request->file('file')->getRealPath(), $this->columns());

        $roleByLabel = Role::pluck('name')->mapWithKeys(fn ($r) => [RoleLabels::label($r) => $r, $r => $r]);
        $scopeByLabel = collect(DataScope::LEVELS)->flip()->merge(collect(array_keys(DataScope::LEVELS))->mapWithKeys(fn ($k) => [$k => $k]));
        $created = $updated = 0;
        $errors = [];
        $passwords = [];

        DB::transaction(function () use ($data, $roleByLabel, $scopeByLabel, &$created, &$updated, &$errors, &$passwords) {
            foreach ($data['rows'] as $row) {
                $line = $row['__row'];
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $nid = preg_replace('/\D/', '', (string) ($row['national_id'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "صف {$line}: بريد إلكتروني غير صالح."; continue; }
                if ($nid !== '' && strlen($nid) !== 14) { $errors[] = "صف {$line}: الرقم القومي يجب أن يكون 14 رقماً."; continue; }

                $user = ! empty($row['id']) ? User::find((int) $row['id']) : null;
                $user ??= User::where('email', $email)->when($nid !== '', fn ($q) => $q->orWhere('national_id', $nid))->first();

                if (User::where('email', $email)->when($user, fn ($q) => $q->where('id', '!=', $user->id))->exists()) { $errors[] = "صف {$line}: البريد مستخدم لحساب آخر."; continue; }
                if ($nid !== '' && User::where('national_id', $nid)->when($user, fn ($q) => $q->where('id', '!=', $user->id))->exists()) { $errors[] = "صف {$line}: الرقم القومي مستخدم لحساب آخر."; continue; }

                $role = $roleByLabel[trim((string) ($row['role'] ?? ''))] ?? null;
                if (! $user && ! $role) { $errors[] = "صف {$line}: الدور «" . ($row['role'] ?? '') . "» غير معروف."; continue; }

                $unitId = null;
                if (! empty($row['unit_code'])) $unitId = OrgUnit::where('code', (string) $row['unit_code'])->value('id');
                if (! $unitId && ! empty($row['unit_name'])) {
                    $n = OrgStructureService::norm($row['unit_name']);
                    $unitId = OrgUnit::where('name', 'like', '%' . mb_substr(trim((string) $row['unit_name']), 0, 25) . '%')->get(['id', 'name'])->first(fn ($u) => OrgStructureService::norm($u->name) === $n)?->id;
                }
                if (! $user && ! $unitId) { $errors[] = "صف {$line}: الجهة غير معروفة."; continue; }

                $attrs = array_filter([
                    'name' => $name ?: null, 'email' => $email, 'national_id' => $nid ?: null,
                    'phone' => isset($row['phone']) ? (string) $row['phone'] : null, 'job_title' => $row['job_title'] ?? null,
                    'org_unit_id' => $unitId,
                    'data_scope' => $scopeByLabel[trim((string) ($row['data_scope'] ?? ''))] ?? null,
                ], fn ($v) => $v !== null && $v !== '');
                if (isset($row['is_active']) && $row['is_active'] !== '') $attrs['is_active'] = in_array(trim((string) $row['is_active']), ['نعم', '1', 'yes'], true);
                if ($unitId) $attrs += $this->legacyFields($unitId);

                if ($user) {
                    $user->update($attrs);
                    if ($role) $user->syncRoles([$role]);
                    DataScope::forget($user);
                    $updated++;
                } else {
                    if ($name === '') { $errors[] = "صف {$line}: الاسم مطلوب."; continue; }
                    $pwd = trim((string) ($row['password'] ?? ''));
                    if (mb_strlen($pwd) < 8) {
                        $pwd = self::generatePassword();
                        $passwords[] = ['name' => $name, 'email' => $email, 'password' => $pwd];
                    }
                    $attrs['password'] = Hash::make($pwd);
                    $attrs['is_active'] ??= true;
                    $attrs['data_scope'] ??= RoleLabels::DEFAULT_SCOPE[$role] ?? 'unit';
                    $user = User::create($attrs);
                    $user->syncRoles([$role]);
                    $created++;
                }
            }
        });

        $this->audit('imported', null, "استيراد الحسابات من Excel: {$created} جديد، {$updated} معدل", null, ['errors' => count($errors)]);
        $r = redirect()->route('admin.users.index')->with('success', "تمت المعالجة: {$created} حساب جديد، {$updated} حساب معدل.");
        if ($errors) $r->with('warning', 'صفوف لم تُعالج (' . count($errors) . "):\n" . implode("\n", array_slice($errors, 0, 15)));
        if ($passwords) $r->with('new_passwords', $passwords);

        return $r;
    }
}
