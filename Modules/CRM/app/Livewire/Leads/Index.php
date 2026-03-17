<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lead;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('lead-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function deleteLead(string $id): void
    {
        $lead = Lead::findOrFail($id);

        if (! auth()->user()->can('crm.manage') && $lead->owner_id !== auth()->id()) {
            session()->flash('error', __('Unauthorized'));
            return;
        }

        $lead->delete();

        $this->dispatch('lead-saved');
        session()->flash('success', __('Lead deleted'));
    }

    public function render()
    {
        $query = Lead::query()->with('contact')->latest();

        if ($this->search) {
            $query->where('source', 'like', "%{$this->search}%")
                ->orWhere('status', 'like', "%{$this->search}%");
        }

        $leads = $query->paginate(10);

        return view('crm::leads.index', compact('leads'));
    }
}


