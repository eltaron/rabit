<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Governorate;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GovernorateController extends Controller
{
    public function index(Request $request): View
    {
        $sectors = Sector::with(['departments.centers.divisions'])->get();
        $departments = Department::with('centers')->get();

        return view('admin.organizations.index', compact('sectors', 'departments'));
    }

    public function create(): View
    {
        return view('admin.organizations.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:governorates,name'],
            'code' => ['required', 'string', 'max:50', 'unique:governorates,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'اسم المحافظة مطلوب.',
            'name.unique' => 'اسم المحافظة موجود مسبقاً.',
            'code.required' => 'كود المحافظة مطلوب.',
            'code.unique' => 'كود المحافظة موجود مسبقاً.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = Auth::id();

        $governorate = Governorate::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Governorate::class,
            'auditable_id' => $governorate->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "إنشاء محافظة: {$governorate->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم إنشاء المحافظة بنجاح.');
    }

    public function edit(Governorate $governorate): View
    {
        return view('admin.organizations.index', compact('governorate'));
    }

    public function update(Request $request, Governorate $governorate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:governorates,name,' . $governorate->id],
            'code' => ['required', 'string', 'max:50', 'unique:governorates,code,' . $governorate->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'اسم المحافظة مطلوب.',
            'name.unique' => 'اسم المحافظة موجود مسبقاً.',
            'code.required' => 'كود المحافظة مطلوب.',
            'code.unique' => 'كود المحافظة موجود مسبقاً.',
        ]);

        $oldValues = $governorate->toArray();
        $validated['is_active'] = $request->boolean('is_active');

        $governorate->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Governorate::class,
            'auditable_id' => $governorate->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "تحديث محافظة: {$governorate->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم تحديث المحافظة بنجاح.');
    }

    public function destroy(Request $request, Governorate $governorate): RedirectResponse
    {
        if ($governorate->sectors()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف المحافظة لارتباطها بقطاعات.');
        }

        $name = $governorate->name;
        $governorate->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Governorate::class,
            'auditable_id' => $governorate->id,
            'event' => 'deleted',
            'old_values' => ['name' => $name],
            'description' => "حذف محافظة: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم حذف المحافظة بنجاح.');
    }
}
