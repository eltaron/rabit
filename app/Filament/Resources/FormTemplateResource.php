<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormTemplateResource\Pages;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Builder;

class FormTemplateResource extends ReadOnlyResource
{
    protected static ?string $model = FormTemplate::class;

    protected static ?string $modelLabel = 'نموذج';

    protected static ?string $pluralModelLabel = 'النماذج';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'النماذج والتقارير';

    protected static ?int $navigationSort = 1;

    public static function fields(): array
    {
        return [
            'name' => [
                'label' => 'اسم النموذج',
                'search' => true,
            ],
            'description' => [
                'label' => 'الوصف',
                'hidden' => true,
            ],
            'is_active' => [
                'label' => 'نشط',
                'type' => 'bool',
                'filter' => true,
            ],
            'requires_approval' => [
                'label' => 'يتطلب اعتماد',
                'type' => 'bool',
            ],
            'distribution' => [
                'label' => 'التوزيع',
                'type' => 'badge',
            ],
            'sector.name' => [
                'label' => 'القطاع',
                'filter' => true,
            ],
            'orgUnit.name' => [
                'label' => 'الجهة',
                'hidden' => true,
            ],
            'form_fields_count' => [
                'label' => 'عدد الحقول',
                'type' => 'number',
            ],
            'form_submissions_count' => [
                'label' => 'عدد الإرسالات',
                'type' => 'number',
            ],
            'createdBy.name' => [
                'label' => 'أنشأه',
            ],
            'created_at' => [
                'label' => 'تاريخ الإنشاء',
                'type' => 'datetime',
            ],
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['formFields', 'formSubmissions']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormTemplates::route('/'),
            'view' => Pages\ViewFormTemplate::route('/{record}'),
        ];
    }
}
