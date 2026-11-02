<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobPositionResource\Pages;
use App\Models\JobPosition;

class JobPositionResource extends ReadOnlyResource
{
    protected static ?string $model = JobPosition::class;

    protected static ?string $modelLabel = 'وظيفة';

    protected static ?string $pluralModelLabel = 'الوظائف';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'الهيكل والمستخدمين';

    protected static ?int $navigationSort = 2;

    public static function fields(): array
    {
        return [
            'title' => [
                'label' => 'المسمى الوظيفي',
                'search' => true,
            ],
            'orgUnit.name' => [
                'label' => 'الوحدة',
            ],
            'category' => [
                'label' => 'الفئة',
                'type' => 'badge',
                'filter' => [
                    'leadership' => 'قيادية',
                    'supervisory' => 'إشرافية',
                    'specialist' => 'تخصصية',
                    'executive' => 'تنفيذية',
                    'support' => 'معاونة',
                ],
                'map' => [
                    'leadership' => 'قيادية',
                    'supervisory' => 'إشرافية',
                    'specialist' => 'تخصصية',
                    'executive' => 'تنفيذية',
                    'support' => 'معاونة',
                ],
            ],
            'grade' => [
                'label' => 'الدرجة',
                'filter' => true,
            ],
            'headcount' => [
                'label' => 'العدد',
                'type' => 'number',
            ],
            'holder.name' => [
                'label' => 'شاغل الوظيفة',
            ],
            'code' => [
                'label' => 'الكود',
                'hidden' => true,
            ],
            'is_active' => [
                'label' => 'نشط',
                'type' => 'bool',
                'filter' => true,
            ],
            'description' => [
                'label' => 'الوصف',
                'type' => 'long',
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPositions::route('/'),
            'view' => Pages\ViewJobPosition::route('/{record}'),
        ];
    }
}
