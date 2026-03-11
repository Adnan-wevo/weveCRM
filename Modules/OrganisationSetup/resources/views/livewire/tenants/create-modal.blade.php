<flux:modal wire:model="show" class="w-full max-w-md">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Create Tenant') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ __('Add a new tenant to your organisation.') }}</flux:text>
    </div>

    <div class="space-y-4">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="formName" placeholder="{{ __('e.g. Acme Corporation') }}" autofocus />
            <flux:error name="formName" />
        </flux:field>
        <flux:field>
            <flux:label>
                {{ __('Tenant ID') }}
                <flux:badge size="sm" color="zinc" class="ml-1">{{ __('Optional') }}</flux:badge>
            </flux:label>
            <flux:input wire:model="formId" placeholder="{{ __('e.g. acme-corp (auto-generated if blank)') }}" />
            <flux:description>{{ __('Lowercase letters, numbers, hyphens and underscores only.') }}</flux:description>
            <flux:error name="formId" />
        </flux:field>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="create" wire:loading.attr="disabled">{{ __('Create Tenant') }}</flux:button>
    </div>
</flux:modal>
