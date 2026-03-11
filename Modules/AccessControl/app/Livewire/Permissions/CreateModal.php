<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\PermissionRecordChanged;

class CreateModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public string $formName = '';

    public string $formGuard = 'web';

    #[On('open-create-permission')]
    public function open(): void
    {
        $this->reset('formName');
        $this->formGuard = 'web';
        $this->resetValidation();
        $this->show = true;
    }

    public function create(): void
    {
        $this->authorize('access-control.permissions.store');

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
                Rule::unique('permissions', 'name')->where('tenant_id', tenancy()->initialized ? tenant('id') : null),
            ],
            'formGuard' => ['required', 'string', 'max:255'],
        ]);

        Permission::create([
            'name' => $this->formName,
            'guard_name' => $this->formGuard,
            'tenant_id' => tenancy()->initialized ? tenant('id') : null,
        ]);

        $this->show = false;
        $this->dispatch('permission-saved');
        $this->dispatch('notify', type: 'success', message: __('Permission created successfully.'));
        $pending = broadcast(new PermissionRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.create-modal');
    }
}
