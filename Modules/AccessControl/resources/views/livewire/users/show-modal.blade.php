<flux:modal wire:model="show" class="w-full max-w-lg">
    @if ($this->user)
        <div class="mb-5">
            <flux:heading size="lg">{{ __('User Details') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">{{ __('Viewing user information.') }}</flux:text>
        </div>

        <div class="space-y-5">
            {{-- Avatar + Name --}}
            <div class="flex items-center gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                <flux:avatar :name="$this->user->name" size="lg" />
                <div>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ $this->user->name }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->user->email }}</p>
                </div>
            </div>

            {{-- Details Grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Status') }}</p>
                    @if ($this->user->email_verified_at)
                        <flux:badge color="green" icon="check-circle">{{ __('Verified') }}</flux:badge>
                    @else
                        <flux:badge color="yellow" icon="clock">{{ __('Pending') }}</flux:badge>
                    @endif
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Joined') }}</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->user->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if ($this->user->email_verified_at)
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Verified At') }}</p>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->user->email_verified_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Last Updated') }}</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->user->updated_at->format('d M Y, H:i') }}</p>
                </div>
            </div>

            {{-- Roles --}}
            <div class="space-y-2">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Assigned Roles') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($this->user->roles as $role)
                        <flux:badge color="indigo" size="sm">{{ $role->name }}</flux:badge>
                    @empty
                        <p class="text-sm text-zinc-400 italic">{{ __('No roles assigned') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Close') }}</flux:button>
            </flux:modal.close>
        </div>
    @endif
</flux:modal>
