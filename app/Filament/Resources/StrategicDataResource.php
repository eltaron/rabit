<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StrategicDataResource\Pages;
use App\Models\StrategicData;

class StrategicDataResource extends ReadOnlyResource
{
    protected static ?string $model = StrategicData::class;

    protected static ?string $modelLabel = 'موقع استراتيجي';

    protected static ?string $pluralModelLabel = 'البيانات الاستراتيجية';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات المؤرشفة';

    protected static ?int $navigationSort = 3;

    public static function fields(): array
    {
        return [
            'category' => [
                'label' => 'الفئة',
                'type' => 'badge',
                'filter' => true,
            ],
            'name' => [
                'label' => 'الاسم',
                'search' => true,
            ],
            'type' => [
                'label' => 'النوع',
                'filter' => true,
            ],
            'center' => [
                'label' => 'المركز',
                'search' => true,
                'filter' => true,
            ],
            'city' => [
                'label' => 'المدينة',
                'search' => true,
                'hidden' => true,
            ],
            'neighborhood' => [
                'label' => 'الحي / القرية',
                'search' => true,
                'hidden' => true,
            ],
            'headquarters' => [
                'label' => 'المقر',
                'list' => false,
            ],
            'address' => [
                'label' => 'العنوان',
                'search' => true,
                'hidden' => true,
            ],
            'phone' => [
                'label' => 'التليفون',
                'hidden' => true,
            ],
            'governorate' => [
                'label' => 'المحافظة',
                'list' => false,
            ],
            'x_coord' => [
                'label' => 'X',
                'list' => false,
            ],
            'y_coord' => [
                'label' => 'Y',
                'list' => false,
            ],
            'sheet_name' => [
                'label' => 'الشيت',
                'hidden' => true,
            ],
            'file_name' => [
                'label' => 'الملف',
                'filter' => true,
                'hidden' => true,
            ],
            'additional_data' => [
                'label' => 'بيانات إضافية',
                'type' => 'json',
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategicDatas::route('/'),
            'view' => Pages\ViewStrategicData::route('/{record}'),
        ];
    }
}
