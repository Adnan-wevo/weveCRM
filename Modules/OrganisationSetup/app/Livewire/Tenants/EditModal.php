<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

class EditModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $tenantId = null;

    public string $formName = '';

    #[On('open-edit-tenant')]
    public function open(string $id): void
    {
        $acquired = RecordLock::acquire('tenant', $id, (string) auth()->id(), auth()->user()->name);

        if (! $acquired) {
            $lock = RecordLock::lockedBy('tenant', $id);
            $this->dispatch('notify', type: 'warning', message: __('Being edited by :name.', ['name' => $lock['user_name']]));

            return;
        }

        $tenant = Tenant::findOrFail($id);
        $this->tenantId = $id;
        $this->formName = (string) $tenant->name;
        $this->resetValidation();
        $this->show = true;

        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function updatedShow(bool $value): void
    {
        if (! $value && $this->tenantId !== null) {
            RecordLock::release('tenant', $this->tenantId, (string) auth()->id());

            $pending = broadcast(new TenantRecordChanged);
            if (request()->hasHeader('X-Socket-ID')) {
                $pending->toOthers();
            }
        }
    }

    public function update(): void
    {
        $this->authorize('organisation-setup.tenants.update');

        $this->validate([
            'formName' => ['required', 'string', 'max:255'],
        ]);

        $tenant = Tenant::findOrFail($this->tenantId);
        $tenant->name = $this->formName;
        $tenant->save();

        RecordLock::release('tenant', $this->tenantId, (string) auth()->id());
        $this->show = false;
        $this->dispatch('tenant-saved');
        $this->dispatch('notify', type: 'success', message: __('Tenant updated successfully.'));
        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.edit-modal');
    }
}
