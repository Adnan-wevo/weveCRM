<?php

namespace Modules\CRM\Livewire\Calls;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\CRM\Models\Call;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('call-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function deleteCall(string $id): void
    {
        $call = Call::findOrFail($id);

        if (! auth()->user()->can('crm.manage') && $call->user_id !== auth()->id()) {
            session()->flash('error', __('Unauthorized'));

            return;
        }

        $call->delete();
        $this->dispatch('call-saved');
        session()->flash('success', __('Call deleted'));
    }

    public function render()
    {
        $calls = Call::query()
            ->with(['contact', 'user'])
            ->when($this->search, function ($query): void {
                $query->where('notes', 'like', "%{$this->search}%")
                    ->orWhereHas('contact', fn ($contactQuery) => $contactQuery->where('name', 'like', "%{$this->search}%"));
            })
            ->latest('called_at')
            ->paginate(10);

        return view('crm::calls.index', compact('calls'));
    }
}
