<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\WritesAudit;
use App\Http\Controllers\Controller;
use App\Support\RoleLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * إدارة الأدوار والصلاحيات (قابلة للتعديل بالكامل من الواجهة).
 */
class RoleController extends Controller
{
    use WritesAudit;

    private function groups(): array
    {
        // الصلاحيات المعرفة في الكتالوج + أي صلاحية موجودة في قاعدة البيانات وغير مصنفة
        $groups = RoleLabels::PERMISSION_GROUPS;
        $known = RoleLabels::allPermissions();
        $extra = Permission::whereNotIn('name', $known)->pluck('name');
        if ($extra->isNotEmpty()) {
            $groups['other'] = ['صلاحيات أخرى', 'fa-ellipsis', $extra->mapWithKeys(fn ($p) => [$p => str_replace('_', ' ', $p)])->all()];
        }
        return $groups;
    }

    public function index(): View
    {
        $roles = Role::withCount(['users', 'permissions'])->with('permissions:id,name')->orderBy('id')->get();
        $totalPermissions = Permission::count();

        return view('admin.roles.index', [
            'roles' => $roles,
            'groups' => $this->groups(),
            'totalPermissions' => $totalPermissions,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role(), 'groups' => $this->groups(), 'assigned' => [], 'label' => '']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'name')],
            'label' => ['nullable', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.required' => 'المعرّف التقني للدور مطلوب.',
            'name.regex' => 'المعرّف بحروف إنجليزية صغيرة وأرقام وشرطة سفلية فقط (مثال: data_officer).',
            'name.unique' => 'يوجد دور بنفس المعرّف.',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        $this->saveLabel($role->name, $data['label'] ?? null);
        $this->flush();
        $this->audit('created', $role, "إنشاء دور: {$role->name}", null, ['permissions' => $data['permissions'] ?? []]);

        return redirect()->route('admin.roles.index')->with('success', 'تم إنشاء الدور «' . self::label($role->name) . '».');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', [
            'role' => $role,
            'groups' => $this->groups(),
            'assigned' => $role->permissions->pluck('name')->all(),
            'label' => self::label($role->name),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $old = $role->permissions->pluck('name')->all();
        $perms = $role->name === 'super_admin' ? Permission::pluck('name')->all() : ($data['permissions'] ?? []);
        $role->syncPermissions($perms);
        $this->saveLabel($role->name, $data['label'] ?? null);
        $this->flush();
        $this->audit('updated', $role, "تحديث صلاحيات الدور: {$role->name}", ['permissions' => $old], ['permissions' => $perms]);

        return redirect()->route('admin.roles.index')->with('success', 'تم تحديث صلاحيات الدور «' . self::label($role->name) . '».');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            return back()->with('error', 'لا يمكن حذف دور مدير النظام.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'لا يمكن حذف دور مسند لمستخدمين. انقل المستخدمين إلى دور آخر أولاً.');
        }
        $name = $role->name;
        $role->delete();
        $this->saveLabel($name, null);
        $this->flush();
        $this->audit('deleted', null, "حذف دور: {$name}", ['name' => $name]);

        return redirect()->route('admin.roles.index')->with('success', 'تم حذف الدور.');
    }

    /** مصفوفة الأدوار × الصلاحيات */
    public function updateMatrix(Request $request): RedirectResponse
    {
        $request->validate(['matrix' => ['array'], 'matrix.*' => ['array'], 'matrix.*.*' => ['string', 'exists:permissions,name']]);
        $matrix = $request->input('matrix', []);
        $changed = 0;

        foreach (Role::all() as $role) {
            if ($role->name === 'super_admin') {
                $role->syncPermissions(Permission::pluck('name'));
                continue;
            }
            $new = collect($matrix[$role->id] ?? [])->unique()->sort()->values()->all();
            $old = $role->permissions->pluck('name')->sort()->values()->all();
            if ($new !== $old) {
                $role->syncPermissions($new);
                $changed++;
                $this->audit('updated', $role, "تحديث صلاحيات الدور من المصفوفة: {$role->name}", ['permissions' => $old], ['permissions' => $new]);
            }
        }
        $this->flush();

        return redirect()->route('admin.roles.index', ['tab' => 'matrix'])->with('success', $changed ? "تم حفظ المصفوفة ({$changed} دور معدّل)." : 'لا توجد تغييرات.');
    }

    public static function label(string $role): string
    {
        return RoleLabels::label($role);
    }

    private function saveLabel(string $role, ?string $label): void
    {
        RoleLabels::saveCustomLabel($role, $label);
    }

    private function flush(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
