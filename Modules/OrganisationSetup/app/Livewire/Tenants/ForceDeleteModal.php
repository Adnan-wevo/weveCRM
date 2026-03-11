<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

class ForceDeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $tenantId = null;

    public string $tenantName = '';

    #[On('open-force-delete-tenant')]
    public function open(string $id): void
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        $this->tenantId = $id;
        $this->tenantName = (string) $tenant->name;
        $this->show = true;
    }

    public function forceDelete(): void
    {
        $this->authorize('organisation-setup.tenants.force-delete');

        $tenant = Tenant::onlyTrashed()->findOrFail($this->tenantId);
        $tenant->domains()->delete();
        $tenant->forceDelete();

        $this->show = false;
        $this->dispatch('tenant-saved');
        $this->dispatch('notify', type: 'success', message: __('Tenant permanently deleted.'));
        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.force-delete-modal');
    }
}
