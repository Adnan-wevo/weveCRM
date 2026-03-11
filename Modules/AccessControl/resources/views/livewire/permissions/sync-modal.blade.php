<flux:modal wire:model="show" class="w-full max-w-sm">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <flux:icon name="arrow-path" class="size-5 text-amber-600 dark:text-amber-400" />
        </div>
        <div>
            <flux:heading>{{ __('Sync to All Tenants') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('This will overwrite existing tenant permissions.') }}</flux:text>
        </div>
    </div>

    <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
        {{ __('All central template permissions will be pushed to every tenant, overwriting any matching tenant permissions.') }}
    </p>

    <div class="mt-5 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="sync" wire:loading.attr="disabled">{{ __('Sync Permissions') }}</flux:button>
    </div>
</flux:modal>
