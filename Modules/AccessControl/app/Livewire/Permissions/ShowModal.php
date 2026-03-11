<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** @property-read \App\Models\Permission|null $permission */
class ShowModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $permissionId = null;

    #[On('open-show-permission')]
    public function open(string $id): void
    {
        $this->authorize('access-control.permissions.show');
        $this->permissionId = $id;
        $this->show = true;
    }

    #[Computed]
    public function permission(): ?Permission
    {
        if ($this->permissionId === null) {
            return null;
        }

        return Permission::with('roles')->find($this->permissionId);
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.show-modal');
    }
}
