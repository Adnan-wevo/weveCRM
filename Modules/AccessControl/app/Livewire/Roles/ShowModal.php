<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** @property-read \App\Models\Role|null $role */
class ShowModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $roleId = null;

    #[On('open-show-role')]
    public function open(string $id): void
    {
        $this->authorize('access-control.roles.show');
        $this->roleId = $id;
        $this->show = true;
    }

    #[Computed]
    public function role(): ?Role
    {
        if ($this->roleId === null) {
            return null;
        }

        return Role::with('permissions')->find($this->roleId);
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.show-modal');
    }
}
