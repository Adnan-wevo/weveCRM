<?php

namespace Modules\CRM\Livewire\Pipeline;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\CRM\Models\Deal;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStage = '';

    protected $queryString = ['search', 'filterStage'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStage(): void
    {
        $this->resetPage();
    }

    #[On('deal-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function deleteDeal(string $id): void
    {
        $deal = Deal::findOrFail($id);

        if (! auth()->user()->can('crm.manage') && $deal->owner_id !== auth()->id()) {
            session()->flash('error', __('Unauthorized'));

            return;
        }

        $deal->delete();
        $this->dispatch('deal-saved');
        session()->flash('success', __('Deal deleted'));
    }

    public function render()
    {
        $query = Deal::query()->with(['contact', 'owner'])->latest();

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->filterStage) {
            $query->where('stage', $this->filterStage);
        }

        $deals = $query->paginate(10);

        $stages = ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

        return view('crm::pipeline.index', compact('deals', 'stages'));
    }
}
