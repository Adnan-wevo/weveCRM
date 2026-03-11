<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif

        @if(config('services.keycloak.client_id'))
            <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Keycloak') }}</flux:heading>
                <flux:subheading class="mt-1">{{ __('Link your account to sign in with Keycloak SSO.') }}</flux:subheading>

                @if(session('status'))
                    <flux:text class="mt-3 font-medium text-green-600 dark:text-green-400">
                        {{ session('status') }}
                    </flux:text>
                @endif

                @error('keycloak')
                    <flux:text class="mt-3 text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div class="mt-4">
                    @if(Auth::user()->keycloak_id)
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="size-6 shrink-0" aria-hidden="true">
                                    <path fill="#4D9BE1" d="M456 32H56C42.7 32 32 42.7 32 56v400c0 13.3 10.7 24 24 24h400c13.3 0 24-10.7 24-24V56c0-13.3-10.7-24-24-24z"/>
                                    <path fill="#fff" d="M316 196l-60-60-60 60 60 60 60-60zm-60 80l-60 60h120l-60-60z"/>
                                </svg>
                                <div>
                                    <flux:text class="font-medium">Keycloak</flux:text>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Connected') }}</flux:text>
                                </div>
                            </div>
                            @if(Auth::user()->password)
                                <form method="POST" action="{{ route('keycloak.unlink') }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" variant="ghost" size="sm">
                                        {{ __('Unlink') }}
                                    </flux:button>
                                </form>
                            @else
                                <flux:text class="text-sm italic text-zinc-400 dark:text-zinc-500">
                                    {{ __('No password set — cannot unlink') }}
                                </flux:text>
                            @endif
                        </div>
                    @else
                        <a href="{{ route('keycloak.link') }}"
                           class="flex w-full items-center justify-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="size-5" aria-hidden="true">
                                <path fill="#4D9BE1" d="M456 32H56C42.7 32 32 42.7 32 56v400c0 13.3 10.7 24 24 24h400c13.3 0 24-10.7 24-24V56c0-13.3-10.7-24-24-24z"/>
                                <path fill="#fff" d="M316 196l-60-60-60 60 60 60 60-60zm-60 80l-60 60h120l-60-60z"/>
                            </svg>
                            {{ __('Link Keycloak account') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </x-settings.layout>
</section>
