<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection $tenantDomains
 */
class AssignDomainModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public string $tenantId = '';

    public string $tenantName = '';

    public string $formDomain = '';

    #[On('open-assign-domain')]
    public function open(string $id): void
    {
        $this->authorize('organisation-setup.tenants.assign-domain');

        $tenant = Tenant::findOrFail($id);

        $this->tenantId = $id;
        $this->tenantName = $tenant->name;
        $this->reset('formDomain');
        $this->resetValidation();
        $this->show = true;
    }

    public function addDomain(): void
    {
        $this->authorize('organisation-setup.tenants.assign-domain');

        $this->validate([
            'formDomain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
        ]);

        $tenant = Tenant::findOrFail($this->tenantId);
        $tenant->domains()->create(['domain' => strtolower(trim($this->formDomain))]);

        $this->reset('formDomain');
        $this->resetValidation();
        unset($this->tenantDomains);

        $this->dispatch('notify', type: 'success', message: __('Domain added successfully.'));

        // Sync the new domain to Keycloak's allowed redirect URIs.
        Artisan::queue('keycloak:sync-uris');

        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function removeDomain(int $domainId): void
    {
        $this->authorize('organisation-setup.tenants.assign-domain');

        $domain = Domain::findOrFail($domainId);

        // Ensure domain belongs to this tenant for safety.
        abort_if($domain->tenant_id !== $this->tenantId, 403);

        $domain->delete();

        unset($this->tenantDomains);

        $this->dispatch('notify', type: 'success', message: __('Domain removed.'));

        // Sync removed domain from Keycloak's allowed redirect URIs.
        Artisan::queue('keycloak:sync-uris');

        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    #[Computed]
    public function tenantDomains(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->tenantId === '') {
            return Domain::query()->whereNull('id')->get();
        }

        return Domain::where('tenant_id', $this->tenantId)->orderBy('domain')->get();
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.assign-domain-modal');
    }
}
