<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

/**
 * @property-read \Illuminate\Pagination\LengthAwarePaginator $tenantUsers
 * @property-read \Illuminate\Database\Eloquent\Collection $tenantRoles
 */
class TenantUserModal extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $show = false;

    public string $tenantId = '';

    public string $tenantName = '';

    public string $formUserEmail = '';

    public string $formRoleId = '';

    #[On('open-tenant-users')]
    public function open(string $id): void
    {
        $this->authorize('organisation-setup.tenants.add-user');

        $tenant = Tenant::findOrFail($id);

        $this->tenantId = $id;
        $this->tenantName = $tenant->name;
        $this->reset('formUserEmail', 'formRoleId');
        $this->resetValidation();
        $this->resetPage();
        $this->show = true;
    }

    public function addUser(): void
    {
        $this->authorize('organisation-setup.tenants.add-user');

        $this->validate([
            'formUserEmail' => ['required', 'email', 'exists:users,email'],
            'formRoleId' => ['nullable', 'exists:roles,id'],
        ]);

        $tenant = Tenant::findOrFail($this->tenantId);
        $user = User::where('email', $this->formUserEmail)->firstOrFail();

        $tenant->users()->syncWithoutDetaching([$user->id]);

        if ($this->formRoleId !== '') {
            $role = Role::forTenant($this->tenantId)->find($this->formRoleId);

            if ($role) {
                $user->assignRole($role);
            }
        }

        $this->reset('formUserEmail', 'formRoleId');
        $this->resetValidation();
        $this->dispatch('notify', type: 'success', message: __('User added to tenant successfully.'));

        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function removeUser(string $userId): void
    {
        $this->authorize('organisation-setup.tenants.remove-user');

        $tenant = Tenant::findOrFail($this->tenantId);
        $user = User::findOrFail($userId);

        // Remove all tenant-scoped roles from this user before detaching.
        $tenantRoles = Role::forTenant($this->tenantId)->get();
        foreach ($tenantRoles as $role) {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        }

        $tenant->users()->detach($userId);

        $this->dispatch('notify', type: 'success', message: __('User removed from tenant.'));

        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    #[Computed]
    public function tenantUsers(): LengthAwarePaginator
    {
        if ($this->tenantId === '') {
            return new LengthAwarePaginator([], 0, 10);
        }

        return User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenant_id', $this->tenantId))
            ->with(['roles' => fn ($q) => $q->forTenant($this->tenantId)])
            ->paginate(10, pageName: 'usersPage');
    }

    #[Computed]
    public function tenantRoles(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->tenantId === '') {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return Role::forTenant($this->tenantId)->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.tenant-user-modal');
    }
}
