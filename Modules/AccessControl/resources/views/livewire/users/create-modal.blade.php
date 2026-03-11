<flux:modal wire:model="show" class="w-full max-w-lg">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Create User') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ __('Add a new user account to the system.') }}</flux:text>
    </div>

    <div class="space-y-4">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="formName" placeholder="{{ __('Full name') }}" autofocus />
            <flux:error name="formName" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('Email') }}</flux:label>
            <flux:input wire:model="formEmail" type="email" placeholder="{{ __('email@example.com') }}" />
            <flux:error name="formEmail" />
        </flux:field>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input wire:model="formPassword" type="password" placeholder="{{ __('Min. 8 chars') }}" />
                <flux:error name="formPassword" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Confirm Password') }}</flux:label>
                <flux:input wire:model="formPasswordConfirm" type="password" placeholder="{{ __('Repeat') }}" />
                <flux:error name="formPasswordConfirm" />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>{{ __('Roles') }}</flux:label>
            <div class="grid grid-cols-2 gap-1 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                @foreach ($this->allRoles as $role)
                    <flux:checkbox wire:model="formRoles" value="{{ $role->id }}" :label="$role->name" />
                @endforeach
                @if ($this->allRoles->isEmpty())
                    <p class="col-span-2 text-sm text-zinc-400">{{ __('No roles available.') }}</p>
                @endif
            </div>
            <flux:error name="formRoles" />
        </flux:field>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="create" wire:loading.attr="disabled">{{ __('Create User') }}</flux:button>
    </div>
</flux:modal>
