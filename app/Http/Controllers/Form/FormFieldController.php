<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormField;
use App\Models\FormTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FormFieldController extends Controller
{
    public function store(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:text,textarea,number,date,datetime,select,radio,checkbox,file,boolean,section'],
            'options' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'is_readonly' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'default_value' => ['nullable', 'string'],
            'is_visible' => ['boolean'],
        ], [
            'name.required' => 'اسم الحقل مطلوب.',
            'label.required' => 'تسمية الحقل مطلوبة.',
            'type.required' => 'نوع الحقل مطلوب.',
            'type.in' => 'نوع الحقل غير صالح.',
        ]);

        $validated['form_template_id'] = $formTemplate->id;
        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_readonly'] = $request->boolean('is_readonly');
        $validated['is_visible'] = $request->boolean('is_visible', true);

        if (is_null($validated['sort_order'])) {
            $validated['sort_order'] = $formTemplate->formFields()->max('sort_order') + 1;
        }

        $field = FormField::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormField::class,
            'auditable_id' => $field->id,
            'event' => 'created',
            'new_values' => $validated,
            'description' => "إضافة حقل: {$field->label} إلى نموذج: {$formTemplate->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'field' => $field]);
        }

        return redirect()->route('forms.templates.show', $formTemplate)
            ->with('success', 'تم إضافة الحقل بنجاح.');
    }

    public function update(Request $request, FormTemplate $formTemplate, FormField $field): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:text,textarea,number,date,datetime,select,radio,checkbox,file,boolean,section'],
            'options' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'is_readonly' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'default_value' => ['nullable', 'string'],
            'is_visible' => ['boolean'],
        ], [
            'name.required' => 'اسم الحقل مطلوب.',
            'label.required' => 'تسمية الحقل مطلوبة.',
            'type.required' => 'نوع الحقل مطلوب.',
        ]);

        $oldValues = $field->toArray();
        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_readonly'] = $request->boolean('is_readonly');
        $validated['is_visible'] = $request->boolean('is_visible', true);

        $field->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormField::class,
            'auditable_id' => $field->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => "تحديث حقل: {$field->label}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'field' => $field]);
        }

        return redirect()->route('forms.templates.show', $formTemplate)
            ->with('success', 'تم تحديث الحقل بنجاح.');
    }

    public function destroy(Request $request, FormTemplate $formTemplate, FormField $field): RedirectResponse
    {
        $label = $field->label;
        $field->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => FormField::class,
            'auditable_id' => $field->id,
            'event' => 'deleted',
            'old_values' => ['label' => $label],
            'description' => "حذف حقل: {$label}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('forms.templates.show', $formTemplate)
            ->with('success', 'تم حذف الحقل بنجاح.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:form_fields,id'],
        ]);

        foreach ($request->input('order') as $index => $fieldId) {
            FormField::where('id', $fieldId)->update(['sort_order' => $index + 1]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تم تحديث ترتيب الحقول.');
    }
}
