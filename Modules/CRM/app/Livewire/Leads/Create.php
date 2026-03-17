<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Component;
use App\Models\Lead;

class Create extends Component
{
    public string $source = '';
    public string $status = 'new';
    public string $contact_id = '';

    protected $rules = [
        'source' => 'nullable|string|max:255',
        'status' => 'required|string|max:50',
        'contact_id' => 'nullable|uuid',
    ];

    public function save(): void
    {
        $this->validate();

        Lead::create([
            'source' => $this->source ?: null,
            'status' => $this->status,
            'contact_id' => $this->contact_id ?: null,
            'owner_id' => auth()->id(),
        ]);

        $this->dispatch('lead-saved');

        session()->flash('success', __('Lead created'));

        $this->redirect(route('crm.leads.index'));
    }

    public function render()
    {
        return view('crm::leads.create');
    }
}
