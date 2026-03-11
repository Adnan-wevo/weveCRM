<flux:modal wire:model="show" class="w-full max-w-2xl">
    <div class="mb-5">
        <flux:heading size="lg">{{ __('Assign Domains') }}</flux:heading>
        <flux:text class="text-zinc-500">
            {{ __('Manage subdomain access for :name.', ['name' => $tenantName]) }}
        </flux:text>
    </div>

    {{-- Add Domain Form --}}
    @can('organisationsetup::tenants.assign-domain')
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
        <flux:heading size="sm" class="mb-3 font-medium">{{ __('Add Domain') }}</flux:heading>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <flux:field class="flex-1">
                <flux:label>{{ __('Domain') }}</flux:label>
                <flux:input
                    wire:model="formDomain"
                    wire:keydown.enter.prevent="addDomain"
                    type="text"
                    placeholder="{{ __('e.g. tenant1.wevetel.test') }}"
                    autofocus
                />
                <flux:error name="formDomain" />
            </flux:field>

            <div class="shrink-0">
                <flux:button
                    icon="plus"
                    variant="primary"
                    wire:click="addDomain"
                    wire:loading.attr="disabled"
                >
                    {{ __('Add') }}
                </flux:button>
            </div>
        </div>
    </div>
    @endcan

    {{-- Current Domains List --}}
    <div class="mt-5">
        <flux:heading size="sm" class="mb-3 font-medium">{{ __('Assigned Domains') }}</flux:heading>

        @if ($this->tenantDomains->isEmpty())
            <div class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-zinc-200 py-10 dark:border-zinc-700">
                <flux:icon name="globe-alt" class="size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No domains assigned to this tenant yet.') }}</p>
            </div>
        @else
            <div class="divide-y divide-zinc-100 overflow-hidden rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($this->tenantDomains as $domain)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/40">
                                <flux:icon name="globe-alt" class="size-4 text-teal-600 dark:text-teal-300" />
                            </div>
                            <p class="truncate font-mono text-sm text-zinc-800 dark:text-zinc-100">{{ $domain->domain }}</p>
                        </div>

                        @can('organisationsetup::tenants.assign-domain')
                        <flux:button
                            icon="x-mark"
                            variant="ghost"
                            size="sm"
                            wire:click="removeDomain({{ $domain->id }})"
                            wire:loading.attr="disabled"
                            wire:confirm="{{ __('Remove this domain from the tenant?') }}"
                        />
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6 flex justify-end">
        <flux:button wire:click="$set('show', false)" variant="ghost">{{ __('Close') }}</flux:button>
    </div>
</flux:modal>
