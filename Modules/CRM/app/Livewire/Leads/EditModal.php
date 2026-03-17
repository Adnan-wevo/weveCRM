<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Component;
use App\Models\Lead;

class EditModal extends Component
{
    public Lead $lead;

    protected $rules = [
        'lead.source' => 'nullable|string|max:255',
        'lead.status' => 'required|string|max:50',
        'lead.contact_id' => 'nullable|uuid',
    ];

    public function save(): void
    {
        $this->validate();

        $this->lead->save();
        $this->dispatch('lead-saved');
        session()->flash('success', __('Lead updated'));
    }

    public function render()
    {
        return view('crm::leads.edit-modal');
    }
}
