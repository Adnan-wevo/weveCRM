<?php

namespace Modules\CRM\Livewire\Calls;

use App\Models\Contact;
use Livewire\Component;
use Modules\CRM\Models\Call;

class Create extends Component
{
    public string $contact_id = '';

    public string $direction = 'outbound';

    public string $duration = '';

    public string $notes = '';

    public string $called_at = '';

    protected $rules = [
        'contact_id' => 'nullable|uuid',
        'direction' => 'required|in:inbound,outbound',
        'duration' => 'nullable|integer|min:0',
        'notes' => 'nullable|string|max:5000',
        'called_at' => 'nullable|date',
    ];

    public function mount(): void
    {
        $this->called_at = now()->format('Y-m-d\\TH:i');
    }

    public function save(): void
    {
        $this->validate();

        Call::create([
            'contact_id' => $this->contact_id ?: null,
            'user_id' => auth()->id(),
            'direction' => $this->direction,
            'duration' => $this->duration ?: null,
            'notes' => $this->notes ?: null,
            'called_at' => $this->called_at ?: now(),
        ]);

        $this->dispatch('call-saved');
        session()->flash('success', __('Call logged'));
        $this->redirect(route('crm.calls.index'));
    }

    public function render()
    {
        $contacts = Contact::query()->orderBy('name')->get(['id', 'name']);

        return view('crm::calls.create', compact('contacts'));
    }
}
