@php
    $steps = $timeline ?? [
        ['key' => 'draft', 'label' => 'إنشاء', 'icon' => 'fas fa-plus'],
        ['key' => 'data_entry', 'label' => 'إدخال البيانات', 'icon' => 'fas fa-keyboard'],
        ['key' => 'under_review', 'label' => 'المراجعة', 'icon' => 'fas fa-magnifying-glass'],
        ['key' => 'approved', 'label' => 'الاعتماد', 'icon' => 'fas fa-circle-check'],
    ];
    $currentStatus = $currentStatus ?? 'draft';
    $aliases = ['assigned' => 'data_entry', 'pending_assignment' => 'draft', 'revision_required' => 'data_entry', 'in_review' => 'under_review', 'pending' => 'draft'];
    $key = $aliases[$currentStatus] ?? $currentStatus;
    $currentIndex = array_search($key, array_column($steps, 'key'));
    if ($currentIndex === false) $currentIndex = count($steps) - 1;
@endphp

<div class="workflow-steps d-flex align-items-start justify-content-between position-relative mb-3" role="list" aria-label="مراحل سير العمل">
    <span class="position-absolute start-0 end-0" style="top:19px;height:1px;background:var(--egypt-gold-line)" aria-hidden="true"></span>
    @foreach ($steps as $index => $step)
        @php $done = $index <= $currentIndex; @endphp
        <div class="text-center position-relative flex-fill" role="listitem" @if ($index === $currentIndex) aria-current="step" @endif>
            <span class="d-inline-flex align-items-center justify-content-center"
                  style="width:38px;height:38px;border-radius:50%;{{ $done ? 'background:var(--egypt-green);color:var(--egypt-gold-2);border:1px solid var(--egypt-gold);' : 'background:var(--white-soft);color:var(--muted);border:1px solid var(--line);' }}">
                <i class="{{ $step['icon'] }}" style="font-size:.85rem"></i>
            </span>
            <div class="mt-1 small {{ $done ? 'fw-bold text-green' : 'text-muted' }}">{{ $step['label'] }}</div>
            @if ($step['date'] ?? null)<div class="text-muted" style="font-size:.7rem">{{ \Carbon\Carbon::parse($step['date'])->format('Y-m-d') }}</div>@endif
            @if ($step['user'] ?? null)<div class="text-muted" style="font-size:.7rem">{{ $step['user'] }}</div>@endif
        </div>
    @endforeach
</div>
