<flux:modal wire:model="show" class="w-full max-w-sm">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <flux:icon name="fire" class="size-5 text-red-600 dark:text-red-400" />
        </div>
        <div>
            <flux:heading>{{ __('Permanently Delete Role') }}</flux:heading>
            <flux:text class="text-red-500">{{ __('This cannot be undone.') }}</flux:text>
        </div>
    </div>

    <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
        {{ __('You are about to permanently erase') }} <strong class="font-semibold">{{ $roleName }}</strong>.
        {{ __('All user assignments for this role will be irreversibly removed from the database.') }}
    </p>

    <div class="mt-5 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="danger" wire:click="forceDelete" wire:loading.attr="disabled">{{ __('Delete Forever') }}</flux:button>
    </div>
</flux:modal>
