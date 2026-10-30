<div class="card-kfs">
    @php $rows = $this->getRows(); @endphp
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="input-group" style="max-width:360px">
            <span class="input-group-text"><i class="fas fa-magnifying-glass"></i></span>
            <input type="search" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ $searchPlaceholder }}" aria-label="بحث">
        </div>
        <select class="form-select w-auto" wire:model.live="perPage" aria-label="عدد الصفوف">
            @foreach ([10, 15, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} صف</option>@endforeach
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-kfs mb-0">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        <th>
                            @if (! empty($col['sortable']))
                                <button type="button" class="btn btn-link p-0 text-reset fw-semibold text-decoration-none" wire:click="sortBy('{{ $col['field'] }}')">
                                    {{ $col['label'] ?? $col['field'] }}
                                    @if ($sortField === $col['field'])<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} mr-1"></i>@endif
                                </button>
                            @else
                                {{ $col['label'] ?? $col['field'] }}
                            @endif
                        </th>
                    @endforeach
                    @if (! empty($actions))<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($columns as $col)
                            <td>{{ data_get($row, $col['field']) ?? '—' }}</td>
                        @endforeach
                        @if (! empty($actions))
                            <td class="text-nowrap">
                                @foreach ($actions as $a)
                                    @if (! empty($a['route']) && Route::has($a['route']))
                                        <a href="{{ route($a['route'], $row) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="{{ $a['label'] ?? '' }}"><i class="fas {{ $a['icon'] ?? 'fa-eye' }}"></i></a>
                                    @endif
                                @endforeach
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + (empty($actions) ? 0 : 1) }}">
                        <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-inbox"></i></span><p class="small mb-0">{{ $emptyMessage }}</p></div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
