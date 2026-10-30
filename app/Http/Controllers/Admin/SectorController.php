<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Governorate;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SectorController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sector::with('governorate');

        if ($governorateId = $request->input('governorate_id')) {
            $query->where('governorate_id', $governorateId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sectors = $query->latest()->paginate(15)->withQueryString();
        $governorates = Governorate::where('is_active', true)->pluck('name', 'id');

        return view('admin.organizations.index', compact('sectors', 'governorates'));
    }

    public function create(): View
    {
        $governorates = Governorate::where('is_active', true)->pluck('name', 'id');

        return view('admin.organizations.index', compact('governorates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'governorate_id' => ['required', 'exists:governorates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'governorate_id.required' => 'المحافظة مطلوبة.',
            'governorate_id.exists' => 'المحافظة المحددة غير موجودة.',
            'name.required' => 'اسم القطاع مطلوب.',
            'code.required' => 'كود القطاع مطلوب.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $sector = Sector::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Sector::class,
            'auditable_id' => $sector->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "إنشاء قطاع: {$sector->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم إنشاء القطاع بنجاح.');
    }

    public function edit(Sector $sector): View
    {
        $governorates = Governorate::where('is_active', true)->pluck('name', 'id');

        return view('admin.organizations.index', compact('sector', 'governorates'));
    }

    public function update(Request $request, Sector $sector): RedirectResponse
    {
        $validated = $request->validate([
            'governorate_id' => ['required', 'exists:governorates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'governorate_id.required' => 'المحافظة مطلوبة.',
            'name.required' => 'اسم القطاع مطلوب.',
            'code.required' => 'كود القطاع مطلوب.',
        ]);

        $oldValues = $sector->toArray();
        $validated['is_active'] = $request->boolean('is_active');

        $sector->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Sector::class,
            'auditable_id' => $sector->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "تحديث قطاع: {$sector->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم تحديث القطاع بنجاح.');
    }

    public function destroy(Request $request, Sector $sector): RedirectResponse
    {
        if ($sector->departments()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف القطاع لارتباطه بأقسام.');
        }

        $name = $sector->name;
        $sector->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Sector::class,
            'auditable_id' => $sector->id,
            'event' => 'deleted',
            'old_values' => ['name' => $name],
            'description' => "حذف قطاع: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'تم حذف القطاع بنجاح.');
    }
}
