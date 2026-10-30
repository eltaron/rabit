<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير التقارير - كفر الشيخ</title>
    <style>
        * { font-family: 'Arial', 'Tahoma', sans-serif; margin: 0; padding: 0; }
        body { padding: 20px; color: #1a1a2e; }
        .header { text-align: center; border-bottom: 3px solid #123F35; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #123F35; font-size: 22px; }
        .header p { color: #6b7280; font-size: 11px; margin-top: 5px; }
        .filters { background: #f8f9fa; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #123F35; color: #fff; padding: 8px 10px; text-align: right; font-weight: 600; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f8f9fb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .badge-draft { background: #e5e7eb; color: #6b7280; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 2px solid #123F35; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير شامل - منصة رابط — محافظة كفر الشيخ</h1>
        <p>تاريخ التصدير: {{ $generatedAt }} | عدد التقارير: {{ $reports->count() }}</p>
    </div>

    @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['date_from']) || !empty($filters['date_to']))
    <div class="filters">
        <strong>الفلاتر المطبقة:</strong>
        @if(!empty($filters['search'])) | البحث: {{ $filters['search'] }} @endif
        @if(!empty($filters['status'])) | الحالة: {{ $filters['status'] }} @endif
        @if(!empty($filters['date_from'])) | من تاريخ: {{ $filters['date_from'] }} @endif
        @if(!empty($filters['date_to'])) | إلى تاريخ: {{ $filters['date_to'] }} @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>عنوان التقرير</th>
                <th>القالب</th>
                <th>الحالة</th>
                <th>الإصدار</th>
                <th>أنشأه</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $index => $report)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $report->title }}</td>
                <td>{{ $report->formSubmission->formTemplate->name ?? '-' }}</td>
                <td>
                    <span class="badge badge-{{ $report->status }}">
                        @if($report->status == 'draft') مسودة
                        @elseif($report->status == 'approved') موافق عليه
                        @elseif($report->status == 'rejected') مرفوض
                        @elseif($report->status == 'sent') مرسل
                        @else {{ $report->status }}
                        @endif
                    </span>
                </td>
                <td>v{{ $report->current_version ?? 1 }}</td>
                <td>{{ $report->createdBy->name ?? '-' }}</td>
                <td>{{ $report->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;">لا توجد تقارير</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>منصة رابط — محافظة كفر الشيخ - {{ date('Y') }}</p>
    </div>
</body>
</html>
