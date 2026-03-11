<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\PermissionRecordChanged;

class BulkDeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    /** @var array<int, string> */
    public array $ids = [];

    #[On('open-bulk-delete-permissions')]
    public function open(array $ids): void
    {
        $this->ids = $ids;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('access-control.permissions.destroy');

        $lockedIds = RecordLock::getLockedByOthers('permission', $this->ids, (string) auth()->id());
        $idsToDelete = array_values(array_filter($this->ids, fn ($id) => ! isset($lockedIds[$id])));

        Permission::whereIn('id', $idsToDelete)->delete();

        $this->show = false;
        $this->ids = [];
        $this->dispatch('permissions-bulk-deleted');

        if (! empty($lockedIds)) {
            $this->dispatch('notify', type: 'warning', message: __(':count record(s) skipped — currently being edited.', ['count' => count($lockedIds)]));
        } else {
            $this->dispatch('notify', type: 'success', message: __('Selected permissions deleted.'));
        }

        $pending = broadcast(new PermissionRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.bulk-delete-modal');
    }
}
