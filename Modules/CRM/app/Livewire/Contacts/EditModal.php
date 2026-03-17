<?php

namespace Modules\CRM\Livewire\Contacts;

use Livewire\Component;
use App\Models\Contact;

class EditModal extends Component
{
    public Contact $contact;

    protected $rules = [
        'contact.name' => 'required|string|max:255',
        'contact.email' => 'nullable|email|max:255',
        'contact.phone' => 'nullable|string|max:50',
        'contact.company' => 'nullable|string|max:255',
    ];

    public function save()
    {
        $this->validate();

        $this->contact->save();
        $this->emit('contactUpdated');
    }

    public function render()
    {
        return view('crm::contacts.edit-modal');
    }
}
