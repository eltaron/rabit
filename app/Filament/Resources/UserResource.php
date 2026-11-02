<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;

class UserResource extends ReadOnlyResource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'الهيكل والمستخدمين';

    protected static ?int $navigationSort = 3;

    public static function fields(): array
    {
        return [
            'name' => [
                'label' => 'الاسم',
                'search' => true,
            ],
            'email' => [
                'label' => 'البريد',
                'search' => true,
            ],
            'roles.name' => [
                'label' => 'الدور',
                'type' => 'badge',
                'filter' => true,
            ],
            'job_title' => [
                'label' => 'المسمى',
                'hidden' => true,
            ],
            'phone' => [
                'label' => 'التليفون',
                'search' => true,
                'hidden' => true,
            ],
            'orgUnit.name' => [
                'label' => 'الجهة',
            ],
            'data_scope' => [
                'label' => 'نطاق البيانات',
                'type' => 'badge',
                'hidden' => true,
            ],
            'is_active' => [
                'label' => 'نشط',
                'type' => 'bool',
                'filter' => true,
            ],
            'two_factor_enabled' => [
                'label' => 'المصادقة الثنائية',
                'type' => 'bool',
                'hidden' => true,
            ],
            'last_login_at' => [
                'label' => 'آخر دخول',
                'type' => 'datetime',
            ],
            'created_at' => [
                'label' => 'تاريخ الإنشاء',
                'type' => 'datetime',
                'hidden' => true,
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
