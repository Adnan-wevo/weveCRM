<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Role;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\RoleRecordChanged;

class BulkDeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    /** @var array<int, string> */
    public array $ids = [];

    #[On('open-bulk-delete-roles')]
    public function open(array $ids): void
    {
        $this->ids = $ids;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('access-control.roles.destroy');

        $lockedIds = RecordLock::getLockedByOthers('role', $this->ids, (string) auth()->id());
        $idsToDelete = array_values(array_filter($this->ids, fn ($id) => ! isset($lockedIds[$id])));

        Role::whereIn('id', $idsToDelete)->delete();

        $this->show = false;
        $this->ids = [];
        $this->dispatch('roles-bulk-deleted');

        if (! empty($lockedIds)) {
            $this->dispatch('notify', type: 'warning', message: __(':count record(s) skipped — currently being edited.', ['count' => count($lockedIds)]));
        } else {
            $this->dispatch('notify', type: 'success', message: __('Selected roles deleted.'));
        }

        $pending = broadcast(new RoleRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.bulk-delete-modal');
    }
}
