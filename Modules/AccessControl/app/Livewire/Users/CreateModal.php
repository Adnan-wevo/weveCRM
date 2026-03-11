<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AccessControl\Events\UserRecordChanged;

class CreateModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public string $formName = '';

    public string $formEmail = '';

    public string $formPassword = '';

    public string $formPasswordConfirm = '';

    /** @var array<int, string> */
    public array $formRoles = [];

    #[On('open-create-user')]
    public function open(): void
    {
        $this->reset('formName', 'formEmail', 'formPassword', 'formPasswordConfirm', 'formRoles');
        $this->resetValidation();
        $this->show = true;
    }

    public function create(): void
    {
        $this->authorize('access-control.users.store');

        $this->validate([
            'formName' => ['required', 'string', 'max:255'],
            'formEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'formPassword' => ['required', 'string', 'min:8', 'same:formPasswordConfirm'],
            'formPasswordConfirm' => ['required'],
            'formRoles' => ['array'],
        ]);

        $user = User::create([
            'name' => $this->formName,
            'email' => $this->formEmail,
            'password' => bcrypt($this->formPassword),
            'email_verified_at' => now(),
        ]);

        if (tenancy()->initialized) {
            $roles = Role::forTenant(tenant('id'))->whereIn('id', $this->formRoles)->get();
            $user->syncRoles($roles);
            Tenant::find(tenant('id'))->users()->syncWithoutDetaching([$user->id]);
        } else {
            $roles = Role::central()->whereIn('id', $this->formRoles)->get();
            $user->syncRoles($roles);
        }

        $this->show = false;
        $this->dispatch('user-saved');
        $this->dispatch('notify', type: 'success', message: __('User created successfully.'));
        $pending = broadcast(new UserRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    #[Computed]
    public function allRoles(): \Illuminate\Database\Eloquent\Collection
    {
        if (tenancy()->initialized) {
            return Role::forTenant(tenant('id'))->orderBy('name')->get();
        }

        return Role::central()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.create-modal');
    }
}
