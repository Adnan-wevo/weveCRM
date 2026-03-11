<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        {{-- Tenant info card --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#0c4d65] text-lg font-bold text-white dark:bg-[#072d3d]">
                        {{ strtoupper(substr((string) tenant('id'), 0, 2)) }}
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ tenant()->name ?? tenant('id') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Tenant ID') }}: <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ tenant('id') }}</span>
                        </p>
                    </div>
                </div>
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            </div>
        </div>

        {{-- Placeholder metric cards --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
