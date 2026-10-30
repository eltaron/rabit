<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Department::with('sector');

        if ($sectorId = $request->input('sector_id')) {
            $query->where('sector_id', $sectorId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $departments = $query->latest()->paginate(15)->withQueryString();
        $sectors = Sector::where('is_active', true)->with('governorate')->get();

        return view('admin.organizations.index', compact('departments', 'sectors'));
    }

    public function create(): View
    {
        $sectors = Sector::where('is_active', true)->get();

        return view('admin.organizations.index', compact('sectors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sector_id' => ['required', 'exists:sectors,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'sector_id.required' => 'القطاع مطلوب.',
            'sector_id.exists' => 'القطاع المحدد غير موجود.',
            'name.required' => 'اسم القسم مطلوب.',
            'code.required' => 'كود القسم مطلوب.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $department = Department::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Department::class,
            'auditable_id' => $department->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "إنشاء قسم: {$department->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم إنشاء القسم بنجاح.');
    }

    public function edit(Department $department): View
    {
        $sectors = Sector::where('is_active', true)->get();

        return view('admin.organizations.index', compact('department', 'sectors'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'sector_id' => ['required', 'exists:sectors,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'sector_id.required' => 'القطاع مطلوب.',
            'name.required' => 'اسم القسم مطلوب.',
            'code.required' => 'كود القسم مطلوب.',
        ]);

        $oldValues = $department->toArray();
        $validated['is_active'] = $request->boolean('is_active');

        $department->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Department::class,
            'auditable_id' => $department->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "تحديث قسم: {$department->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم تحديث القسم بنجاح.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        if ($department->centers()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف القسم لارتباطه بمراكز.');
        }

        $name = $department->name;
        $department->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Department::class,
            'auditable_id' => $department->id,
            'event' => 'deleted',
            'old_values' => ['name' => $name],
            'description' => "حذف قسم: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم حذف القسم بنجاح.');
    }
}
