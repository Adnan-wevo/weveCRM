<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** @property-read \Illuminate\Support\Collection $audits */
class HistoryModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $tenantId = null;

    public string $recordName = '';

    #[On('open-history-tenant')]
    public function open(string $id): void
    {
        $this->authorize('organisation-setup.tenants.history');
        $this->tenantId = $id;

        $tenant = Tenant::withTrashed()->find($id);
        // @phpstan-ignore nullsafe.neverNull
        $this->recordName = $tenant?->name ?? $id;
        $this->show = true;
    }

    #[Computed]
    public function audits(): Collection
    {
        if ($this->tenantId === null) {
            return collect();
        }

        return Tenant::withTrashed()
            ->find($this->tenantId)
            ?->audits()
            ->with('user')
            ->latest()
            ->get() ?? collect();
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.history-modal');
    }
}
