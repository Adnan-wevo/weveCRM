<flux:modal wire:model="show" class="w-full max-w-sm">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
            <flux:icon name="arrow-path" class="size-5 text-green-600 dark:text-green-400" />
        </div>
        <div>
            <flux:heading>{{ __('Restore Permission') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('This permission will be made active again.') }}</flux:text>
        </div>
    </div>

    <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
        {{ __('You are about to restore') }} <strong class="font-semibold text-zinc-900 dark:text-white">{{ $permissionName }}</strong>.
        {{ __('Roles and users assigned to this permission will have access reinstated.') }}
    </p>

    <div class="mt-5 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="restore" wire:loading.attr="disabled">{{ __('Restore Permission') }}</flux:button>
    </div>
</flux:modal>
