{{-- شريط إحصاءات حكومي: $items = [[icon, label, value, href?, hint?], ...] --}}
<div class="gov-stats mb-4" data-reveal>
    @foreach ($items as $it)
        @php $tag = ! empty($it[3]) ? 'a' : 'div'; @endphp
        <{{ $tag }} class="gs-item" @if (! empty($it[3])) href="{{ $it[3] }}" @endif>
            <div class="gs-icon"><i class="fas {{ $it[0] }}"></i></div>
            <div class="gs-num" data-count="{{ (int) $it[2] }}">{{ number_format((int) $it[2]) }}</div>
            <div class="gs-label">{{ $it[1] }}</div>
            @if (! empty($it[4]))<div class="gs-hint">{{ $it[4] }}</div>@endif
        </{{ $tag }}>
    @endforeach
</div>
