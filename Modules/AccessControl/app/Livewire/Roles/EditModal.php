<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\RoleRecordChanged;

class EditModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $roleId = null;

    public string $formName = '';

    public string $formGuard = 'web';

    /** @var array<int, string> */
    public array $formPermissions = [];

    #[On('open-edit-role')]
    public function open(string $id): void
    {
        $acquired = RecordLock::acquire('role', $id, (string) auth()->id(), auth()->user()->name);

        if (! $acquired) {
            $lock = RecordLock::lockedBy('role', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $role = Role::with('permissions')->findOrFail($id);
        $this->roleId = $id;
        $this->formName = $role->name;
        $this->formGuard = $role->guard_name;
        $this->formPermissions = $role->permissions
            ->filter(function ($p) {
                /** @var \App\Models\Permission $p */
                return tenancy()->initialized ? $p->tenant_id === tenant('id') : $p->tenant_id === null;
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
        $this->resetValidation();
        $this->show = true;

        $pending = broadcast(new RoleRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function updatedShow(bool $value): void
    {
        if (! $value && $this->roleId !== null) {
            RecordLock::release('role', $this->roleId, (string) auth()->id());

            $pending = broadcast(new RoleRecordChanged);
            if (request()->hasHeader('X-Socket-ID')) {
                $pending->toOthers();
            }
        }
    }

    public function update(): void
    {
        $this->authorize('access-control.roles.update');

        $this->validate([
            'formName' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->where('tenant_id', tenancy()->initialized ? tenant('id') : null)->ignore($this->roleId),
            ],
            'formGuard' => ['required', 'string', 'max:255'],
        ]);

        $role = Role::findOrFail($this->roleId);
        $role->update(['name' => $this->formName, 'guard_name' => $this->formGuard]);

        $permissions = tenancy()->initialized
            ? Permission::forTenant(tenant('id'))->whereIn('id', $this->formPermissions)->get()
            : Permission::central()->whereIn('id', $this->formPermissions)->get();

        $role->syncPermissions($permissions);

        RecordLock::release('role', $this->roleId, (string) auth()->id());
        $this->show = false;
        $this->dispatch('role-saved');
        $this->dispatch('notify', type: 'success', message: __('Role updated successfully.'));
        $pending = broadcast(new RoleRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    #[Computed]
    public function allPermissions(): Collection
    {
        if (tenancy()->initialized) {
            return Permission::forTenant(tenant('id'))->orderBy('name')->get();
        }

        return Permission::central()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.edit-modal');
    }
}
