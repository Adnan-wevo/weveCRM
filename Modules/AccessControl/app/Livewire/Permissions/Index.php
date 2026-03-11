<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
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
 * @property-read \Illuminate\Pagination\LengthAwarePaginator $permissions
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
        $this->authorize('access-control.permissions.index');
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
            ? $this->permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray()
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
        $this->authorize('access-control.permissions.show');
        $this->dispatch('open-show-permission', id: $id);
    }

    public function openHistory(string $id): void
    {
        $this->authorize('access-control.permissions.history');
        $this->dispatch('open-history-permission', id: $id);
    }

    public function openCreate(): void
    {
        $this->authorize('access-control.permissions.create');
        $this->dispatch('open-create-permission');
    }

    public function openImportExport(): void
    {
        $this->authorize('access-control.permissions.import-export');
        $this->dispatch('open-import-export-permissions');
    }

    public function openEdit(string $id): void
    {
        $this->authorize('access-control.permissions.edit');

        $permission = Permission::findOrFail($id);

        if ($permission->is_synced) {
            $this->dispatch('notify', type: 'warning', message: __('Synced permissions cannot be modified.'));

            return;
        }

        if (RecordLock::isLockedByOther('permission', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('permission', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-edit-permission', id: $id);
    }

    public function openDelete(string $id): void
    {
        $this->authorize('access-control.permissions.destroy');

        $permission = Permission::findOrFail($id);

        if ($permission->is_synced) {
            $this->dispatch('notify', type: 'warning', message: __('Synced permissions cannot be deleted.'));

            return;
        }

        if (RecordLock::isLockedByOther('permission', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('permission', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $this->dispatch('open-delete-permission', id: $id);
    }

    public function openBulkDelete(): void
    {
        $this->authorize('access-control.permissions.destroy');

        $ids = Permission::whereIn('id', $this->selected)->where('is_synced', false)->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        if (empty($ids)) {
            $this->dispatch('notify', type: 'warning', message: __('No deletable permissions selected. Synced permissions cannot be deleted.'));

            return;
        }

        if (count($ids) < count($this->selected)) {
            $this->dispatch('notify', type: 'info', message: __('Synced permissions were excluded from selection.'));
        }

        $this->dispatch('open-bulk-delete-permissions', ids: $ids);
    }

    public function openRestore(string $id): void
    {
        $this->authorize('access-control.permissions.restore');
        $this->dispatch('open-restore-permission', id: $id);
    }

    public function openForceDelete(string $id): void
    {
        $this->authorize('access-control.permissions.force-delete');

        $permission = Permission::withTrashed()->findOrFail($id);

        if ($permission->is_synced) {
            $this->dispatch('notify', type: 'warning', message: __('Synced permissions cannot be deleted.'));

            return;
        }

        $this->dispatch('open-force-delete-permission', id: $id);
    }

    #[On('permission-saved')]
    public function onPermissionSaved(): void
    {
        $this->resetPage();
    }

    #[On('permissions-bulk-deleted')]
    public function onBulkDeleted(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    #[On('permission-record-changed-broadcast')]
    public function onPermissionRecordChanged(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'info', message: __('Data was updated by another user.'));
    }

    #[Computed]
    public function lockedIds(): array
    {
        $ids = $this->permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        return RecordLock::getLockedByOthers('permission', $ids, (string) auth()->id());
    }

    #[Computed]
    public function permissions(): LengthAwarePaginator
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

        return Permission::query()
            ->when(
                $this->tenantId,
                fn ($q) => $q->forTenant($this->tenantId),
                fn ($q) => $q->central(),
            )
            ->when($this->tab === 'trash', fn ($q) => $q->onlyTrashed())
            ->filter($builtFilters)
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->sort(["{$this->sortBy}:{$this->sortDir}"])
            ->withCount('roles')
            ->paginate($this->perPage)
            ->onEachSide(1);
    }

    public function openSync(): void
    {
        $this->authorize('access-control.permissions.sync');
        $this->dispatch('open-sync-permissions');
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.index');
    }
}
