<?php

namespace Modules\CRM\Livewire\Dashboard;

use App\Models\Contact;
use App\Models\Lead;
use Livewire\Component;
use Modules\CRM\Models\Call;
use Modules\CRM\Models\Deal;

class Index extends Component
{
    public function render()
    {
        $openDealStages = ['new', 'qualified', 'proposal', 'negotiation'];

        $stats = [
            'contacts' => Contact::query()->count(),
            'active_leads' => Lead::query()->whereNotIn('status', ['lost', 'converted'])->count(),
            'open_deals' => Deal::query()->whereIn('stage', $openDealStages)->count(),
            'calls' => Call::query()->count(),
        ];

        $recentDeals = Deal::query()->latest()->limit(5)->get(['id', 'title', 'stage', 'value', 'currency']);
        $recentCalls = Call::query()->with('contact')->latest('called_at')->limit(5)->get(['id', 'contact_id', 'direction', 'called_at']);

        return view('crm::dashboard', compact('stats', 'recentDeals', 'recentCalls'));
    }
}
