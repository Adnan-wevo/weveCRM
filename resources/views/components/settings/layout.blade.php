@php
    $isTenant     = tenancy()->initialized;
    // Resolve the {tenant} route parameter for the current tenancy mode.
    $tenantParam  = $isTenant
        ? (config('tenancy.mode') === 'path' ? tenant('id') : request()->route('tenant'))
        : null;
    $tenantParams = $tenantParam ? ['tenant' => $tenantParam] : [];

    $profileUrl    = ($isTenant && $tenantParam) ? route('tenant.profile.edit',       $tenantParams) : route('profile.edit');
    $passwordUrl   = ($isTenant && $tenantParam) ? route('tenant.user-password.edit', $tenantParams) : route('user-password.edit');
    $twoFactorUrl  = ($isTenant && $tenantParam) ? route('tenant.two-factor.show',    $tenantParams) : route('two-factor.show');
    $appearanceUrl = ($isTenant && $tenantParam) ? route('tenant.appearance.edit',    $tenantParams) : route('appearance.edit');
@endphp
<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="$profileUrl" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="$passwordUrl" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <flux:navlist.item :href="$twoFactorUrl" wire:navigate>{{ __('Two-Factor Auth') }}</flux:navlist.item>
            @endif
            <flux:navlist.item :href="$appearanceUrl" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
