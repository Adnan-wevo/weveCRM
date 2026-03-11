@php
    $isTenant    = tenancy()->initialized;
    $tenantParam = $isTenant
        ? (config('tenancy.mode') === 'path' ? tenant('id') : request()->route('tenant'))
        : null;
    $tp = $tenantParam ? ['tenant' => $tenantParam] : [];
    $loginRoute            = $isTenant ? route('tenant.login', $tp)             : route('login');
    $keycloakRegisterRoute = $isTenant ? route('tenant.keycloak.register', $tp) : route('keycloak.register');
@endphp
<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        {{-- ── Keycloak SSO (shown when configured) ── --}}
        @if(config('services.keycloak.client_id'))
        <a href="{{ $keycloakRegisterRoute }}"
           class="flex w-full items-center justify-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="size-5" aria-hidden="true">
                <path fill="#4D9BE1" d="M456 32H56C42.7 32 32 42.7 32 56v400c0 13.3 10.7 24 24 24h400c13.3 0 24-10.7 24-24V56c0-13.3-10.7-24-24-24z"/>
                <path fill="#fff" d="M316 196l-60-60-60 60 60 60 60-60zm-60 80l-60 60h120l-60-60z"/>
            </svg>
            {{ __('Continue with Keycloak') }}
        </a>

        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('or register with email') }}</span>
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
        </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="$loginRoute" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
