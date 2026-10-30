{{-- ترحيب + نطاق البيانات --}}
<div class="dash-greet" data-reveal>
    <div class="section-head mb-0">
        <div class="sh-line"><span class="sh-num">{{ $num ?? '01' }}</span></div>
        <h2>{{ $greeting }}، {{ $user->name }}</h2>
        <p>{{ $subtitle ?? 'ملخص البيانات والمهام ضمن نطاق صلاحياتك' }}</p>
    </div>
    <div class="meta">
        <span><i class="fas fa-user-tie"></i>{{ $roleLabel }}</span>
        @if ($unit)<span><i class="fas {{ $unit->icon }}"></i>{{ $unit->name }}</span>@endif
        <span><i class="fas fa-eye"></i>نطاق البيانات: {{ $scopeLabel }}</span>
    </div>
</div>
