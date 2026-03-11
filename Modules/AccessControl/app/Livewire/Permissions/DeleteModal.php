<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\PermissionRecordChanged;

class DeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $permissionId = null;

    public string $permissionName = '';

    #[On('open-delete-permission')]
    public function open(string $id): void
    {
        if (RecordLock::isLockedByOther('permission', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('permission', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $permission = Permission::findOrFail($id);
        $this->permissionId = $id;
        $this->permissionName = $permission->name;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('access-control.permissions.destroy');

        Permission::findOrFail($this->permissionId)->delete();

        $this->show = false;
        $this->dispatch('permission-saved');
        $this->dispatch('notify', type: 'success', message: __('Permission deleted successfully.'));
        $pending = broadcast(new PermissionRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.delete-modal');
    }
}
