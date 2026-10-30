<?php

namespace App\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithPagination;

    public array $data = [];
    public array $columns = [];
    public string $search = '';
    public string $sortField = '';
    public string $sortDirection = 'asc';
    public int $perPage = 15;
    public string $modelClass = '';
    public string $emptyMessage = 'لا توجد بيانات';
    public array $actions = [];
    public ?string $searchPlaceholder = 'بحث...';
    public int $totalResults = 0;

    protected array $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => ''],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 15],
    ];

    protected array $rules = [
        'search' => 'nullable|string|max:255',
        'sortField' => 'nullable|string|max:255',
        'sortDirection' => 'in:asc,desc',
        'perPage' => 'integer|in:10,15,25,50,100',
    ];

    public function mount(
        string $modelClass = '',
        array $columns = [],
        array $actions = [],
        ?string $searchPlaceholder = null,
        int $perPage = 15
    ): void {
        $this->modelClass = $modelClass;
        $this->columns = $columns;
        $this->actions = $actions;
        $this->searchPlaceholder = $searchPlaceholder ?? 'بحث...';
        $this->perPage = $perPage;

        if (! empty($columns) && ! isset($columns[0]['field'])) {
            $firstColumn = reset($columns);
            $this->sortField = $firstColumn['field'] ?? '';
        }

        $this->loadData();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadData();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadData();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->loadData();
    }

    public function loadData(): void
    {
        if (! $this->modelClass || ! class_exists($this->modelClass)) {
            $this->data = [];
            $this->totalResults = 0;
            return;
        }

        /** @var Builder $query */
        $query = $this->modelClass::query();

        if ($this->search) {
            $searchableColumns = collect($this->columns)
                ->where('searchable', true)
                ->pluck('field')
                ->toArray();

            if (! empty($searchableColumns)) {
                $query->where(function ($q) use ($searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$this->search}%");
                    }
                });
            }
        }

        if ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $this->totalResults = $query->count();

        $paginator = $query->paginate($this->perPage);
        $this->data = $paginator->items();
        $this->resetPage();
    }

    public function getRows(): LengthAwarePaginator
    {
        if (! $this->modelClass || ! class_exists($this->modelClass)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        /** @var Builder $query */
        $query = $this->modelClass::query();

        if ($this->search) {
            $searchableColumns = collect($this->columns)
                ->where('searchable', true)
                ->pluck('field')
                ->toArray();

            if (! empty($searchableColumns)) {
                $query->where(function ($q) use ($searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$this->search}%");
                    }
                });
            }
        }

        if ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.data-table');
    }
}
