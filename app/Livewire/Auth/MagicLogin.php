<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Maize\MagicLogin\Facades\MagicLink;

#[Layout('layouts.auth')]
class MagicLogin extends Component
{
    public string $email = '';

    public bool $linkSent = false;

    /**
     * Send a magic login link to the given email address.
     *
     * To prevent user enumeration we always return the same success
     * response regardless of whether a matching account was found.
     *
     * We pass an explicit mutable Carbon expiration to avoid a CarbonImmutable
     * type mismatch in the package on Laravel 12 (now() returns CarbonImmutable).
     */
    public function send(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $this->email)->first();

        if ($user !== null) {
            // When in tenant context, direct the magic link back to the tenant dashboard.
            // In subdomain mode the {tenant} param is the slug from the request route;
            // in path mode it is the tenant ID used as the URL prefix.
            $redirectUrl = null;

            if (tenancy()->initialized && config('tenancy.mode') !== 'single') {
                $tenantParam = config('tenancy.mode') === 'path'
                    ? tenant('id')
                    : request()->route('tenant');

                if ($tenantParam) {
                    $redirectUrl = route('tenant.dashboard', ['tenant' => $tenantParam]);
                }
            }

            MagicLink::send(
                authenticatable: $user,
                expiration: Carbon::now()->addMinutes(30),
                redirectUrl: $redirectUrl,
            );
        }

        $this->linkSent = true;
        $this->reset('email');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.auth.magic-login');
    }
}
