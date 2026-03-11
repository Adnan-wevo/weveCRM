<flux:modal wire:model="show" class="w-full max-w-md">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Edit Permission') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ __('Update permission name and guard.') }}</flux:text>
    </div>

    <div class="space-y-4">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="formName" />
            <flux:error name="formName" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('Guard') }}</flux:label>
            <flux:input wire:model="formGuard" />
            <flux:error name="formGuard" />
        </flux:field>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="update" wire:loading.attr="disabled">{{ __('Save Changes') }}</flux:button>
    </div>
</flux:modal>
