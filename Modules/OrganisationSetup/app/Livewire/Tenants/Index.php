<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read \Illuminate\Pagination\LengthAwarePaginator $tenants
 * @property-read array<string, array{user_id: string, user_name: string}> $lockedIds
 */
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    /** Maps virtual column names used in UI to actual DB columns. */
    private const COLUMN_MAP = [
        'name' => 'data->name',
        'id' => 'id',
        'created_at' => 'created_at',
    ];

    #[Url(except: 'id')]
    public string $sortBy = 'id';

    #[Url(except: 'asc')]
    public string $sortDir = 'asc';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'active')]
    public string $tab = 'active';

    public int $perPage = 10;

    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    /**
     * @var array<int, array{col: string, op: string, val: string}>
     */
    public array $filterRows = [];

    public function mount(): void
    {
        $this->authorize('organisation-setup.tenants.index');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRows(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->tenants->pluck('id')->toArray()
            : [];
    }

    public function addFilterRow(): void
    {
        $this->filterRows[] = ['col' => 'name', 'op' => '$contains', 'val' => ''];
    }

    public function removeFilterRow(int $index): void
    {
        unset($this->filterRows[$index]);
        $this->filterRows = array_values($this->filterRows);
        $this->resetPage();
    }

    public function resetFiltersAndSort(): void
    {
        $this->filterRows = [];
        $this->search = '';
        $this->sortBy = 'id';
        $this->sortDir = 'asc';
        $this->resetPage();
    }

    public function openTenantUsers(string $id): void
    {
        $this->authorize('organisation-setup.tenants.add-user');
        $this->dispatch('open-tenant-users', id: $id);
    }

    public function openAssignDomain(string $id): void
    {
        $this->authorize('organisation-setup.tenants.assign-domain');
        $this->dispatch('open-assign-domain', id: $id);
    }

    public function openShow(string $id): void
    {
        $this->authorize('organisation-setup.tenants.show');
        $this->dispatch('open-show-tenant', id: $id);
    }

    public function openHistory(string $id): void
    {
        $this->authorize('organisation-setup.tenants.history');
        $this->dispatch('open-history-tenant', id: $id);
    }

    public function openCreate(): void
    {
        $this->authorize('organisation-setup.tenants.create');
        $this->dispatch('open-create-tenant');
    }

    public function openImportExport(): void
    {
        $this->authorize('organisation-setup.tenants.import-export');
        $this->dispatch('open-import-export-tenants');
    }

    public function openSyncMigrations(): void
    {
        $this->authorize('organisation-setup.tenants.sync-migrations');
        $this->dispatch('open-sync-migrations');
    }

    public function openEdit(string $id): void
    {
        $this->authorize('organisation-setup.tenants.edit');

        if (RecordLock::isLockedByOther('tenant', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('tenant', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-edit-tenant', id: $id);
    }

    public function openDelete(string $id): void
    {
        $this->authorize('organisation-setup.tenants.destroy');

        if (RecordLock::isLockedByOther('tenant', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('tenant', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-delete-tenant', id: $id);
    }

    public function openBulkDelete(): void
    {
        $this->authorize('organisation-setup.tenants.destroy');
        $this->dispatch('open-bulk-delete-tenants', ids: $this->selected);
    }

    public function openRestore(string $id): void
    {
        $this->authorize('organisation-setup.tenants.restore');
        $this->dispatch('open-restore-tenant', id: $id);
    }

    public function openForceDelete(string $id): void
    {
        $this->authorize('organisation-setup.tenants.force-delete');
        $this->dispatch('open-force-delete-tenant', id: $id);
    }

    #[On('tenant-saved')]
    public function onTenantSaved(): void
    {
        $this->resetPage();
    }

    #[On('tenants-bulk-deleted')]
    public function onBulkDeleted(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    #[On('tenant-record-changed-broadcast')]
    public function onTenantRecordChanged(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'info', message: __('Data was updated by another user.'));
    }

    #[Computed]
    public function lockedIds(): array
    {
        $ids = $this->tenants->pluck('id')->toArray();

        return RecordLock::getLockedByOthers('tenant', $ids, (string) auth()->id());
    }

    #[Computed]
    public function tenants(): LengthAwarePaginator
    {
        $query = Tenant::query()
            ->when($this->tab === 'trash', fn ($q) => $q->onlyTrashed());

        // Column filters
        foreach ($this->filterRows as $row) {
            if ($row['col'] === '') {
                continue;
            }

            $isNullOp = in_array($row['op'], ['$null', '$notNull']);

            if (! $isNullOp && trim($row['val']) === '') {
                continue;
            }

            $dbCol = self::COLUMN_MAP[$row['col']] ?? $row['col'];
            $this->applyFilterOperator($query, $dbCol, $row['op'], $row['val']);
        }

        // Global search across ID and name
        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('id', 'like', "%{$this->search}%")
                ->orWhere('data->name', 'like', "%{$this->search}%"));
        }

        // Sort with JSON column mapping
        $sortCol = self::COLUMN_MAP[$this->sortBy] ?? $this->sortBy;
        $query->orderBy($sortCol, $this->sortDir);

        return $query->paginate($this->perPage);
    }

    private function applyFilterOperator(Builder $query, string $col, string $op, string $val): void
    {
        $isListOp = in_array($op, ['$in', '$notIn', '$between', '$notBetween']);
        $listValues = $isListOp ? array_map('trim', explode(',', $val)) : [];

        match ($op) {
            '$eq', '$eqc' => $query->where($col, $val),
            '$ne' => $query->where($col, '!=', $val),
            '$lt' => $query->where($col, '<', $val),
            '$lte' => $query->where($col, '<=', $val),
            '$gt' => $query->where($col, '>', $val),
            '$gte' => $query->where($col, '>=', $val),
            '$contains', '$containsc' => $query->where($col, 'like', "%{$val}%"),
            '$notContains', '$notContainsc' => $query->where($col, 'not like', "%{$val}%"),
            '$startsWith', '$startsWithc' => $query->where($col, 'like', "{$val}%"),
            '$endsWith', '$endsWithc' => $query->where($col, 'like', "%{$val}"),
            '$in' => $query->whereIn($col, $listValues),
            '$notIn' => $query->whereNotIn($col, $listValues),
            '$between' => $query->whereBetween($col, [$listValues[0] ?? '', $listValues[1] ?? '']),
            '$notBetween' => $query->whereNotBetween($col, [$listValues[0] ?? '', $listValues[1] ?? '']),
            '$null' => $query->whereNull($col),
            '$notNull' => $query->whereNotNull($col),
            default => null,
        };
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.index');
    }
}
