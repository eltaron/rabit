<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowLogResource\Pages;
use App\Models\WorkflowLog;

class WorkflowLogResource extends ReadOnlyResource
{
    protected static ?string $model = WorkflowLog::class;

    protected static ?string $modelLabel = 'حركة';

    protected static ?string $pluralModelLabel = 'سجل سير العمل';

    protected static ?string $recordTitleAttribute = 'action';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'السجلات';

    protected static ?int $navigationSort = 1;

    public static function fields(): array
    {
        return [
            'formSubmission.reference_number' => [
                'label' => 'الإرسال',
                'search' => true,
            ],
            'user.name' => [
                'label' => 'المستخدم',
            ],
            'action' => [
                'label' => 'الإجراء',
                'type' => 'badge',
                'filter' => true,
            ],
            'from_status' => [
                'label' => 'من',
                'map' => [
                    'draft' => 'مسودة',
                    'pending_assignment' => 'بانتظار التوجيه',
                    'assigned' => 'موجّه',
                    'data_entry' => 'إدخال بيانات',
                    'under_review' => 'قيد المراجعة',
                    'revision_required' => 'مطلوب تعديل',
                    'approved' => 'معتمد',
                    'rejected' => 'مرفوض',
                    'report_pending' => 'بانتظار التقرير',
                    'report_draft' => 'مسودة تقرير',
                    'report_approved' => 'تقرير معتمد',
                    'report_sent' => 'تقرير مُرسل',
                ],
            ],
            'to_status' => [
                'label' => 'إلى',
                'map' => [
                    'draft' => 'مسودة',
                    'pending_assignment' => 'بانتظار التوجيه',
                    'assigned' => 'موجّه',
                    'data_entry' => 'إدخال بيانات',
                    'under_review' => 'قيد المراجعة',
                    'revision_required' => 'مطلوب تعديل',
                    'approved' => 'معتمد',
                    'rejected' => 'مرفوض',
                    'report_pending' => 'بانتظار التقرير',
                    'report_draft' => 'مسودة تقرير',
                    'report_approved' => 'تقرير معتمد',
                    'report_sent' => 'تقرير مُرسل',
                ],
            ],
            'created_at' => [
                'label' => 'التاريخ',
                'type' => 'datetime',
            ],
            'notes' => [
                'label' => 'ملاحظات',
                'type' => 'long',
            ],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowLogs::route('/'),
            'view' => Pages\ViewWorkflowLog::route('/{record}'),
        ];
    }
}
