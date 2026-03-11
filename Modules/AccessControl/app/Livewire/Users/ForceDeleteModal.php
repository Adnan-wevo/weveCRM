<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\UserRecordChanged;

class ForceDeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $userId = null;

    public string $userName = '';

    #[On('open-force-delete-user')]
    public function open(string $id): void
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if (tenancy()->initialized && ! $user->tenants->contains('id', tenant('id'))) {
            $this->dispatch('notify', type: 'error', message: __('User does not belong to this tenant.'));

            return;
        }

        $this->userId = $id;
        $this->userName = $user->name;
        $this->show = true;
    }

    public function forceDelete(): void
    {
        $this->authorize('access-control.users.force-delete');

        User::onlyTrashed()->findOrFail($this->userId)->forceDelete();

        $this->show = false;
        $this->dispatch('user-saved');
        $this->dispatch('notify', type: 'success', message: __('User permanently deleted.'));
        $pending = broadcast(new UserRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.force-delete-modal');
    }
}
