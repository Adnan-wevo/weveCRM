<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** @property-read \Illuminate\Support\Collection $audits */
class HistoryModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $userId = null;

    public string $recordName = '';

    #[On('open-history-user')]
    public function open(string $id): void
    {
        $this->authorize('access-control.users.history');
        $this->userId = $id;

        $user = User::withTrashed()->find($id);
        // @phpstan-ignore nullsafe.neverNull
        $this->recordName = $user?->name ?? $id;
        $this->show = true;
    }

    #[Computed]
    public function audits(): Collection
    {
        if ($this->userId === null) {
            return collect();
        }

        return User::withTrashed()
            ->find($this->userId)
            ?->audits()
            ->with('user')
            ->latest()
            ->get() ?? collect();
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.history-modal');
    }
}
