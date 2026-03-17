<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Component;
use App\Models\Lead;

class Edit extends Component
{
    public Lead $lead;

    public string $source = '';
    public string $status = 'new';
    public string $contact_id = '';

    protected $rules = [
        'source' => 'nullable|string|max:255',
        'status' => 'required|string|max:50',
        'contact_id' => 'nullable|uuid',
    ];

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
        $this->source = $lead->source ?? '';
        $this->status = $lead->status ?? 'new';
        $this->contact_id = $lead->contact_id ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $this->lead->update([
            'source' => $this->source ?: null,
            'status' => $this->status,
            'contact_id' => $this->contact_id ?: null,
        ]);

        $this->dispatch('lead-saved');

        session()->flash('success', __('Lead updated'));

        $this->redirect(route('crm.leads.index'));
    }

    public function render()
    {
        return view('crm::leads.edit');
    }
}
