<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

class DeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $tenantId = null;

    public string $tenantName = '';

    #[On('open-delete-tenant')]
    public function open(string $id): void
    {
        if (RecordLock::isLockedByOther('tenant', $id, (string) auth()->id())) {
            $lock = RecordLock::lockedBy('tenant', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $tenant = Tenant::findOrFail($id);
        $this->tenantId = $id;
        $this->tenantName = (string) $tenant->name;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('organisation-setup.tenants.destroy');

        Tenant::findOrFail($this->tenantId)->delete();

        $this->show = false;
        $this->dispatch('tenant-saved');
        $this->dispatch('notify', type: 'success', message: __('Tenant deleted successfully.'));
        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.delete-modal');
    }
}
