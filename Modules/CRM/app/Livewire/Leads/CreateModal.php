(<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Component;
use App\Models\Lead;

class CreateModal extends Component
{
	public $source;
	public $status = 'new';
	public $contact_id;

	protected $rules = [
		'source' => 'nullable|string|max:255',
		'status' => 'required|string|max:50',
		'contact_id' => 'nullable|uuid',
	];

	public function save()
	{
		$this->validate();

		Lead::create([
			'source' => $this->source,
			'status' => $this->status,
			'contact_id' => $this->contact_id,
		]);

		$this->emit('leadCreated');
		$this->reset(['source','status','contact_id']);
	}

	public function render()
	{
		return view('crm::leads.create-modal');
	}
}
)

