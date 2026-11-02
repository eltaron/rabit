<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormSubmissionResource\Pages;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Builder;

class FormSubmissionResource extends ReadOnlyResource
{
    protected static ?string $model = FormSubmission::class;

    protected static ?string $modelLabel = 'إرسال';

    protected static ?string $pluralModelLabel = 'إرسالات النماذج';

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'النماذج والتقارير';

    protected static ?int $navigationSort = 2;

    public static function fields(): array
    {
        return [
            'reference_number' => [
                'label' => 'الرقم المرجعي',
                'search' => true,
            ],
            'formTemplate.name' => [
                'label' => 'النموذج',
                'filter' => true,
            ],
            'status' => [
                'label' => 'الحالة',
                'type' => 'badge',
                'filter' => [
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
                'colors' => [
                    'approved' => 'success',
                    'report_approved' => 'success',
                    'report_sent' => 'success',
                    'rejected' => 'danger',
                    'revision_required' => 'warning',
                    'under_review' => 'info',
                    'assigned' => 'primary',
                    'draft' => 'gray',
                ],
            ],
            'createdBy.name' => [
                'label' => 'أنشأه',
            ],
            'assignedTo.name' => [
                'label' => 'موجّه إلى',
            ],
            'orgUnit.name' => [
                'label' => 'الجهة',
                'hidden' => true,
            ],
            'submission_data_count' => [
                'label' => 'عدد القيم',
                'type' => 'number',
            ],
            'revision_count' => [
                'label' => 'مرات التعديل',
                'type' => 'number',
                'hidden' => true,
            ],
            'submitted_at' => [
                'label' => 'تاريخ الإرسال',
                'type' => 'datetime',
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
            'revision_notes' => [
                'label' => 'ملاحظات التعديل',
                'type' => 'long',
            ],
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('submissionData');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormSubmissions::route('/'),
            'view' => Pages\ViewFormSubmission::route('/{record}'),
        ];
    }
}
