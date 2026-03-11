<flux:modal wire:model="show" class="w-full max-w-lg">
    @if ($this->permission)
        <div class="mb-5">
            <flux:heading size="lg">{{ __('Permission Details') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">{{ __('Viewing permission information.') }}</flux:text>
        </div>

        <div class="space-y-5">
            {{-- Icon + Name --}}
            <div class="flex items-center gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                <div class="flex size-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <flux:icon name="key" class="size-6 text-emerald-600 dark:text-emerald-300" />
                </div>
                <div>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ $this->permission->name }}</p>
                    <flux:badge size="sm" color="zinc">{{ $this->permission->guard_name }}</flux:badge>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Assigned To') }}</p>
                    <flux:badge color="purple">{{ $this->permission->roles->count() }} {{ __('roles') }}</flux:badge>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Created') }}</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->permission->created_at->format('d M Y, H:i') }}</p>
                </div>
            </div>

            {{-- Roles List --}}
            <div class="space-y-2">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('Roles Using This Permission') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($this->permission->roles as $role)
                        <flux:badge color="indigo" size="sm">{{ $role->name }}</flux:badge>
                    @empty
                        <p class="text-sm text-zinc-400 italic">{{ __('Not assigned to any role') }}</p>
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
