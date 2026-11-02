<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;

class ReportResource extends ReadOnlyResource
{
    protected static ?string $model = Report::class;

    protected static ?string $modelLabel = 'تقرير';

    protected static ?string $pluralModelLabel = 'التقارير';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'النماذج والتقارير';

    protected static ?int $navigationSort = 3;

    public static function fields(): array
    {
        return [
            'title' => [
                'label' => 'العنوان',
                'search' => true,
            ],
            'status' => [
                'label' => 'الحالة',
                'type' => 'badge',
                'filter' => [
                    'draft' => 'مسودة',
                    'pending_approval' => 'بانتظار الاعتماد',
                    'approved' => 'معتمد',
                    'rejected' => 'مرفوض',
                    'sent' => 'مُرسل',
                ],
                'map' => [
                    'draft' => 'مسودة',
                    'pending_approval' => 'بانتظار الاعتماد',
                    'approved' => 'معتمد',
                    'rejected' => 'مرفوض',
                    'sent' => 'مُرسل',
                ],
                'colors' => [
                    'draft' => 'gray',
                    'pending_approval' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'sent' => 'info',
                ],
            ],
            'is_ai_generated' => [
                'label' => 'مولد بالذكاء الاصطناعي',
                'type' => 'bool',
            ],
            'current_version' => [
                'label' => 'الإصدار',
                'type' => 'number',
            ],
            'formSubmission.reference_number' => [
                'label' => 'الإرسال',
            ],
            'createdBy.name' => [
                'label' => 'أنشأه',
            ],
            'approvedBy.name' => [
                'label' => 'اعتمده',
                'hidden' => true,
            ],
            'approved_at' => [
                'label' => 'تاريخ الاعتماد',
                'type' => 'datetime',
                'hidden' => true,
            ],
            'created_at' => [
                'label' => 'تاريخ الإنشاء',
                'type' => 'datetime',
            ],
            'rejection_notes' => [
                'label' => 'ملاحظات الرفض',
                'type' => 'long',
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }
}
