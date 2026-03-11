<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\User;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\UserRecordChanged;

class BulkDeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    /** @var array<int, string> */
    public array $ids = [];

    #[On('open-bulk-delete-users')]
    public function open(array $ids): void
    {
        $this->ids = $ids;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('access-control.users.destroy');

        $lockedIds = RecordLock::getLockedByOthers('user', $this->ids, (string) auth()->id());
        $idsToDelete = array_values(array_filter($this->ids, fn ($id) => ! isset($lockedIds[$id])));

        $query = User::whereIn('id', $idsToDelete);

        if (tenancy()->initialized) {
            $query = $query->whereHas('tenants', fn ($q) => $q->where('tenant_id', tenant('id')));
        }

        $query->delete();

        $this->show = false;
        $this->ids = [];
        $this->dispatch('users-bulk-deleted');

        if (! empty($lockedIds)) {
            $this->dispatch('notify', type: 'warning', message: __(':count record(s) skipped — currently being edited.', ['count' => count($lockedIds)]));
        } else {
            $this->dispatch('notify', type: 'success', message: __('Selected users deleted.'));
        }

        $pending = broadcast(new UserRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.bulk-delete-modal');
    }
}
