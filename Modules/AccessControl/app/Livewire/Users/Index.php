<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\User;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read \Illuminate\Pagination\LengthAwarePaginator $users
 * @property-read array<string, array{user_id: string, user_name: string}> $lockedIds
 */
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(except: 'name')]
    public string $sortBy = 'name';

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
     * Tenant ID captured on mount and persisted in the Livewire snapshot.
     * Using this directly in queries instead of tenancy()->initialized avoids
     * a race window where the computed property runs before tenancy is restored.
     */
    public ?string $tenantId = null;

    /**
     * Dynamic filter rows: each row = [col, op, val].
     *
     * @var array<int, array{col: string, op: string, val: string}>
     */
    public array $filterRows = [];

    public function mount(): void
    {
        $this->tenantId = tenancy()->initialized ? tenant('id') : null;
        $this->authorize('access-control.users.index');
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
            ? $this->users->pluck('id')->map(fn ($id) => (string) $id)->toArray()
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
        $this->sortBy = 'name';
        $this->sortDir = 'asc';
        $this->resetPage();
    }

    public function openShow(string $id): void
    {
        $this->authorize('access-control.users.show');
        $this->dispatch('open-show-user', id: $id);
    }

    public function openHistory(string $id): void
    {
        $this->authorize('access-control.users.history');
        $this->dispatch('open-history-user', id: $id);
    }

    public function openCreate(): void
    {
        $this->authorize('access-control.users.create');
        $this->dispatch('open-create-user');
    }

    public function openImportExport(): void
    {
        $this->authorize('access-control.users.import-export');
        $this->dispatch('open-import-export-users');
    }

    public function openEdit(string $id): void
    {
        $this->authorize('access-control.users.edit');

        if (RecordLock::isLockedByOther('user', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('user', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-edit-user', id: $id);
    }

    public function openDelete(string $id): void
    {
        $this->authorize('access-control.users.destroy');

        if (RecordLock::isLockedByOther('user', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('user', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-delete-user', id: $id);
    }

    public function openImpersonate(string $id): void
    {
        $this->authorize('access-control.users.impersonate');
        $this->dispatch('open-impersonate-user', id: $id);
    }

    public function openBulkDelete(): void
    {
        $this->authorize('access-control.users.destroy');
        $this->dispatch('open-bulk-delete-users', ids: $this->selected);
    }

    public function openRestore(string $id): void
    {
        $this->authorize('access-control.users.restore');
        $this->dispatch('open-restore-user', id: $id);
    }

    public function openForceDelete(string $id): void
    {
        $this->authorize('access-control.users.force-delete');
        $this->dispatch('open-force-delete-user', id: $id);
    }

    #[On('user-saved')]
    public function onUserSaved(): void
    {
        $this->resetPage();
    }

    #[On('users-bulk-deleted')]
    public function onBulkDeleted(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    #[On('user-record-changed-broadcast')]
    public function onUserRecordChanged(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'info', message: __('Data was updated by another user.'));
    }

    #[Computed]
    public function lockedIds(): array
    {
        $ids = $this->users->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        return RecordLock::getLockedByOthers('user', $ids, (string) auth()->id());
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $builtFilters = [];

        foreach ($this->filterRows as $row) {
            if ($row['col'] === '') {
                continue;
            }

            $isNullOp = in_array($row['op'], ['$null', '$notNull']);
            $isListOp = in_array($row['op'], ['$in', '$notIn', '$between', '$notBetween']);

            if (! $isNullOp && trim($row['val']) === '') {
                continue;
            }

            $builtFilters[$row['col']] = [
                $row['op'] => $isNullOp
                    ? true
                    : ($isListOp
                        ? array_map('trim', explode(',', $row['val']))
                        : $row['val']),
            ];
        }

        return User::query()
            ->when($this->tab === 'trash', fn ($q) => $q->onlyTrashed())
            ->when(
                $this->tenantId,
                fn ($q) => $q->whereHas('tenants', fn ($q) => $q->where('tenant_id', $this->tenantId)),
            )
            ->filter($builtFilters)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->sort(["{$this->sortBy}:{$this->sortDir}"])
            ->with(['roles' => fn ($q) => $this->tenantId ? $q->forTenant($this->tenantId) : $q->central()])
            ->paginate($this->perPage)
            ->onEachSide(1);
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.index');
    }
}
