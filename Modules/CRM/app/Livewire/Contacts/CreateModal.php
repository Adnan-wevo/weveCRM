<?php

namespace Modules\CRM\Livewire\Contacts;

use Livewire\Component;
use App\Models\Contact;

class CreateModal extends Component
{
    public $name;
    public $email;
    public $phone;
    public $company;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'company' => 'nullable|string|max:255',
    ];

    public function save()
    {
        $this->validate();

        Contact::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
        ]);

        $this->emit('contactCreated');
        $this->reset(['name','email','phone','company']);
    }

    public function render()
    {
        return view('crm::contacts.create-modal');
    }
}
