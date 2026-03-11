<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Models\Tenant;
use App\Support\RecordLock;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\OrganisationSetup\Events\TenantRecordChanged;

class BulkDeleteModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    /** @var array<int, string> */
    public array $ids = [];

    #[On('open-bulk-delete-tenants')]
    public function open(array $ids): void
    {
        $this->ids = $ids;
        $this->show = true;
    }

    public function delete(): void
    {
        $this->authorize('organisation-setup.tenants.destroy');

        $lockedIds = RecordLock::getLockedByOthers('tenant', $this->ids, (string) auth()->id());
        $idsToDelete = array_values(array_filter($this->ids, fn ($id) => ! isset($lockedIds[$id])));

        Tenant::whereIn('id', $idsToDelete)->delete();

        $this->show = false;
        $this->ids = [];
        $this->dispatch('tenants-bulk-deleted');

        if (! empty($lockedIds)) {
            $this->dispatch('notify', type: 'warning', message: __(':count record(s) skipped — currently being edited.', ['count' => count($lockedIds)]));
        } else {
            $this->dispatch('notify', type: 'success', message: __('Selected tenants deleted.'));
        }

        $pending = broadcast(new TenantRecordChanged);
        if (request()->hasHeader('X-Socket-ID')) {
            $pending->toOthers();
        }
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.bulk-delete-modal');
    }
}
