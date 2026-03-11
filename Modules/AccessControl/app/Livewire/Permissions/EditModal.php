<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\PermissionRecordChanged;

class EditModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $permissionId = null;

    public string $formName = '';

    public string $formGuard = 'web';

    #[On('open-edit-permission')]
    public function open(string $id): void
    {
        $acquired = RecordLock::acquire('permission', $id, (string) auth()->id(), auth()->user()->name);

        if (! $acquired) {
            $lock = RecordLock::lockedBy('permission', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $permission = Permission::findOrFail($id);
        $this->permissionId = $id;
        $this->formName = $permission->name;
        $this->formGuard = $permission->guard_name;
        $this->resetValidation();
        $this->show = true;

        $pending = broadcast(new PermissionRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function updatedShow(bool $value): void
    {
        if (! $value && $this->permissionId !== null) {
            RecordLock::release('permission', $this->permissionId, (string) auth()->id());

            $pending = broadcast(new PermissionRecordChanged);
            if (request()->hasHeader('X-Socket-ID')) {
                $pending->toOthers();
            }
        }
    }

    public function update(): void
    {
        $this->authorize('access-control.permissions.update');

        // Tenants may only hold access-control.permissions.index and .show.
        if (tenancy()->initialized
            && str_starts_with($this->formName, 'access-control.permissions.')
            && ! in_array($this->formName, ['access-control.permissions.index', 'access-control.permissions.show'])
        ) {
            $this->addError('formName', __('Tenants can only have access-control.permissions.index and access-control.permissions.show.'));

            return;
        }

        $this->validate([
            'formName' => [
                'required', 'string', 'max:255',
                Rule::unique('permissions', 'name')->where('tenant_id', tenancy()->initialized ? tenant('id') : null)->ignore($this->permissionId),
            ],
            'formGuard' => ['required', 'string', 'max:255'],
        ]);

        Permission::findOrFail($this->permissionId)->update([
            'name' => $this->formName,
            'guard_name' => $this->formGuard,
        ]);

        RecordLock::release('permission', $this->permissionId, (string) auth()->id());
        $this->show = false;
        $this->dispatch('permission-saved');
        $this->dispatch('notify', type: 'success', message: __('Permission updated successfully.'));
        $pending = broadcast(new PermissionRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.edit-modal');
    }
}
