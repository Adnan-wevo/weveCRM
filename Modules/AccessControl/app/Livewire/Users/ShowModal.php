<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** @property-read \App\Models\User|null $user */
class ShowModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $userId = null;

    #[On('open-show-user')]
    public function open(string $id): void
    {
        $this->authorize('access-control.users.show');
        $this->userId = $id;
        $this->show = true;
    }

    #[Computed]
    public function user(): ?User
    {
        if ($this->userId === null) {
            return null;
        }

        return User::with('roles')->find($this->userId);
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.show-modal');
    }
}
