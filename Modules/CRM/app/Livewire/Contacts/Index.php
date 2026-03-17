<?php

namespace Modules\CRM\Livewire\Contacts;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    protected $listeners = ['contactCreated' => '$refresh', 'contactUpdated' => '$refresh'];

    public function render()
    {
        $query = Contact::query()->latest();

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
        }

        $contacts = $query->paginate(10);

        return view('crm::contacts.index', compact('contacts'));
    }
}
