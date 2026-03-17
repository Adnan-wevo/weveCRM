(<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lead;

class Index extends Component
{
	use WithPagination;

	public $search = '';

	protected $listeners = ['leadCreated' => '$refresh', 'leadUpdated' => '$refresh'];

	public function render()
	{
		$query = Lead::query()->latest();

		if ($this->search) {
			$query->where('source', 'like', "%{$this->search}%")->orWhere('status', 'like', "%{$this->search}%");
		}

		$leads = $query->paginate(10);

		return view('crm::leads.index', compact('leads'));
	}
}
)

