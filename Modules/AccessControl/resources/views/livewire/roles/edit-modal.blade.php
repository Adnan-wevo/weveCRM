<flux:modal wire:model="show" class="w-full max-w-lg">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Edit Role') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ __('Update role details and its permissions.') }}</flux:text>
    </div>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
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
        <flux:field>
            <flux:label>{{ __('Permissions') }}</flux:label>
            <div class="grid max-h-52 grid-cols-2 gap-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                @foreach ($this->allPermissions as $perm)
                    <flux:checkbox wire:model="formPermissions" value="{{ $perm->id }}" :label="$perm->name" />
                @endforeach
            </div>
        </flux:field>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="update" wire:loading.attr="disabled">{{ __('Save Changes') }}</flux:button>
    </div>
</flux:modal>
