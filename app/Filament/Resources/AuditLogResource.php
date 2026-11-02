<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;

class AuditLogResource extends ReadOnlyResource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $modelLabel = 'سجل';

    protected static ?string $pluralModelLabel = 'سجل التدقيق';

    protected static ?string $recordTitleAttribute = 'event';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static string|\UnitEnum|null $navigationGroup = 'السجلات';

    protected static ?int $navigationSort = 2;

    public static function fields(): array
    {
        return [
            'user.name' => [
                'label' => 'المستخدم',
            ],
            'event' => [
                'label' => 'الحدث',
                'type' => 'badge',
                'filter' => true,
            ],
            'auditable_type' => [
                'label' => 'الكيان',
                'filter' => true,
            ],
            'auditable_id' => [
                'label' => 'المعرف',
            ],
            'description' => [
                'label' => 'الوصف',
                'search' => true,
            ],
            'ip_address' => [
                'label' => 'IP',
                'hidden' => true,
            ],
            'created_at' => [
                'label' => 'التاريخ',
                'type' => 'datetime',
            ],
            'old_values' => [
                'label' => 'القيم القديمة',
                'type' => 'json',
            ],
            'new_values' => [
                'label' => 'القيم الجديدة',
                'type' => 'json',
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
