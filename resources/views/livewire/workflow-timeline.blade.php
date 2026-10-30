<div class="workflow-timeline">
    @if (count($logs) > 0)
        <ol class="list-unstyled position-relative mb-0" style="padding-right:34px">
            <span class="position-absolute top-0 bottom-0" style="right:13px;width:1px;background:var(--egypt-gold-line)" aria-hidden="true"></span>
            @foreach ($logs as $log)
                @php
                    $color = [
                        'success' => 'var(--success)', 'warning' => 'var(--warning)', 'danger' => 'var(--danger)',
                        'info' => 'var(--info)', 'primary' => 'var(--egypt-green)', 'secondary' => 'var(--muted)',
                    ][$this->getStatusColor($log['to_status'] ?? $log['from_status'] ?? '')] ?? 'var(--egypt-green)';
                @endphp
                <li class="position-relative mb-3">
                    <span class="position-absolute d-flex align-items-center justify-content-center"
                          style="right:-34px;top:2px;width:27px;height:27px;border-radius:50%;background:var(--white-soft);border:1px solid {{ $color }};color:{{ $color }};font-size:.7rem">
                        <i class="fas {{ $this->getActionIcon($log['action']) }}"></i>
                    </span>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw-bold text-green" style="font-size:.86rem">{{ $this->getActionLabel($log['action']) }}</span>
                        <small class="text-muted" title="{{ \Carbon\Carbon::parse($log['created_at'])->format('Y-m-d H:i') }}">{{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }}</small>
                    </div>
                    <div class="small text-muted">
                        {{ $log['user']['name'] ?? 'النظام' }}
                        @if (! empty($log['to_status']))
                            · {{ $this->getStatusLabel($log['to_status']) }}
                        @endif
                    </div>
                    @if (! empty($log['notes']))
                        <div class="mt-1 p-2 small" style="background:var(--papyrus);border-radius:var(--r);border-right:2px solid var(--egypt-gold-line)">{{ $log['notes'] }}</div>
                    @endif
                </li>
            @endforeach
        </ol>
    @else
        <div class="empty-state py-3">
            <span class="eg-icon"><i class="fas fa-clock-rotate-left"></i></span>
            <p class="mb-0 mt-2 small">لا يوجد سجل حركات بعد</p>
        </div>
    @endif
</div>
