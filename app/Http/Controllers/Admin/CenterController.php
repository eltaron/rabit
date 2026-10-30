<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Center;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CenterController extends Controller
{
    public function index(Request $request): View
    {
        $query = Center::with('department');

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $centers = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->with('sector')->get();

        return view('admin.organizations.index', compact('centers', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::where('is_active', true)->get();

        return view('admin.organizations.index', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ], [
            'department_id.required' => 'Ø§Ù„Ù‚Ø³Ù… Ù…Ø·Ù„ÙˆØ¨.',
            'department_id.exists' => 'Ø§Ù„Ù‚Ø³Ù… Ø§Ù„Ù…Ø­Ø¯Ø¯ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯.',
            'name.required' => 'Ø§Ø³Ù… Ø§Ù„Ù…Ø±ÙƒØ² Ù…Ø·Ù„ÙˆØ¨.',
            'code.required' => 'ÙƒÙˆØ¯ Ø§Ù„Ù…Ø±ÙƒØ² Ù…Ø·Ù„ÙˆØ¨.',
            'latitude.between' => 'Ø®Ø· Ø§Ù„Ø¹Ø±Ø¶ ÙŠØ¬Ø¨ Ø£Ù† ÙŠÙƒÙˆÙ† Ø¨ÙŠÙ† -90 Ùˆ 90.',
            'longitude.between' => 'Ø®Ø· Ø§Ù„Ø·ÙˆÙ„ ÙŠØ¬Ø¨ Ø£Ù† ÙŠÙƒÙˆÙ† Ø¨ÙŠÙ† -180 Ùˆ 180.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['latitude'] = $validated['latitude'] ?? null;
        $validated['longitude'] = $validated['longitude'] ?? null;

        $center = Center::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Center::class,
            'auditable_id' => $center->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø±ÙƒØ²: {$center->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù…Ø±ÙƒØ² Ø¨Ù†Ø¬Ø§Ø­.');
    }

    public function edit(Center $center): View
    {
        $departments = Department::where('is_active', true)->get();

        return view('admin.organizations.index', compact('center', 'departments'));
    }

    public function update(Request $request, Center $center): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ], [
            'department_id.required' => 'Ø§Ù„Ù‚Ø³Ù… Ù…Ø·Ù„ÙˆØ¨.',
            'name.required' => 'Ø§Ø³Ù… Ø§Ù„Ù…Ø±ÙƒØ² Ù…Ø·Ù„ÙˆØ¨.',
            'code.required' => 'ÙƒÙˆØ¯ Ø§Ù„Ù…Ø±ÙƒØ² Ù…Ø·Ù„ÙˆØ¨.',
            'latitude.between' => 'Ø®Ø· Ø§Ù„Ø¹Ø±Ø¶ ÙŠØ¬Ø¨ Ø£Ù† ÙŠÙƒÙˆÙ† Ø¨ÙŠÙ† -90 Ùˆ 90.',
            'longitude.between' => 'Ø®Ø· Ø§Ù„Ø·ÙˆÙ„ ÙŠØ¬Ø¨ Ø£Ù† ÙŠÙƒÙˆÙ† Ø¨ÙŠÙ† -180 Ùˆ 180.',
        ]);

        $oldValues = $center->toArray();
        $validated['is_active'] = $request->boolean('is_active');
        $validated['latitude'] = $validated['latitude'] ?? null;
        $validated['longitude'] = $validated['longitude'] ?? null;

        $center->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Center::class,
            'auditable_id' => $center->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "ØªØ­Ø¯ÙŠØ« Ù…Ø±ÙƒØ²: {$center->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„Ù…Ø±ÙƒØ² Ø¨Ù†Ø¬Ø§Ø­.');
    }

    public function destroy(Request $request, Center $center): RedirectResponse
    {
        if ($center->divisions()->count() > 0) {
            return back()->with('error', 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ø§Ù„Ù…Ø±ÙƒØ² Ù„Ø§Ø±ØªØ¨Ø§Ø·Ù‡ Ø¨Ø£Ù‚Ø³Ø§Ù… ÙØ±Ø¹ÙŠØ©.');
        }

        $name = $center->name;
        $center->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Center::class,
            'auditable_id' => $center->id,
            'event' => 'deleted',
            'old_values' => ['name' => $name],
            'description' => "Ø­Ø°Ù Ù…Ø±ÙƒØ²: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'ØªÙ… Ø­Ø°Ù Ø§Ù„Ù…Ø±ÙƒØ² Ø¨Ù†Ø¬Ø§Ø­.');
    }
}

