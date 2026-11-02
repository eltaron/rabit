<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectorDataResource\Pages;
use App\Models\SectorData;

class SectorDataResource extends ReadOnlyResource
{
    protected static ?string $model = SectorData::class;

    protected static ?string $modelLabel = 'بيان قطاعي';

    protected static ?string $pluralModelLabel = 'البيانات القطاعية';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات المؤرشفة';

    protected static ?int $navigationSort = 4;

    public static function fields(): array
    {
        return [
            'sector' => [
                'label' => 'القطاع',
                'type' => 'badge',
                'filter' => true,
                'map' => [
                    'education' => 'التعليم',
                    'health' => 'الصحة',
                    'investment' => 'الاستثمار',
                    'population' => 'السكان',
                    'supply' => 'التموين',
                ],
            ],
            'name' => [
                'label' => 'الاسم',
                'search' => true,
            ],
            'subcategory' => [
                'label' => 'التصنيف',
                'search' => true,
                'filter' => true,
            ],
            'center_name' => [
                'label' => 'المركز',
                'search' => true,
                'filter' => true,
            ],
            'year_period' => [
                'label' => 'الفترة',
                'filter' => true,
                'hidden' => true,
            ],
            'address' => [
                'label' => 'العنوان',
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
            'sheet_name' => [
                'label' => 'الشيت',
                'hidden' => true,
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
            'index' => Pages\ListSectorDatas::route('/'),
            'view' => Pages\ViewSectorData::route('/{record}'),
        ];
    }
}
