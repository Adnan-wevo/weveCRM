<flux:modal wire:model="show" class="w-full max-w-sm">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <flux:icon name="trash" class="size-5 text-red-600 dark:text-red-400" />
        </div>
        <div>
            <flux:heading>{{ __('Delete Selected Roles') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('This action cannot be undone.') }}</flux:text>
        </div>
    </div>

    <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
        {{ __('You are about to permanently delete') }}
        <strong class="font-semibold text-zinc-900 dark:text-white">{{ count($ids) }} {{ __('roles') }}</strong>.
        {{ __('Affected users will lose the permissions assigned through these roles.') }}
    </p>

    <div class="mt-5 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">{{ __('Delete All') }}</flux:button>
    </div>
</flux:modal>
