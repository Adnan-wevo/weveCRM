@php
    $columns = [
        'name' => __('Name'),
        'email' => __('Email'),
        'created_at' => __('Created At'),
        'email_verified_at' => __('Verified At'),
    ];
    $operators = [
        '$eq' => __('Equal'),
        '$eqc' => __('Equal (case-sensitive)'),
        '$ne' => __('Not equal'),
        '$lt' => __('Less than'),
        '$lte' => __('Less than or equal to'),
        '$gt' => __('Greater than'),
        '$gte' => __('Greater than or equal to'),
        '$contains' => __('Contains'),
        '$notContains' => __('Does not contain'),
        '$containsc' => __('Contains (case-sensitive)'),
        '$notContainsc' => __('Does not contain (case-sensitive)'),
        '$startsWith' => __('Starts with'),
        '$startsWithc' => __('Starts with (case-sensitive)'),
        '$endsWith' => __('Ends with'),
        '$endsWithc' => __('Ends with (case-sensitive)'),
        '$in' => __('In list'),
        '$notIn' => __('Not in list'),
        '$between' => __('Between'),
        '$notBetween' => __('Not between'),
        '$null' => __('Is null'),
        '$notNull' => __('Is not null'),
    ];
    $noValueOps = ['$null', '$notNull'];
    $listOps = ['$in', '$notIn', '$between', '$notBetween'];
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="font-semibold">{{ __('Users') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                {{ __('Manage system users and their assigned roles.') }}
            </flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('access-control.users.import-export')
            <flux:button icon="arrows-up-down" wire:click="openImportExport" variant="outline" class="shrink-0 !text-amber-600 !border-amber-400 hover:!bg-amber-50 dark:!text-amber-400 dark:!border-amber-600 dark:hover:!bg-amber-950">{{ __('Import / Export') }}</flux:button>
            @endcan
            @can('access-control.users.create')
            @if ($tab === 'active')
            <flux:button icon="plus" wire:click="openCreate" variant="primary" class="shrink-0">{{ __('New User') }}</flux:button>
            @endif
            @endcan
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <button wire:click="$set('tab', 'active')"
            class="px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'active' ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
            {{ __('Active') }}
        </button>
        <button wire:click="$set('tab', 'trash')"
            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'trash' ? 'border-b-2 border-red-600 text-red-600 dark:border-red-400 dark:text-red-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
            <flux:icon name="trash" class="size-3.5" />
            {{ __('Trash') }}
        </button>
    </div>

    {{-- Filter Panel --}}
    <div
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        {{-- Panel Header --}}
        <div
            class="flex flex-col gap-2 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                <flux:icon name="funnel" class="size-4" />
                <span>{{ __('Search & Filters') }}</span>
                @if (count($filterRows) > 0)
                    <flux:badge size="sm" color="blue">{{ count($filterRows) }}</flux:badge>
                @endif
            </div>
            <div class="flex items-center justify-between gap-2 sm:justify-end">
                @if ($search || count($filterRows) > 0 || $sortBy !== 'name' || $sortDir !== 'asc')
                    <flux:button wire:click="resetFiltersAndSort" variant="ghost" size="sm" icon="x-mark"
                        class="text-zinc-400 hover:text-red-500">
                        {{ __('Reset') }}
                    </flux:button>
                @endif
                <span class="text-xs text-zinc-400">{{ __('Per page') }}</span>
                <flux:select wire:model.live="perPage" class="w-24 text-sm">
                    @foreach ([5, 10, 25, 50, 100, 500, 1000] as $n)
                        <flux:select.option value="{{ $n }}">{{ $n }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="space-y-3 p-4">
            {{-- Global Search --}}
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by name or email across all records…') }}" icon="magnifying-glass"
                clearable />

            {{-- Column Filter Rows --}}
            @foreach ($filterRows as $i => $row)
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2" wire:key="filter-row-{{ $i }}">
                    <flux:select wire:model.live="filterRows.{{ $i }}.col"
                        class="w-[calc(50%-4px)] min-w-0 sm:w-36 sm:shrink-0">
                        @foreach ($columns as $val => $label)
                            <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterRows.{{ $i }}.op"
                        class="w-[calc(50%-4px)] min-w-0 sm:w-44 sm:shrink-0">
                        @foreach ($operators as $val => $label)
                            <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if (!in_array($row['op'], $noValueOps))
                        <flux:input wire:model.live.debounce.400ms="filterRows.{{ $i }}.val"
                            class="min-w-0 flex-1"
                            placeholder="{{ in_array($row['op'], $listOps) ? __('value1, value2, …') : __('Value…') }}" />
                    @else
                        <div
                            class="flex-1 rounded-lg border border-dashed border-zinc-200 px-3 py-2 text-center text-xs text-zinc-400 dark:border-zinc-700">
                            {{ __('No value needed') }}
                        </div>
                    @endif

                    <flux:button wire:click="removeFilterRow({{ $i }})" variant="ghost" icon="x-mark"
                        size="sm" class="shrink-0 text-zinc-400 hover:text-red-500" />
                </div>
            @endforeach

            <flux:button wire:click="addFilterRow" variant="ghost" icon="plus" size="sm" class="text-zinc-500">
                {{ __('Add column filter') }}
            </flux:button>
        </div>
    </div>

    {{-- Table --}}
    <div
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        {{-- Table Toolbar --}}
        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->users->total() }} {{ __('users') }}
                </span>
                @if (count($selected) > 0)
                    <flux:badge size="sm" color="amber">{{ count($selected) }} {{ __('selected') }}</flux:badge>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @can('access-control.users.destroy')
                @if (count($selected) > 0)
                    <flux:button size="sm" variant="danger" wire:click="openBulkDelete" icon="trash">
                        {{ __('Delete') }}
                    </flux:button>
                @endif
                @endcan
            </div>
        </div>

        <div class="m-4 px-4 py-3">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-10 px-4">
                        <flux:checkbox wire:model.live="selectAll" />
                    </flux:table.column>
                    <flux:table.column class="w-10 text-center text-zinc-400">#</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir"
                        wire:click="sort('name')" class="cursor-pointer">
                        {{ __('User') }}
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDir"
                        wire:click="sort('email')" class="cursor-pointer">
                        {{ __('Email') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Roles') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'email_verified_at'" :direction="$sortDir"
                        wire:click="sort('email_verified_at')" class="cursor-pointer">
                        {{ __('Status') }}
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDir"
                        wire:click="sort('created_at')" class="cursor-pointer">
                        {{ __('Joined') }}
                    </flux:table.column>
                    <flux:table.column class="w-12 text-end">
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->users as $user)
                        <flux:table.row :key="$user->id" wire:key="user-{{ $user->id }}">
                            <flux:table.cell class="px-4">
                                <flux:checkbox wire:model.live="selected" value="{{ $user->id }}" />
                            </flux:table.cell>

                            <flux:table.cell class="text-center text-xs tabular-nums text-zinc-400 dark:text-zinc-500">
                                {{ $this->users->firstItem() + $loop->index }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$user->name" size="sm" class="shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-zinc-800 dark:text-zinc-100">
                                            {{ $user->name }}</p>
                                        <p class="truncate text-xs text-zinc-400">#{{ substr($user->id, 0, 8) }}</p>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ $user->email }}</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <flux:badge size="sm" color="indigo">{{ $role->name }}</flux:badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endforelse
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($user->email_verified_at)
                                    <flux:badge size="sm" color="green" icon="check-circle">{{ __('Verified') }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="yellow" icon="clock">{{ __('Pending') }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $user->created_at->format('d M Y') }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell class="text-end">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        @if ($tab === 'active')
                                        @can('access-control.users.show')
                                        <flux:menu.item icon="eye" wire:click="openShow('{{ $user->id }}')">
                                            {{ __('View') }}
                                        </flux:menu.item>
                                        @endcan
                                        @can('access-control.users.history')
                                        <flux:menu.separator />
                                        <flux:menu.item icon="clock" wire:click="openHistory('{{ $user->id }}')">
                                            {{ __('History') }}
                                        </flux:menu.item>
                                        @endcan
                                        @can('access-control.users.edit')
                                        <flux:menu.separator />
                                        @if (isset($this->lockedIds[(string) $user->id]))
                                            <flux:menu.item icon="lock-closed" disabled>
                                                {{ __('Editing — :name', ['name' => $this->lockedIds[(string) $user->id]['user_name']]) }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="pencil-square"
                                                wire:click="openEdit('{{ $user->id }}')">{{ __('Edit') }}</flux:menu.item>
                                        @endif
                                        @endcan
                                        @can('access-control.users.destroy')
                                        <flux:menu.separator />
                                        @if (isset($this->lockedIds[(string) $user->id]))
                                            <flux:menu.item icon="lock-closed" disabled>
                                                {{ __('Locked') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="trash"
                                                wire:click="openDelete('{{ $user->id }}')">{{ __('Delete') }}</flux:menu.item>
                                        @endif
                                        @endcan
                                        @can('access-control.users.impersonate')
                                        <flux:menu.separator />
                                        <flux:menu.item icon="identification" wire:click="openImpersonate('{{ $user->id }}')">
                                            {{ __('Impersonate') }}
                                        </flux:menu.item>
                                        @endcan
                                        @else
                                        @can('access-control.users.restore')
                                        <flux:menu.item icon="arrow-path" wire:click="openRestore('{{ $user->id }}')">
                                            {{ __('Restore') }}
                                        </flux:menu.item>
                                        @endcan
                                        @can('access-control.users.force-delete')
                                        <flux:menu.separator />
                                        <flux:menu.item icon="fire" wire:click="openForceDelete('{{ $user->id }}')">
                                            {{ __('Delete Forever') }}
                                        </flux:menu.item>
                                        @endcan
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="users" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                        {{ __('No users found') }}</p>
                                    <p class="text-xs text-zinc-400">{{ __('Try adjusting your search or filters.') }}
                                    </p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-col items-center gap-2 border-t border-zinc-100 px-4 py-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Showing') }}
                <strong>{{ $this->users->firstItem() ?? 0 }}</strong>–<strong>{{ $this->users->lastItem() ?? 0 }}</strong>
                {{ __('of') }} <strong>{{ $this->users->total() }}</strong> {{ __('users') }}
            </span>
            {{ $this->users->links() }}
        </div>

    </div>

    {{-- Modal Components --}}
    @livewire('accesscontrol::users.show-modal')
    @livewire('accesscontrol::users.create-modal')
    @livewire('accesscontrol::users.edit-modal')
    @livewire('accesscontrol::users.delete-modal')
    @livewire('accesscontrol::users.bulk-delete-modal')
    @livewire('accesscontrol::users.import-export-modal')
    @livewire('accesscontrol::users.restore-modal')
    @livewire('accesscontrol::users.force-delete-modal')
    @livewire('accesscontrol::users.history-modal')
    @livewire('accesscontrol::users.impersonate-modal')

</div>
