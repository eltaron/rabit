<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user()->load('governorate', 'sector', 'department', 'center', 'division', 'orgUnit', 'jobPosition.parent.holder', 'roles');

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'in:ar,en'],
        ], [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
        ]);

        $oldValues = $user->only(array_keys($validated));
        $user->update($validated);

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'event' => 'profile_updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => 'تحديث الملف الشخصي',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'كلمة المرور الحالية غير صحيحة.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'event' => 'password_changed',
            'description' => 'تغيير كلمة المرور',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }
}
