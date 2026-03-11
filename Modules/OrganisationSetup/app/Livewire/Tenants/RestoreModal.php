<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

class RestoreModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $tenantId = null;

    public string $tenantName = '';

    #[On('open-restore-tenant')]
    public function open(string $id): void
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        $this->tenantId = $id;
        $this->tenantName = (string) $tenant->name;
        $this->show = true;
    }

    public function restore(): void
    {
        $this->authorize('organisation-setup.tenants.restore');

        Tenant::onlyTrashed()->findOrFail($this->tenantId)->restore();

        $this->show = false;
        $this->dispatch('tenant-saved');
        $this->dispatch('notify', type: 'success', message: __('Tenant restored successfully.'));
        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.restore-modal');
    }
}
