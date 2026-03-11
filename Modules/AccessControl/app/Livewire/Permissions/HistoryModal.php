<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Models\Permission;
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

    public ?string $permissionId = null;

    public string $recordName = '';

    #[On('open-history-permission')]
    public function open(string $id): void
    {
        $this->authorize('access-control.permissions.history');
        $this->permissionId = $id;

        $permission = Permission::withTrashed()->find($id);
        // @phpstan-ignore nullsafe.neverNull
        $this->recordName = $permission?->name ?? $id;
        $this->show = true;
    }

    #[Computed]
    public function audits(): Collection
    {
        if ($this->permissionId === null) {
            return collect();
        }

        return Permission::withTrashed()
            ->find($this->permissionId)
            ?->audits()
            ->with('user')
            ->latest()
            ->get() ?? collect();
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.history-modal');
    }
}
