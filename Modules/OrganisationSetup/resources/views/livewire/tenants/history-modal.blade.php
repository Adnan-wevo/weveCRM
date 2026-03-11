<flux:modal wire:model="show" class="w-full max-w-2xl">
    <div class="mb-5 flex items-start justify-between">
        <div>
            <flux:heading size="lg">{{ __('Change History') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                {{ $recordName ? __(':name — full audit trail', ['name' => $recordName]) : __('Full audit trail for this record.') }}
            </flux:text>
        </div>
    </div>

    <div class="max-h-[60vh] overflow-y-auto space-y-3 pr-1">
        @forelse ($this->audits as $audit)
            @php
                $eventColors = [
                    'created'      => 'green',
                    'updated'      => 'blue',
                    'deleted'      => 'red',
                    'restored'     => 'yellow',
                    'force-deleted'=> 'red',
                ];
                $eventIcons = [
                    'created'      => 'plus-circle',
                    'updated'      => 'pencil-square',
                    'deleted'      => 'trash',
                    'restored'     => 'arrow-path',
                    'force-deleted'=> 'fire',
                ];
                $color = $eventColors[$audit->event] ?? 'zinc';
                $icon  = $eventIcons[$audit->event]  ?? 'clock';
                $changedFields = array_unique(array_merge(
                    array_keys($audit->old_values ?? []),
                    array_keys($audit->new_values ?? [])
                ));
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50">
                {{-- Audit Header --}}
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3 dark:border-zinc-700">
                    <div class="flex items-center gap-2">
                        <flux:badge :color="$color" :icon="$icon" size="sm">
                            {{ ucfirst($audit->event) }}
                        </flux:badge>
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">
                            {{ $audit->user?->name ?? __('System') }}
                        </span>
                    </div>
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">
                        {{ $audit->created_at->format('d M Y, H:i:s') }}
                        <span class="ml-1 text-zinc-300 dark:text-zinc-600">({{ $audit->created_at->diffForHumans() }})</span>
                    </span>
                </div>

                {{-- Changed Values --}}
                @if (count($changedFields) > 0)
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($changedFields as $field)
                            @php
                                $old = $audit->old_values[$field] ?? null;
                                $new = $audit->new_values[$field] ?? null;
                                $label = ucwords(str_replace('_', ' ', $field));
                            @endphp
                            <div class="grid grid-cols-3 gap-2 px-4 py-2 text-sm">
                                <span class="font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
                                <div class="min-w-0">
                                    @if ($old !== null)
                                        <span class="inline-block max-w-full truncate rounded bg-red-50 px-1.5 py-0.5 font-mono text-xs text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                            {{ is_array($old) ? json_encode($old) : $old }}
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    @if ($new !== null)
                                        <span class="inline-block max-w-full truncate rounded bg-green-50 px-1.5 py-0.5 font-mono text-xs text-green-700 dark:bg-green-950/40 dark:text-green-400">
                                            {{ is_array($new) ? json_encode($new) : $new }}
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="px-4 py-3 text-xs italic text-zinc-400">{{ __('No field changes recorded.') }}</p>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center gap-2 py-12 text-center">
                <flux:icon name="clock" class="size-10 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('No history found') }}</p>
                <p class="text-xs text-zinc-400">{{ __('No audit records exist for this entry.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6 flex justify-end">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Close') }}</flux:button>
    </div>
</flux:modal>
