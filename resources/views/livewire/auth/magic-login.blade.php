@php
    $isTenant    = tenancy()->initialized;
    $tenantParam = $isTenant
        ? (config('tenancy.mode') === 'path' ? tenant('id') : request()->route('tenant'))
        : null;
    $tp = $tenantParam ? ['tenant' => $tenantParam] : [];
    $loginRoute = $isTenant ? route('tenant.login', $tp) : route('login');
@endphp
<div class="flex flex-col gap-6">
    @if ($this->linkSent)
        <x-auth-header
            :title="__('Check your email')"
            :description="__('If an account exists for that address, we\'ve sent a sign-in link. It expires in 30 minutes.')"
        />

        <flux:button variant="primary" wire:click="$set('linkSent', false)" class="w-full">
            {{ __('Send another link') }}
        </flux:button>
    @else
        <x-auth-header
            :title="__('Sign in with a magic link')"
            :description="__('Enter your email and we\'ll send you a one-click sign-in link — no password needed.')"
        />

        <form wire:submit="send" class="flex flex-col gap-6">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" wire:loading.attr="disabled" class="w-full">
                <span wire:loading.remove>{{ __('Send magic link') }}</span>
                <span wire:loading>{{ __('Sending…') }}</span>
            </flux:button>
        </form>
    @endif

    <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Or, return to') }}</span>
        <flux:link :href="$loginRoute" wire:navigate>{{ __('sign in') }}</flux:link>
    </div>
</div>
