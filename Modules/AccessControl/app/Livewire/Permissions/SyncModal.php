<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Actions\SyncRolesPermissionsToTenantAction;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class SyncModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    #[On('open-sync-permissions')]
    public function open(): void
    {
        $this->authorize('access-control.permissions.sync');
        $this->show = true;
    }

    public function sync(): void
    {
        $this->authorize('access-control.permissions.sync');

        $tenants = Tenant::all();
        $action = app(SyncRolesPermissionsToTenantAction::class);

        foreach ($tenants as $tenant) {
            /** @var \App\Models\Tenant $tenant */
            $action->execute($tenant);
        }

        $this->show = false;
        $this->dispatch('notify', type: 'success', message: __('Permissions synced to :count tenants.', ['count' => $tenants->count()]));
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.sync-modal');
    }
}
