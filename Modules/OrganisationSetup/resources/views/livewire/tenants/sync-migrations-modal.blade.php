<flux:modal wire:model="show" class="w-full max-w-md">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
            <flux:icon name="arrow-path" class="size-5 text-blue-600 dark:text-blue-400" />
        </div>
        <div>
            <flux:heading>{{ __('Sync Migrations & Seed') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('Run pending migrations and seeders on all tenants.') }}</flux:text>
        </div>
    </div>

    @if ($status === 'idle')
        <div class="space-y-4">
            <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                {{ __('This will run any pending migrations on all tenant databases. Optionally, seeders and permission sync will also be executed.') }}
            </p>

            <div class="space-y-3">
                <flux:checkbox wire:model="withSeed" label="{{ __('Run database seeders') }}" />
                <flux:checkbox wire:model="withPermissionSync" label="{{ __('Sync roles & permissions') }}" />
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <flux:button variant="filled" wire:click="close">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="sync" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sync">{{ __('Run Sync') }}</span>
                <span wire:loading wire:target="sync">{{ __('Syncing…') }}</span>
            </flux:button>
        </div>
    @elseif ($status === 'running')
        <div class="flex items-center gap-3 py-4">
            <flux:icon name="arrow-path" class="size-5 animate-spin text-blue-500" />
            <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Running migrations on all tenants, please wait…') }}</span>
        </div>
    @else
        <div class="space-y-4">
            @if ($status === 'done')
                <div class="flex items-center gap-2">
                    <flux:badge color="green" icon="check-circle">{{ __('Completed') }}</flux:badge>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <flux:badge color="yellow" icon="exclamation-triangle">{{ __('Completed with errors') }}</flux:badge>
                </div>
            @endif

            <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                {{ $resultMessage }}
            </p>

            @if (count($errorDetails) > 0)
                <div class="max-h-48 space-y-2 overflow-y-auto">
                    @foreach ($errorDetails as $detail)
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-950">
                            <p class="text-xs font-semibold text-red-700 dark:text-red-400">{{ $detail['tenant'] }}</p>
                            <p class="mt-0.5 break-all text-xs text-red-600 dark:text-red-300">{{ $detail['error'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-5 flex justify-end">
            <flux:button variant="filled" wire:click="close">{{ __('Close') }}</flux:button>
        </div>
    @endif
</flux:modal>
