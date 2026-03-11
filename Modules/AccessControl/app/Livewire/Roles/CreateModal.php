<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\RoleRecordChanged;

class CreateModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public string $formName = '';

    public string $formGuard = 'web';

    /** @var array<int, string> */
    public array $formPermissions = [];

    #[On('open-create-role')]
    public function open(): void
    {
        $this->reset('formName', 'formPermissions');
        $this->formGuard = 'web';
        $this->resetValidation();
        $this->show = true;
    }

    public function create(): void
    {
        $this->authorize('access-control.roles.store');

        $this->validate([
            'formName' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->where('tenant_id', tenancy()->initialized ? tenant('id') : null),
            ],
            'formGuard' => ['required', 'string', 'max:255'],
        ]);

        $role = Role::create([
            'name' => $this->formName,
            'guard_name' => $this->formGuard,
            'tenant_id' => tenancy()->initialized ? tenant('id') : null,
        ]);

        $permissions = tenancy()->initialized
            ? Permission::forTenant(tenant('id'))->whereIn('id', $this->formPermissions)->get()
            : Permission::central()->whereIn('id', $this->formPermissions)->get();

        $role->syncPermissions($permissions);

        $this->show = false;
        $this->dispatch('role-saved');
        $this->dispatch('notify', type: 'success', message: __('Role created successfully.'));
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
        return view('accesscontrol::livewire.roles.create-modal');
    }
}
