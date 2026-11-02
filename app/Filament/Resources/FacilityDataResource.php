<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacilityDataResource\Pages;
use App\Models\FacilityData;

class FacilityDataResource extends ReadOnlyResource
{
    protected static ?string $model = FacilityData::class;

    protected static ?string $modelLabel = 'منشأة';

    protected static ?string $pluralModelLabel = 'المنشآت والمرافق';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات المؤرشفة';

    protected static ?int $navigationSort = 5;

    public static function fields(): array
    {
        return [
            'module_name' => [
                'label' => 'النوع',
                'type' => 'badge',
                'filter' => true,
            ],
            'name' => [
                'label' => 'الاسم',
                'search' => true,
            ],
            'type' => [
                'label' => 'التصنيف',
                'filter' => true,
            ],
            'center_name' => [
                'label' => 'المركز',
                'search' => true,
                'filter' => true,
            ],
            'city' => [
                'label' => 'المدينة',
                'search' => true,
                'hidden' => true,
            ],
            'district' => [
                'label' => 'الحي',
                'hidden' => true,
            ],
            'directorate' => [
                'label' => 'المديرية',
                'hidden' => true,
            ],
            'address' => [
                'label' => 'العنوان',
                'hidden' => true,
            ],
            'phone' => [
                'label' => 'التليفون',
                'hidden' => true,
            ],
            'latitude' => [
                'label' => 'خط العرض',
                'list' => false,
            ],
            'longitude' => [
                'label' => 'خط الطول',
                'list' => false,
            ],
            'file_name' => [
                'label' => 'الملف',
                'filter' => true,
                'hidden' => true,
            ],
            'extra_data' => [
                'label' => 'بيانات إضافية',
                'type' => 'json',
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacilityDatas::route('/'),
            'view' => Pages\ViewFacilityData::route('/{record}'),
        ];
    }
}
