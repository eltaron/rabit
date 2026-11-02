<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrgUnitResource\Pages;
use App\Models\OrgUnit;

class OrgUnitResource extends ReadOnlyResource
{
    protected static ?string $model = OrgUnit::class;

    protected static ?string $modelLabel = 'وحدة تنظيمية';

    protected static ?string $pluralModelLabel = 'الهيكل التنظيمي';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'الهيكل والمستخدمين';

    protected static ?int $navigationSort = 1;

    public static function fields(): array
    {
        return [
            'name' => [
                'label' => 'الاسم',
                'search' => true,
            ],
            'type' => [
                'label' => 'النوع',
                'type' => 'badge',
                'filter' => [
                    'governorate' => 'محافظة',
                    'sector' => 'قطاع',
                    'ministry' => 'وزارة / مديرية وزارة',
                    'directorate' => 'مديرية',
                    'authority' => 'هيئة',
                    'company' => 'شركة / جهاز',
                    'center' => 'مركز ومدينة',
                    'city' => 'مدينة',
                    'district' => 'حي',
                    'local_unit' => 'وحدة محلية قروية',
                    'village' => 'قرية',
                    'hamlet' => 'عزبة / كفر / نجع',
                    'department' => 'إدارة',
                    'section' => 'قسم',
                    'office' => 'مكتب / فرع',
                ],
                'map' => [
                    'governorate' => 'محافظة',
                    'sector' => 'قطاع',
                    'ministry' => 'وزارة / مديرية وزارة',
                    'directorate' => 'مديرية',
                    'authority' => 'هيئة',
                    'company' => 'شركة / جهاز',
                    'center' => 'مركز ومدينة',
                    'city' => 'مدينة',
                    'district' => 'حي',
                    'local_unit' => 'وحدة محلية قروية',
                    'village' => 'قرية',
                    'hamlet' => 'عزبة / كفر / نجع',
                    'department' => 'إدارة',
                    'section' => 'قسم',
                    'office' => 'مكتب / فرع',
                ],
            ],
            'parent.name' => [
                'label' => 'تتبع',
            ],
            'code' => [
                'label' => 'الكود',
                'search' => true,
                'hidden' => true,
            ],
            'head_name' => [
                'label' => 'رئيس الوحدة',
                'search' => true,
                'hidden' => true,
            ],
            'head_title' => [
                'label' => 'المسمى',
                'list' => false,
            ],
            'population' => [
                'label' => 'السكان',
                'type' => 'number',
                'hidden' => true,
            ],
            'area_km2' => [
                'label' => 'المساحة كم²',
                'type' => 'number',
                'hidden' => true,
            ],
            'phone' => [
                'label' => 'التليفون',
                'list' => false,
            ],
            'email' => [
                'label' => 'البريد',
                'list' => false,
            ],
            'address' => [
                'label' => 'العنوان',
                'list' => false,
            ],
            'depth' => [
                'label' => 'المستوى',
                'type' => 'number',
                'hidden' => true,
            ],
            'is_active' => [
                'label' => 'نشط',
                'type' => 'bool',
                'filter' => true,
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrgUnits::route('/'),
            'view' => Pages\ViewOrgUnit::route('/{record}'),
        ];
    }
}
