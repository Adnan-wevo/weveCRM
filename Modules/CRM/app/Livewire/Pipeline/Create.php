<?php

namespace Modules\CRM\Livewire\Pipeline;

use App\Models\Contact;
use Livewire\Component;
use Modules\CRM\Models\Deal;

class Create extends Component
{
    public string $title = '';

    public string $contact_id = '';

    public string $stage = 'new';

    public string $status = 'open';

    public string $value = '';

    public string $currency = 'MYR';

    protected $rules = [
        'title' => 'required|string|max:255',
        'contact_id' => 'nullable|uuid',
        'stage' => 'required|string|max:50',
        'status' => 'required|string|max:50',
        'value' => 'nullable|numeric|min:0',
        'currency' => 'nullable|string|max:10',
    ];

    public function save(): void
    {
        $this->validate();

        Deal::create([
            'title' => $this->title,
            'contact_id' => $this->contact_id ?: null,
            'stage' => $this->stage,
            'status' => $this->status,
            'value' => $this->value ?: null,
            'currency' => $this->currency ?: 'MYR',
            'owner_id' => auth()->id(),
        ]);

        $this->dispatch('deal-saved');
        session()->flash('success', __('Deal created'));
        $this->redirect(route('crm.pipeline.index'));
    }

    public function render()
    {
        $contacts = Contact::query()->orderBy('name')->get(['id', 'name']);
        $stages = ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
        $statuses = ['open', 'won', 'lost'];

        return view('crm::pipeline.create', compact('contacts', 'stages', 'statuses'));
    }
}
