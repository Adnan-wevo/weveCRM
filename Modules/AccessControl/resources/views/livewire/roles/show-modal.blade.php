<flux:modal wire:model="show" class="w-full max-w-lg">
    @if ($this->role)
        <div class="mb-5">
            <flux:heading size="lg">{{ __('Role Details') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">{{ __('Viewing role information.') }}</flux:text>
        </div>

        <div class="space-y-5">
            {{-- Icon + Name --}}
            <div class="flex items-center gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                <div class="flex size-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40">
                    <flux:icon name="shield-check" class="size-6 text-purple-600 dark:text-purple-300" />
                </div>
                <div>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ $this->role->name }}</p>
                    <flux:badge size="sm" color="zinc">{{ $this->role->guard_name }}</flux:badge>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Permissions') }}</p>
                    <flux:badge color="violet">{{ $this->role->permissions->count() }} {{ __('permissions') }}</flux:badge>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Created') }}</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->role->created_at->format('d M Y, H:i') }}</p>
                </div>
            </div>

            {{-- Permissions List --}}
            <div class="space-y-2">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Assigned Permissions') }}</p>
                <div class="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto">
                    @forelse ($this->role->permissions as $permission)
                        <flux:badge color="emerald" size="sm">{{ $permission->name }}</flux:badge>
                    @empty
                        <p class="text-sm text-zinc-400 italic">{{ __('No permissions assigned') }}</p>
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
