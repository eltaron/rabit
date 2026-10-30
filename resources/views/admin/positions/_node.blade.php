<li>
    <div class="pos-card cat-{{ $p->category }} {{ $p->user_id ? '' : 'vacant' }}">
        <div class="t">{{ $p->title }}</div>
        @if ($p->holder)
            <div class="h"><i class="fas fa-user ml-1 text-gold"></i>{{ $p->holder->name }}</div>
        @else
            <div class="h vac"><i class="fas fa-user-clock ml-1"></i>شاغرة</div>
        @endif
        <div class="meta">{{ $p->category_label }}@if ($p->grade) · {{ $p->grade }}@endif @if ($p->headcount > 1) · {{ $p->headcount }} درجات @endif</div>
        @if ($p->parent && $p->parent->org_unit_id !== $p->org_unit_id)
            <div class="ext"><i class="fas fa-arrow-up ml-1"></i>يتبع: {{ $p->parent->title }} — {{ $p->parent->orgUnit->name ?? '' }}</div>
        @endif
        <div class="acts">
            <button type="button" title="إضافة وظيفة تابعة" aria-label="إضافة وظيفة تابعة" onclick="openPosition(null, {parent_id: {{ $p->id }}})"><i class="fas fa-plus"></i></button>
            <button type="button" title="تسكين" aria-label="تسكين" onclick="openAssign({{ $p->id }}, @js($p->title), {{ $p->user_id ?? 'null' }})"><i class="fas fa-user-plus"></i></button>
            <button type="button" title="تعديل" aria-label="تعديل" onclick="openPosition({{ json_encode($p->only(['id','title','code','grade','category','parent_id','user_id','headcount','description','sort_order']), JSON_UNESCAPED_UNICODE) }})"><i class="fas fa-pen"></i></button>
            <button type="button" title="حذف" aria-label="حذف" onclick="confirmDelete('{{ route('admin.positions.destroy', $p) }}', @js($p->title))"><i class="fas fa-trash"></i></button>
        </div>
    </div>
    @if (! empty($byParent[$p->id]))
        <ul>
            @foreach ($byParent[$p->id] as $child)
                @include('admin.positions._node', ['p' => $child])
            @endforeach
        </ul>
    @endif
</li>
