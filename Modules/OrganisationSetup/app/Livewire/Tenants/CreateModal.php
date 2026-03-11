<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

class CreateModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public string $formId = '';

    public string $formName = '';

    #[On('open-create-tenant')]
    public function open(): void
    {
        $this->reset('formId', 'formName');
        $this->resetValidation();
        $this->show = true;
    }

    public function create(): void
    {
        $this->authorize('organisation-setup.tenants.store');

        $this->validate([
            'formName' => ['required', 'string', 'max:255'],
            'formId' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/', 'unique:tenants,id'],
        ]);

        $data = ['name' => $this->formName];

        if ($this->formId !== '') {
            $data['id'] = $this->formId;
        }

        Tenant::create($data);

        $this->show = false;
        $this->dispatch('tenant-saved');
        $this->dispatch('notify', type: 'success', message: __('Tenant created successfully.'));
        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.create-modal');
    }
}
