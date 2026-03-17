<?php

namespace Modules\CRM\Livewire\Leads;

use Livewire\Component;
use App\Models\Lead;

class ShowModal extends Component
{
	public Lead $lead;

	public function render()
	{
		return view('crm::leads.show-modal');
	}
}
