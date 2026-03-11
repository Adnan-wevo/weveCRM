<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** @property-read \App\Models\Tenant|null $tenant */
class ShowModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $tenantId = null;

    #[On('open-show-tenant')]
    public function open(string $id): void
    {
        $this->authorize('organisation-setup.tenants.show');
        $this->tenantId = $id;
        $this->show = true;
    }

    #[Computed]
    public function tenant(): ?Tenant
    {
        if ($this->tenantId === null) {
            return null;
        }

        // @phpstan-ignore larastan.relationExistence
        return Tenant::with('domains')->find($this->tenantId);
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.show-modal');
    }
}
