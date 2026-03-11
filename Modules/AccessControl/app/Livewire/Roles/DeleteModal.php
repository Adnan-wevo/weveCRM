<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Role;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\RoleRecordChanged;

class DeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $roleId = null;

    public string $roleName = '';

    #[On('open-delete-role')]
    public function open(string $id): void
    {
        if (RecordLock::isLockedByOther('role', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('role', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $role = Role::findOrFail($id);
        $this->roleId = $id;
        $this->roleName = $role->name;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('access-control.roles.destroy');

        Role::findOrFail($this->roleId)->delete();

        $this->show = false;
        $this->dispatch('role-saved');
        $this->dispatch('notify', type: 'success', message: __('Role deleted successfully.'));
        $pending = broadcast(new RoleRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.delete-modal');
    }
}
