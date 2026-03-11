<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\User;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\UserRecordChanged;

class DeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $userId = null;

    public string $userName = '';

    #[On('open-delete-user')]
    public function open(string $id): void
    {
        if (RecordLock::isLockedByOther('user', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('user', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $user = User::findOrFail($id);

        if (tenancy()->initialized && ! $user->tenants->contains('id', tenant('id'))) {
            $this->dispatch('notify', type: 'error', message: __('User does not belong to this tenant.'));

            return;
        }

        $this->userId = $id;
        $this->userName = $user->name;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('access-control.users.destroy');

        User::findOrFail($this->userId)->delete();

        $this->show = false;
        $this->dispatch('user-saved');
        $this->dispatch('notify', type: 'success', message: __('User deleted successfully.'));
        $pending = broadcast(new UserRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.delete-modal');
    }
}
