<?php

namespace Modules\AccessControl\Livewire\Roles;

use App\Models\Role;
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

    public ?string $roleId = null;

    public string $recordName = '';

    #[On('open-history-role')]
    public function open(string $id): void
    {
        $this->authorize('access-control.roles.history');
        $this->roleId = $id;

        $role = Role::withTrashed()->find($id);
        // @phpstan-ignore nullsafe.neverNull
        $this->recordName = $role?->name ?? $id;
        $this->show = true;
    }

    #[Computed]
    public function audits(): Collection
    {
        if ($this->roleId === null) {
            return collect();
        }

        return Role::withTrashed()
            ->find($this->roleId)
            ?->audits()
            ->with('user')
            ->latest()
            ->get() ?? collect();
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.roles.history-modal');
    }
}
