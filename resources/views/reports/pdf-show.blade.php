<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->title }} - تقرير</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Tajawal', 'Arial', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        body { padding: 30px; color: #202522; line-height: 1.8; background: #FAF8F2; }

        .print-bar {
            position: fixed; top: 0; left: 0; right: 0;
            background: #0A2924; color: #FEFDFC; border-bottom: 1px solid #B8954F;
            padding: 12px 24px;
            display: flex; align-items: center; justify-content: space-between;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .print-bar .title { font-size: 14px; font-weight: 600; }
        .print-bar .hint { font-size: 12px; color: rgba(255,255,255,0.6); }
        .print-bar .btn-print {
            background: #B8954F; color: #123F35; border: none;
            padding: 8px 20px; border-radius: 6px; font-weight: 700;
            font-size: 13px; cursor: pointer; font-family: inherit;
            display: flex; align-items: center; gap: 6px;
        }
        .print-bar .btn-print:hover { background: #D1B574; }
        .print-bar .btn-back {
            background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px; border-radius: 6px; font-weight: 600;
            font-size: 13px; cursor: pointer; font-family: inherit;
            text-decoration: none; display: flex; align-items: center; gap: 6px;
        }
        .print-bar .btn-back:hover { background: rgba(255,255,255,0.1); }

        .page { max-width: 900px; margin: 70px auto 0; background: #fff; padding: 40px 50px; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }

        .header { text-align: center; border-bottom: 3px solid #123F35; padding-bottom: 20px; margin-bottom: 25px; }
        .header h1 { color: #123F35; font-size: 24px; margin-bottom: 5px; }
        .header .subtitle { color: #B8954F; font-size: 13px; font-weight: 700; }
        .header p { color: #6b7280; font-size: 12px; }
        .meta { display: flex; justify-content: space-between; background: #f8f9fa; padding: 14px 20px; border-radius: 10px; margin-bottom: 25px; font-size: 13px; }
        .meta div { flex: 1; }
        .meta strong { color: #123F35; }
        .section { margin-bottom: 25px; }
        .section h2 { color: #123F35; font-size: 16px; border-bottom: 2px solid #B8954F; padding-bottom: 8px; margin-bottom: 14px; }
        .content-box { background: #f8f9fb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px; font-size: 14px; white-space: pre-wrap; line-height: 2; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 15px; }
        th { background: #0A2924; color: #FEFDFC; border-bottom: 1px solid #B8954F; padding: 10px 12px; text-align: right; }
        td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f8f9fb; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-draft { background: #e5e7eb; color: #6b7280; }
        .badge-approved, .badge-report_approved, .badge-report_sent { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .badge-pending, .badge-assigned, .badge-data_entry { background: #fef3c7; color: #92400e; }
        .badge-under_review, .badge-revision_required { background: #dbeafe; color: #1e40af; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 3px solid #123F35; font-size: 11px; color: #6b7280; }

        @media print {
            .print-bar { display: none !important; }
            body { background: #fff; padding: 0; }
            .page { margin: 0; padding: 20px 30px; box-shadow: none; border-radius: 0; max-width: 100%; }
            .table-responsive { overflow: visible !important; }
            table { width: 100% !important; font-size: 11px !important; }
            th, td { padding: 5px 8px !important; white-space: nowrap; }
            @page { margin: 12mm; size: landscape; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <div>
            <div class="title">معاينة التقرير للطباعة</div>
            <div class="hint">اضغط Ctrl+P للطباعة أو استخدم الزر</div>
        </div>
        <div class="d-flex gap-2" style="display:flex;gap:8px;">
            <a href="{{ url()->previous() }}" class="btn-back">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> طباعة
            </button>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <div class="subtitle">منصة رابط — محافظة كفر الشيخ</div>
            <h1>{{ $report->title }}</h1>
            <p>رقم التقرير: #{{ $report->id }} | الإصدار: v{{ $report->current_version ?? 1 }}</p>
        </div>

        <div class="meta">
            <div><strong>الحالة:</strong>
                <span class="badge badge-{{ $report->status }}">
                    @if($report->status == 'draft') مسودة
                    @elseif($report->status == 'approved') موافق عليه
                    @elseif($report->status == 'rejected') مرفوض
                    @elseif($report->status == 'sent') مرسل
                    @else {{ $report->status }}
                    @endif
                </span>
            </div>
            <div><strong>أنشأه:</strong> {{ $report->createdBy->name ?? '-' }}</div>
            <div><strong>التاريخ:</strong> {{ $report->created_at->format('d/m/Y H:i') }}</div>
        </div>

        @if($report->formSubmission)
        <div class="section">
            <h2>بيانات المرفوع</h2>
            <p style="font-size:13px;margin-bottom:10px;"><strong>القالب:</strong> {{ $report->formSubmission->formTemplate->name ?? '-' }}</p>
            @if($rows->count() && count($headers))
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:45px;">#</th>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $rowIndex => $row)
                            <tr>
                                <td style="text-align:center;color:#6b7280;">{{ $rowIndex + 1 }}</td>
                                @foreach($headers as $header)
                                    <td>{{ $row[$header] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        @if($report->content)
        <div class="section">
            <h2>محتوى التقرير</h2>
            <div class="content-box">{{ $report->content }}</div>
        </div>
        @endif

        @if($report->ai_generated_content)
        <div class="section">
            <h2>محتوى الذكاء الاصطناعي</h2>
            <div class="content-box">{{ $report->ai_generated_content }}</div>
        </div>
        @endif

        @if($report->rejection_notes)
        <div class="section">
            <h2>ملاحظات الرفض</h2>
            <div class="content-box">{{ $report->rejection_notes }}</div>
        </div>
        @endif

        <div class="footer">
            <p>تم التصدير في: {{ $generatedAt }}</p>
            <p>منصة رابط — محافظة كفر الشيخ - {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
