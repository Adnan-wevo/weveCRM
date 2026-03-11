<flux:modal wire:model="show" class="w-full max-w-lg">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Edit User') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ __('Update user account details and roles.') }}</flux:text>
    </div>

    <div class="space-y-4">

        {{-- Avatar --}}
        <flux:field>
            <flux:label>{{ __('Profile Image') }}</flux:label>
            <div class="flex items-center gap-4">

                {{-- Preview --}}
                <div class="size-16 shrink-0 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    @if ($this->avatarPreviewUrl)
                        <img src="{{ $this->avatarPreviewUrl }}" class="size-full object-cover" alt="">
                    @elseif ($currentAvatarUrl && ! $removeAvatar)
                        <img src="{{ $currentAvatarUrl }}" class="size-full object-cover" alt="">
                    @else
                        <div class="flex size-full items-center justify-center text-lg font-semibold text-zinc-400 uppercase">
                            {{ substr($formName ?: '?', 0, 1) }}
                        </div>
                    @endif
                </div>

                <div class="flex-1 space-y-2">
                    <input type="file"
                           wire:model="formAvatar"
                           accept="image/png"
                           class="block w-full text-sm text-zinc-600 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-600 hover:file:bg-zinc-200 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                    @if ($currentAvatarUrl && ! $formAvatar)
                        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-red-500 hover:text-red-700">
                            <flux:checkbox wire:model="removeAvatar" />
                            {{ __('Remove current photo') }}
                        </label>
                    @endif
                </div>
            </div>
            <flux:error name="formAvatar" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="formName" />
            <flux:error name="formName" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('Email') }}</flux:label>
            <flux:input wire:model="formEmail" type="email" />
            <flux:error name="formEmail" />
        </flux:field>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>
                    {{ __('New Password') }}
                    <span class="ml-1 text-xs text-zinc-400">({{ __('optional') }})</span>
                </flux:label>
                <flux:input wire:model="formPassword" type="password" placeholder="{{ __('Leave blank to keep') }}" />
                <flux:error name="formPassword" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Confirm Password') }}</flux:label>
                <flux:input wire:model="formPasswordConfirm" type="password" />
                <flux:error name="formPasswordConfirm" />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>{{ __('Roles') }}</flux:label>
            <div class="grid grid-cols-2 gap-1 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                @foreach ($this->allRoles as $role)
                    <flux:checkbox wire:model="formRoles" value="{{ $role->id }}" :label="$role->name" />
                @endforeach
            </div>
        </flux:field>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="update" wire:loading.attr="disabled">{{ __('Save Changes') }}</flux:button>
    </div>
</flux:modal>
