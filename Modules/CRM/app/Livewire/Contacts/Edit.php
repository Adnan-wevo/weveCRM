<?php

namespace Modules\CRM\Livewire\Contacts;

use Livewire\Component;
use App\Models\Contact;

class Edit extends Component
{
    public Contact $contact;

    public $name = '';
    public $email = '';
    public $phone = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
    ];

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
        $this->name = $contact->name;
        $this->email = $contact->email;
        $this->phone = $contact->phone;
    }

    public function save()
    {
        $this->validate();

        $this->contact->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $this->dispatch('contact-saved');

        session()->flash('success', __('Contact updated'));

        return redirect()->route('crm.contacts.index');
    }

    public function render()
    {
        return view('crm::contacts.edit');
    }
}
