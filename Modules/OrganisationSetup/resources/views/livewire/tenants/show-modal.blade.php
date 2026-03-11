<flux:modal wire:model="show" class="w-full max-w-lg">
    @if ($this->tenant)
        <div class="mb-5">
            <flux:heading size="lg">{{ __('Tenant Details') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">{{ __('Viewing tenant information.') }}</flux:text>
        </div>

        <div class="space-y-5">
            {{-- Icon + Name --}}
            <div class="flex items-center gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                <div class="flex size-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                    <flux:icon name="building-office" class="size-6 text-blue-600 dark:text-blue-300" />
                </div>
                <div>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ $this->tenant->name }}</p>
                    <flux:badge size="sm" color="zinc">{{ $this->tenant->id }}</flux:badge>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Domains') }}</p>
                    <flux:badge color="indigo">{{ $this->tenant->domains->count() }} {{ __('domains') }}</flux:badge>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Created') }}</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->tenant->created_at->format('d M Y, H:i') }}</p>
                </div>
            </div>

            @if ($this->tenant->deleted_at)
                <div class="space-y-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Deleted At') }}</p>
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $this->tenant->deleted_at->format('d M Y, H:i') }}</p>
                </div>
            @endif

            {{-- Domains List --}}
            <div class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Domains') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($this->tenant->domains as $domain)
                        <flux:badge color="blue" size="sm">{{ $domain->domain }}</flux:badge>
                    @empty
                        <p class="text-sm italic text-zinc-400">{{ __('No domains configured') }}</p>
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
