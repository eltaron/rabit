<div wire:poll.60s="refreshStats">
    @php
        $items = [
            ['fa-inbox', 'إجمالي الإرسالات', 'total_submissions'],
            ['fa-magnifying-glass', 'قيد المراجعة', 'pending_review'],
            ['fa-circle-check', 'معتمدة', 'approved'],
            ['fa-circle-xmark', 'مرفوضة', 'rejected'],
            ['fa-rotate-left', 'مطلوب تعديلها', 'revision_required'],
            ['fa-calendar-check', 'أُنجزت اليوم', 'completed_today'],
        ];
    @endphp
    <div class="gov-stats">
        @foreach ($items as [$icon, $label, $key])
            <div class="gs-item">
                <div class="gs-icon"><i class="fas {{ $icon }}"></i></div>
                <div class="gs-num">{{ number_format($stats[$key] ?? 0) }}</div>
                <div class="gs-label">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</div>
