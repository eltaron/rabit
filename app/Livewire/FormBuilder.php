<?php

namespace App\Livewire;

use App\Models\FormField;
use App\Models\FormTemplate;
use Livewire\Component;

class FormBuilder extends Component
{
    public ?int $templateId = null;
    public array $fields = [];
    public array $newField = [
        'name' => '',
        'label' => '',
        'type' => 'text',
        'options' => null,
        'validation_rules' => null,
        'is_required' => false,
        'is_readonly' => false,
        'placeholder' => '',
        'help_text' => '',
        'default_value' => '',
        'is_visible' => true,
    ];

    protected array $rules = [
        'templateId' => 'nullable|integer',
        'fields' => 'array',
        'newField.name' => 'required|string|max:255',
        'newField.label' => 'required|string|max:255',
        'newField.type' => 'required|string|in:text,textarea,number,email,phone,national_id,date,select,multiselect,radio,checkbox,file,image,boolean,latitude,longitude',
        'newField.is_required' => 'boolean',
        'newField.is_readonly' => 'boolean',
        'newField.placeholder' => 'nullable|string|max:255',
        'newField.help_text' => 'nullable|string|max:500',
        'newField.default_value' => 'nullable|string|max:255',
    ];

    protected array $validationAttributes = [
        'newField.name' => 'اسم الحقل',
        'newField.label' => 'عنوان الحقل',
        'newField.type' => 'نوع الحقل',
    ];

    public function mount(?int $templateId = null): void
    {
        $this->templateId = $templateId;

        if ($templateId) {
            $template = FormTemplate::with('formFields')->findOrFail($templateId);
            $this->fields = $template->formFields
                ->sortBy('sort_order')
                ->values()
                ->where('is_visible', true)
                ->values()
                ->map(fn (FormField $field) => $field->toArray() + [
                    'options_text' => implode("
", (array) ($field->options ?? [])),
                    'accepted_types' => $field->validation_rules['accepted_types'] ?? '',
                ])
                ->toArray();
        }
    }

    public function addField(string $type = 'text'): void
    {
        $sortOrder = count($this->fields);

        $newField = [
            'name' => 'field_' . ($sortOrder + 1),
            'label' => '',
            'type' => $type,
            'options' => null,
            'options_text' => '',
            'accepted_types' => '',
            'validation_rules' => null,
            'is_required' => false,
            'is_readonly' => false,
            'sort_order' => $sortOrder,
            'placeholder' => '',
            'help_text' => '',
            'default_value' => '',
            'is_visible' => true,
        ];

        $this->fields = [...$this->fields, $newField];

        $this->dispatch('fieldAdded', count: count($this->fields));
    }

    public function changeFieldType(int $index, string $type): void
    {
        if (isset($this->fields[$index])) {
            $this->fields[$index]['type'] = $type;
        }
    }

    public function removeField(int $index): void
    {
        if (isset($this->fields[$index])) {
            unset($this->fields[$index]);
            $this->fields = array_values($this->fields);
            $this->updateSortOrders();
        }
    }

    public function updateField(int $index, string $property, mixed $value): void
    {
        if (isset($this->fields[$index])) {
            $this->fields[$index][$property] = $value;
        }
    }

    public function reorderFields(array $order): void
    {
        $reordered = [];
        foreach ($order as $index) {
            if (isset($this->fields[$index])) {
                $reordered[] = $this->fields[$index];
            }
        }
        $this->fields = $reordered;
        $this->updateSortOrders();
    }

    public function moveFieldUp(int $index): void
    {
        if ($index > 0 && isset($this->fields[$index])) {
            $temp = $this->fields[$index];
            $this->fields[$index] = $this->fields[$index - 1];
            $this->fields[$index - 1] = $temp;
            $this->updateSortOrders();
        }
    }

    public function moveFieldDown(int $index): void
    {
        if (isset($this->fields[$index]) && $index < count($this->fields) - 1) {
            $temp = $this->fields[$index];
            $this->fields[$index] = $this->fields[$index + 1];
            $this->fields[$index + 1] = $temp;
            $this->updateSortOrders();
        }
    }

    public function saveTemplate(): void
    {
        $this->validate();

        $template = FormTemplate::findOrFail($this->templateId);

        $existingFieldIds = collect($this->fields)
            ->pluck('id')
            ->filter()
            ->toArray();

        $template->formFields()
            ->whereNotIn('id', $existingFieldIds)
            ->delete();

        foreach ($this->fields as $index => $fieldData) {
            if (isset($fieldData['id'])) {
                FormField::where('id', $fieldData['id'])
                    ->update(array_merge($fieldData, ['sort_order' => $index]));
            } else {
                $template->formFields()->create(array_merge($fieldData, ['sort_order' => $index]));
            }
        }

        $this->dispatch('templateSaved', templateId: $template->id);
    }

    private function updateSortOrders(): void
    {
        foreach ($this->fields as $index => &$field) {
            $field['sort_order'] = $index;
        }
    }

    private function resetNewField(): void
    {
        $this->newField = [
            'name' => '',
            'label' => '',
            'type' => 'text',
            'options' => null,
            'validation_rules' => null,
            'is_required' => false,
            'is_readonly' => false,
            'placeholder' => '',
            'help_text' => '',
            'default_value' => '',
            'is_visible' => true,
        ];
    }

    public function render()
    {
        return view('livewire.form-builder');
    }
}
