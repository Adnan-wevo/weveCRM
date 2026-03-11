<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Role;
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
 * @property-read \Illuminate\Pagination\LengthAwarePaginator $roles
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
     * @var array<int, array{col: string, op: string, val: string}>
     */
    public array $filterRows = [];

    public function mount(): void
    {
        $this->tenantId = tenancy()->initialized ? tenant('id') : null;
        $this->authorize('access-control.roles.index');
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
            ? $this->roles->pluck('id')->map(fn ($id) => (string) $id)->toArray()
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
        $this->authorize('access-control.roles.show');
        $this->dispatch('open-show-role', id: $id);
    }

    public function openHistory(string $id): void
    {
        $this->authorize('access-control.roles.history');
        $this->dispatch('open-history-role', id: $id);
    }

    public function openCreate(): void
    {
        $this->authorize('access-control.roles.create');
        $this->dispatch('open-create-role');
    }

    public function openImportExport(): void
    {
        $this->authorize('access-control.roles.import-export');
        $this->dispatch('open-import-export-roles');
    }

    public function openEdit(string $id): void
    {
        $this->authorize('access-control.roles.edit');

        $role = Role::findOrFail($id);

        if ($role->is_synced) {
            $this->dispatch('notify', type: 'warning', message: __('Synced roles cannot be modified.'));

            return;
        }

        if (RecordLock::isLockedByOther('role', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('role', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-edit-role', id: $id);
    }

    public function openDelete(string $id): void
    {
        $this->authorize('access-control.roles.destroy');

        $role = Role::findOrFail($id);

        if ($role->is_synced) {
            $this->dispatch('notify', type: 'warning', message: __('Synced roles cannot be deleted.'));

            return;
        }

        if (RecordLock::isLockedByOther('role', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('role', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-delete-role', id: $id);
    }

    public function openBulkDelete(): void
    {
        $this->authorize('access-control.roles.destroy');

        $ids = Role::whereIn('id', $this->selected)->where('is_synced', false)->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        if (empty($ids)) {
            $this->dispatch('notify', type: 'warning', message: __('No deletable roles selected. Synced roles cannot be deleted.'));

            return;
        }

        if (count($ids) < count($this->selected)) {
            $this->dispatch('notify', type: 'info', message: __('Synced roles were excluded from selection.'));
        }

        $this->dispatch('open-bulk-delete-roles', ids: $ids);
    }

    public function openRestore(string $id): void
    {
        $this->authorize('access-control.roles.restore');
        $this->dispatch('open-restore-role', id: $id);
    }

    public function openForceDelete(string $id): void
    {
        $this->authorize('access-control.roles.force-delete');

        $role = Role::withTrashed()->findOrFail($id);

        if ($role->is_synced) {
            $this->dispatch('notify', type: 'warning', message: __('Synced roles cannot be deleted.'));

            return;
        }

        $this->dispatch('open-force-delete-role', id: $id);
    }

    #[On('role-saved')]
    public function onRoleSaved(): void
    {
        $this->resetPage();
    }

    #[On('roles-bulk-deleted')]
    public function onBulkDeleted(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    #[On('role-record-changed-broadcast')]
    public function onRoleRecordChanged(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'info', message: __('Data was updated by another user.'));
    }

    #[Computed]
    public function lockedIds(): array
    {
        $ids = $this->roles->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        return RecordLock::getLockedByOthers('role', $ids, (string) auth()->id());
    }

    #[Computed]
    public function roles(): LengthAwarePaginator
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

        return Role::query()
            ->when(
                $this->tenantId,
                fn ($q) => $q->forTenant($this->tenantId),
                fn ($q) => $q->central(),
            )
            ->when($this->tab === 'trash', fn ($q) => $q->onlyTrashed())
            ->filter($builtFilters)
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->sort(["{$this->sortBy}:{$this->sortDir}"])
            ->withCount('permissions', 'users')
            ->paginate($this->perPage)
            ->onEachSide(1);
    }

    /**
     * Sync all central template roles + permissions to every existing tenant.
     * Central only — opens the SyncModal component.
     */
    public function openSync(): void
    {
        $this->authorize('access-control.roles.sync');
        $this->dispatch('open-sync-roles');
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.index');
    }
}
