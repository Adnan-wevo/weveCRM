<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\PermissionRecordChanged;

class RestoreModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $permissionId = null;

    public string $permissionName = '';

    #[On('open-restore-permission')]
    public function open(string $id): void
    {
        $permission = Permission::onlyTrashed()->findOrFail($id);
        $this->permissionId = $id;
        $this->permissionName = $permission->name;
        $this->show = true;
    }

    public function restore(): void
    {
        $this->authorize('access-control.permissions.restore');

        Permission::onlyTrashed()->findOrFail($this->permissionId)->restore();

        $this->show = false;
        $this->dispatch('permission-saved');
        $this->dispatch('notify', type: 'success', message: __('Permission restored successfully.'));
        $pending = broadcast(new PermissionRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.restore-modal');
    }
}
