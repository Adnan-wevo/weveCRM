<flux:modal wire:model="show" class="w-full max-w-md">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Edit Tenant') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ __('Update tenant information.') }}</flux:text>
    </div>

    <div class="space-y-4">
        <flux:field>
            <flux:label>{{ __('Tenant ID') }}</flux:label>
            <div class="flex items-center gap-2">
                <flux:badge color="zinc">{{ $tenantId }}</flux:badge>
                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('ID cannot be changed') }}</span>
            </div>
        </flux:field>
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="formName" autofocus />
            <flux:error name="formName" />
        </flux:field>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="update" wire:loading.attr="disabled">{{ __('Save Changes') }}</flux:button>
    </div>
</flux:modal>
