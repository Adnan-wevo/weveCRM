@php
    $isTenant    = tenancy()->initialized;
    // In subdomain mode: slug from request route; in path mode: tenant ID; in single mode: null.
    $tenantParam = $isTenant
        ? (config('tenancy.mode') === 'path' ? tenant('id') : request()->route('tenant'))
        : null;
    $tp = $tenantParam ? ['tenant' => $tenantParam] : [];
    $loginRoute          = $isTenant ? route('tenant.login', $tp)               : route('login');
    $registerRoute       = $isTenant ? route('tenant.register', $tp)            : route('register');
    $forgotPasswordRoute = $isTenant ? route('tenant.password.request', $tp)    : route('password.request');
    $magicLoginRoute     = $isTenant ? route('tenant.magic-login.request', $tp) : route('magic-login.request');
    $keycloakRoute       = $isTenant ? route('tenant.keycloak.redirect', $tp)   : route('keycloak.redirect');
@endphp
<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        {{-- ── Keycloak SSO (shown when configured) ── --}}
        @if(config('services.keycloak.client_id'))
        <a href="{{ $keycloakRoute }}"
           class="flex w-full items-center justify-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
        >
            {{-- Keycloak logo --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="size-5" aria-hidden="true">
                <path fill="#4D9BE1" d="M456 32H56C42.7 32 32 42.7 32 56v400c0 13.3 10.7 24 24 24h400c13.3 0 24-10.7 24-24V56c0-13.3-10.7-24-24-24z"/>
                <path fill="#fff" d="M316 196l-60-60-60 60 60 60 60-60zm-60 80l-60 60h120l-60-60z"/>
            </svg>
            {{ __('Continue with Keycloak') }}
        </a>

        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('or sign in with email') }}</span>
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
        </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="$forgotPasswordRoute" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="$registerRoute" wire:navigate>{{ __('Sign up') }}</flux:link>
            </div>
        @endif

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <flux:link :href="$magicLoginRoute" wire:navigate>{{ __('Sign in with a magic link') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
