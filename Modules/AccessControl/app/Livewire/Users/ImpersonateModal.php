<?php

namespace Modules\AccessControl\Livewire\Users;

use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ImpersonateModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?string $userId = null;

    public string $userName = '';

    public string $userEmail = '';

    #[Validate('required|string|min:3|max:500')]
    public string $reason = '';

    #[On('open-impersonate-user')]
    public function open(string $id): void
    {
        $user = User::findOrFail($id);

        $this->userId = $id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->reason = '';
        $this->show = true;
    }

    public function impersonate(): void
    {
        $this->authorize('access-control.users.impersonate');
        $this->validate();

        /** @var User $currentUser */
        $currentUser = auth()->user();

        $target = User::findOrFail($this->userId);

        if (! $target->canBeImpersonated()) {
            $this->dispatch('notify', type: 'error', message: __('This user cannot be impersonated.'));

            return;
        }

        ImpersonationLog::create([
            'impersonator_id' => $currentUser->id,
            'impersonated_id' => $target->id,
            'reason' => $this->reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'started_at' => now(),
        ]);

        // After leaving impersonation the package reads this session key to decide where to redirect.
        session()->put('laravel-impersonate:leave_redirect_to', 'access-control.users');

        $currentUser->impersonate($target);

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.users.impersonate-modal');
    }
}
