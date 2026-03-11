<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\RoleRecordChanged;

class RestoreModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $roleId = null;

    public string $roleName = '';

    #[On('open-restore-role')]
    public function open(string $id): void
    {
        $role = Role::onlyTrashed()->findOrFail($id);
        $this->roleId = $id;
        $this->roleName = $role->name;
        $this->show = true;
    }

    public function restore(): void
    {
        $this->authorize('access-control.roles.restore');

        Role::onlyTrashed()->findOrFail($this->roleId)->restore();

        $this->show = false;
        $this->dispatch('role-saved');
        $this->dispatch('notify', type: 'success', message: __('Role restored successfully.'));
        $pending = broadcast(new RoleRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.restore-modal');
    }
}
