<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenancePlanResource\Pages;
use App\Models\MaintenancePlan;

class MaintenancePlanResource extends ReadOnlyResource
{
    protected static ?string $model = MaintenancePlan::class;

    protected static ?string $modelLabel = 'خطة صيانة';

    protected static ?string $pluralModelLabel = 'خطط الصيانة';

    protected static ?string $recordTitleAttribute = 'vehicle_number';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات المؤرشفة';

    protected static ?int $navigationSort = 2;

    public static function fields(): array
    {
        return [
            'serial_number' => [
                'label' => 'م',
            ],
            'center_name' => [
                'label' => 'المركز',
                'search' => true,
                'filter' => true,
            ],
            'vehicle_number' => [
                'label' => 'رقم المركبة',
                'search' => true,
            ],
            'vehicle_type' => [
                'label' => 'نوع المركبة',
                'search' => true,
                'filter' => true,
            ],
            'usage_group' => [
                'label' => 'مجموعة الاستخدام',
                'filter' => true,
            ],
            'month' => [
                'label' => 'الشهر',
                'type' => 'number',
                'filter' => true,
            ],
            'year' => [
                'label' => 'السنة',
                'type' => 'number',
                'filter' => true,
            ],
            'week1_planned' => [
                'label' => 'أسبوع 1 مخطط',
                'hidden' => true,
            ],
            'week1_executed' => [
                'label' => 'أسبوع 1 منفذ',
                'hidden' => true,
            ],
            'week2_planned' => [
                'label' => 'أسبوع 2 مخطط',
                'hidden' => true,
            ],
            'week2_executed' => [
                'label' => 'أسبوع 2 منفذ',
                'hidden' => true,
            ],
            'week3_planned' => [
                'label' => 'أسبوع 3 مخطط',
                'hidden' => true,
            ],
            'week3_executed' => [
                'label' => 'أسبوع 3 منفذ',
                'hidden' => true,
            ],
            'week4_planned' => [
                'label' => 'أسبوع 4 مخطط',
                'hidden' => true,
            ],
            'week4_executed' => [
                'label' => 'أسبوع 4 منفذ',
                'hidden' => true,
            ],
            'file_name' => [
                'label' => 'الملف',
                'filter' => true,
                'hidden' => true,
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenancePlans::route('/'),
            'view' => Pages\ViewMaintenancePlan::route('/{record}'),
        ];
    }
}
