<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Models\Vehicle;

class VehicleResource extends ReadOnlyResource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $modelLabel = 'المركبة';

    protected static ?string $pluralModelLabel = 'المركبات';

    protected static ?string $recordTitleAttribute = 'license_plate';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات المؤرشفة';

    protected static ?int $navigationSort = 1;

    public static function fields(): array
    {
        return [
            'serial_number' => [
                'label' => 'م',
                'search' => true,
            ],
            'entity' => [
                'label' => 'الجهة',
                'search' => true,
                'filter' => true,
            ],
            'affiliated_entity' => [
                'label' => 'الجهة التابعة',
                'search' => true,
                'hidden' => true,
            ],
            'license_plate' => [
                'label' => 'رقم اللوحة',
                'search' => true,
            ],
            'vehicle_type' => [
                'label' => 'نوع المركبة',
                'search' => true,
                'filter' => true,
            ],
            'status' => [
                'label' => 'الحالة',
                'type' => 'badge',
                'filter' => true,
                'colors' => [
                    'صالح' => 'success',
                    'عاطل' => 'danger',
                    'جسيم' => 'danger',
                    'مجمع' => 'warning',
                    'مستغني' => 'gray',
                ],
            ],
            'technical_condition' => [
                'label' => 'الحالة الفنية',
                'hidden' => true,
            ],
            'fuel_type' => [
                'label' => 'نوع الوقود',
                'filter' => true,
                'hidden' => true,
            ],
            'model_year' => [
                'label' => 'سنة الموديل',
                'hidden' => true,
            ],
            'manufacture_year' => [
                'label' => 'سنة الصنع',
                'list' => false,
            ],
            'chassis_number' => [
                'label' => 'رقم الشاسيه',
                'search' => true,
                'list' => false,
            ],
            'engine_number' => [
                'label' => 'رقم الموتور',
                'search' => true,
                'list' => false,
            ],
            'vehicle_type_per_license' => [
                'label' => 'النوع حسب الرخصة',
                'list' => false,
            ],
            'color' => [
                'label' => 'اللون',
                'list' => false,
            ],
            'procurement_type' => [
                'label' => 'طريقة الشراء',
                'list' => false,
            ],
            'procurement_source' => [
                'label' => 'جهة الشراء',
                'list' => false,
            ],
            'procurement_year' => [
                'label' => 'سنة الشراء',
                'list' => false,
            ],
            'odometer_reading' => [
                'label' => 'قراءة العداد',
                'list' => false,
            ],
            'location' => [
                'label' => 'الموقع',
                'hidden' => true,
            ],
            'maintenance_contract' => [
                'label' => 'عقد الصيانة',
                'list' => false,
            ],
            'breakdown_type' => [
                'label' => 'نوع العطل',
                'list' => false,
            ],
            'breakdown_date' => [
                'label' => 'تاريخ العطل',
                'list' => false,
            ],
            'breakdown_cause' => [
                'label' => 'سبب العطل',
                'list' => false,
            ],
            'action_taken' => [
                'label' => 'الإجراء المتخذ',
                'list' => false,
            ],
            'technical_validity' => [
                'label' => 'الصلاحية الفنية',
                'list' => false,
            ],
            'sheet_name' => [
                'label' => 'الشيت',
                'filter' => true,
                'hidden' => true,
            ],
            'file_name' => [
                'label' => 'الملف',
                'filter' => true,
                'hidden' => true,
            ],
            'created_at' => [
                'label' => 'تاريخ الأرشفة',
                'type' => 'datetime',
                'hidden' => true,
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'view' => Pages\ViewVehicle::route('/{record}'),
        ];
    }
}
