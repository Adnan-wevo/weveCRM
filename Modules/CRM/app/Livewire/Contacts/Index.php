<?php

namespace Modules\CRM\Livewire\Contacts;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('contact-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function deleteContact($id)
    {
        $contact = Contact::findOrFail($id);

        if (! auth()->user() || (! auth()->user()->can('crm.manage') && $contact->owner_id !== auth()->id())) {
            session()->flash('error', __('Unauthorized'));
            return;
        }

        $contact->delete();

        session()->flash('success', __('Contact deleted'));
        $this->dispatch('contact-saved');
    }

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
