<flux:modal wire:model="show" class="w-full max-w-2xl">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Manage Users') }}</flux:heading>
        <flux:text class="text-zinc-500">
            {{ __('Add or remove users for :name.', ['name' => $tenantName]) }}
        </flux:text>
    </div>

    {{-- Add User Form --}}
    @can('organisationsetup::tenants.add-user')
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
        <flux:heading size="sm" class="mb-3 font-medium">{{ __('Add User') }}</flux:heading>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <flux:field class="flex-1">
                <flux:label>{{ __('Email Address') }}</flux:label>
                <flux:input
                    wire:model="formUserEmail"
                    type="email"
                    placeholder="{{ __('user@example.com') }}"
                    autofocus
                />
                <flux:error name="formUserEmail" />
            </flux:field>

            <flux:field class="flex-1">
                <flux:label>
                    {{ __('Role') }}
                    <flux:badge size="sm" color="zinc" class="ml-1">{{ __('Optional') }}</flux:badge>
                </flux:label>
                <flux:select wire:model="formRoleId" placeholder="{{ __('Select a role') }}">
                    @foreach ($this->tenantRoles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="formRoleId" />
            </flux:field>

            <div class="shrink-0">
                <flux:button
                    icon="user-plus"
                    variant="primary"
                    wire:click="addUser"
                    wire:loading.attr="disabled"
                >
                    {{ __('Add') }}
                </flux:button>
            </div>
        </div>
    </div>
    @endcan

    {{-- Current Users List --}}
    <div class="mt-5">
        <flux:heading size="sm" class="mb-3 font-medium">{{ __('Current Users') }}</flux:heading>

        @if ($this->tenantUsers->isEmpty())
            <div class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-zinc-200 py-10 dark:border-zinc-700">
                <flux:icon name="users" class="size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No users in this tenant yet.') }}</p>
            </div>
        @else
            <div class="divide-y divide-zinc-100 overflow-hidden rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($this->tenantUsers as $user)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                                <flux:icon name="user" class="size-4 text-blue-600 dark:text-blue-300" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $user->name }}</p>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if ($user->roles->isNotEmpty())
                                <div class="hidden gap-1 sm:flex">
                                    @foreach ($user->roles as $role)
                                        <flux:badge size="sm" color="indigo">{{ $role->name }}</flux:badge>
                                    @endforeach
                                </div>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('No role') }}</flux:badge>
                            @endif

                            @can('organisationsetup::tenants.remove-user')
                            <flux:button
                                icon="x-mark"
                                variant="ghost"
                                size="sm"
                                wire:click="removeUser('{{ $user->id }}')"
                                wire:loading.attr="disabled"
                                wire:confirm="{{ __('Remove this user from the tenant?') }}"
                            />
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2">
                {{ $this->tenantUsers->links() }}
            </div>
        @endif
    </div>

    <div class="mt-6 flex justify-end">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Close') }}</flux:button>
    </div>
</flux:modal>
