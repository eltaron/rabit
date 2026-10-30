<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Center;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Division::with('center');

        if ($centerId = $request->input('center_id')) {
            $query->where('center_id', $centerId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $divisions = $query->latest()->paginate(15)->withQueryString();
        $centers = Center::where('is_active', true)->with('department')->get();

        return view('admin.organizations.index', compact('divisions', 'centers'));
    }

    public function create(): View
    {
        $centers = Center::where('is_active', true)->get();

        return view('admin.organizations.index', compact('centers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'center_id' => ['required', 'exists:centers,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'center_id.required' => 'Ø§Ù„Ù…Ø±ÙƒØ² Ù…Ø·Ù„ÙˆØ¨.',
            'center_id.exists' => 'Ø§Ù„Ù…Ø±ÙƒØ² Ø§Ù„Ù…Ø­Ø¯Ø¯ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯.',
            'name.required' => 'Ø§Ø³Ù… Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ù…Ø·Ù„ÙˆØ¨.',
            'code.required' => 'ÙƒÙˆØ¯ Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ù…Ø·Ù„ÙˆØ¨.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $division = Division::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Division::class,
            'auditable_id' => $division->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "Ø¥Ù†Ø´Ø§Ø¡ Ù‚Ø³Ù… ÙØ±Ø¹ÙŠ: {$division->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ø¨Ù†Ø¬Ø§Ø­.');
    }

    public function edit(Division $division): View
    {
        $centers = Center::where('is_active', true)->get();

        return view('admin.organizations.index', compact('division', 'centers'));
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'center_id' => ['required', 'exists:centers,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'center_id.required' => 'Ø§Ù„Ù…Ø±ÙƒØ² Ù…Ø·Ù„ÙˆØ¨.',
            'name.required' => 'Ø§Ø³Ù… Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ù…Ø·Ù„ÙˆØ¨.',
            'code.required' => 'ÙƒÙˆØ¯ Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ù…Ø·Ù„ÙˆØ¨.',
        ]);

        $oldValues = $division->toArray();
        $validated['is_active'] = $request->boolean('is_active');

        $division->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Division::class,
            'auditable_id' => $division->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "ØªØ­Ø¯ÙŠØ« Ù‚Ø³Ù… ÙØ±Ø¹ÙŠ: {$division->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ø¨Ù†Ø¬Ø§Ø­.');
    }

    public function destroy(Request $request, Division $division): RedirectResponse
    {
        if ($division->users()->count() > 0) {
            return back()->with('error', 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ù„Ø§Ø±ØªØ¨Ø§Ø·Ù‡ Ø¨Ù…Ø³ØªØ®Ø¯Ù…ÙŠÙ†.');
        }

        $name = $division->name;
        $division->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Division::class,
            'auditable_id' => $division->id,
            'event' => 'deleted',
            'old_values' => ['name' => $name],
            'description' => "Ø­Ø°Ù Ù‚Ø³Ù… ÙØ±Ø¹ÙŠ: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.governorates.index')
            ->with('success', 'ØªÙ… Ø­Ø°Ù Ø§Ù„Ù‚Ø³Ù… Ø§Ù„ÙØ±Ø¹ÙŠ Ø¨Ù†Ø¬Ø§Ø­.');
    }
}

