<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\Role;
use App\Models\User;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AccessControl\Events\UserRecordChanged;

class EditModal extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $show = false;

    public ?string $userId = null;

    public string $formName = '';

    public string $formEmail = '';

    public string $formPassword = '';

    public string $formPasswordConfirm = '';

    /** @var array<int, string> */
    public array $formRoles = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    #[Validate(['nullable', 'mimes:png', 'max:5120'])]
    public $formAvatar = null;

    public ?string $currentAvatarUrl = null;

    public bool $removeAvatar = false;

    #[On('open-edit-user')]
    public function open(string $id): void
    {
        $acquired = RecordLock::acquire('user', $id, (string) auth()->id(), auth()->user()->name);

        if (! $acquired) {
            $lock = RecordLock::lockedBy('user', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $user = User::with('roles', 'tenants')->findOrFail($id);

        if (tenancy()->initialized && ! $user->tenants->contains('id', tenant('id'))) {
            RecordLock::release('user', $id, (string) auth()->id());
            $this->dispatch('notify', type: 'error', message: __('User does not belong to this tenant.'));

            return;
        }

        $this->userId = $id;
        $this->formName = $user->name;
        $this->formEmail = $user->email;
        $this->formPassword = '';
        $this->formPasswordConfirm = '';
        $this->formRoles = $user->roles
            ->filter(function ($r) {
                /** @var \App\Models\Role $r */
                return tenancy()->initialized ? $r->tenant_id === tenant('id') : $r->tenant_id === null;
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
        $this->formAvatar = null;
        $this->removeAvatar = false;
        $this->currentAvatarUrl = $user->avatarUrl();
        $this->resetValidation();
        $this->show = true;

        $pending = broadcast(new UserRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function updatedShow(bool $value): void
    {
        if (! $value && $this->userId !== null) {
            RecordLock::release('user', $this->userId, (string) auth()->id());

            $pending = broadcast(new UserRecordChanged);
            if (request()->hasHeader('X-Socket-ID')) {
                $pending->toOthers();
            }
        }
    }

    public function update(): void
    {
        $this->authorize('access-control.users.update');

        $this->validate([
            'formName' => ['required', 'string', 'max:255'],
            'formEmail' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->userId],
            'formPassword' => ['nullable', 'string', 'min:8', 'same:formPasswordConfirm'],
            'formPasswordConfirm' => ['nullable'],
            'formRoles' => ['array'],
            'formAvatar' => ['nullable', 'mimes:png', 'max:5120'],
        ]);

        $user = User::findOrFail($this->userId);

        $data = ['name' => $this->formName, 'email' => $this->formEmail];

        if ($this->formPassword) {
            $data['password'] = bcrypt($this->formPassword);
        }

        $user->update($data);

        // Smart role sync: only manage roles belonging to the current context;
        // preserve roles that belong to other scopes.
        if (tenancy()->initialized) {
            $selectedRoles = Role::forTenant(tenant('id'))->whereIn('id', $this->formRoles)->get();
            $otherRoles = $user->roles->filter(function ($r) {
                /** @var \App\Models\Role $r */
                return $r->tenant_id !== tenant('id');
            });
        } else {
            $selectedRoles = Role::central()->whereIn('id', $this->formRoles)->get();
            $otherRoles = $user->roles->filter(function ($r) {
                /** @var \App\Models\Role $r */
                return $r->tenant_id !== null;
            });
        }

        $user->syncRoles($otherRoles->merge($selectedRoles));

        if ($this->removeAvatar && ! $this->formAvatar) {
            $user->clearMediaCollection('users');
        }

        if ($this->formAvatar) {
            $user->addMedia($this->formAvatar->getRealPath())
                ->usingFileName($this->formAvatar->getClientOriginalName())
                ->toMediaCollection('users');
        }

        RecordLock::release('user', $this->userId, (string) auth()->id());
        $this->show = false;
        $this->dispatch('user-saved');
        $this->dispatch('notify', type: 'success', message: __('User updated successfully.'));
        $pending = broadcast(new UserRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    #[Computed]
    public function avatarPreviewUrl(): ?string
    {
        if (! $this->formAvatar) {
            return null;
        }

        try {
            return $this->formAvatar->temporaryUrl();
        } catch (\Throwable) {
            return null;
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
        return view('accesscontrol::livewire.users.edit-modal');
    }
}
