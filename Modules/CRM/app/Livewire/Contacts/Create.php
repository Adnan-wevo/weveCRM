<?php

namespace Modules\CRM\Livewire\Contacts;

use Livewire\Component;
use App\Models\Contact;

class Create extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
    ];

    public function save()
    {
        $this->validate();

        Contact::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'owner_id' => auth()->id(),
        ]);

        $this->dispatch('contact-saved');

        session()->flash('success', __('Contact created'));

        return redirect()->route('crm.contacts.index');
    }

    public function render()
    {
        return view('crm::contacts.create');
    }
}
