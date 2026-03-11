<flux:modal wire:model="show" class="w-full max-w-2xl">

    {{-- Polling for background job statuses --}}
    @if ($importStatus === 'queued' || $exportStatus === 'queued')
        <div wire:poll.3000ms="checkJobStatuses" class="hidden" aria-hidden="true"></div>
    @endif

    <div class="mb-5">
        <flux:heading size="lg">{{ __('Import / Export Roles') }}</flux:heading>
        <flux:text class="text-zinc-500">
            {{ __('Export existing roles to a spreadsheet or import new roles from a file.') }}
        </flux:text>
    </div>

    @if ($step === 'idle')

        <div class="space-y-6">

            {{-- Export Section --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center gap-2">
                    <flux:icon.arrow-down-tray class="size-5 text-zinc-500" />
                    <flux:heading size="sm">{{ __('Export') }}</flux:heading>
                </div>
                <flux:text class="mb-4 text-sm text-zinc-500">
                    {{ __('Download all roles as an Excel spreadsheet including their permissions.') }}
                </flux:text>

                @if ($exportStatus === 'done')
                    <div class="flex items-center gap-3">
                        <flux:badge color="green" icon="check-circle">{{ __('Ready') }}</flux:badge>
                        <a href="{{ $exportUrl }}"
                           download="{{ $exportFilename }}"
                           class="text-sm font-medium text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            {{ $exportFilename }}
                        </a>
                    </div>
                @elseif ($exportStatus === 'queued')
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <flux:icon.arrow-path class="size-4 animate-spin" />
                        {{ __('Generating export, please wait…') }}
                    </div>
                @elseif ($exportStatus === 'failed')
                    <flux:badge color="red" icon="x-circle">{{ __('Export failed. Please try again.') }}</flux:badge>
                @else
                    <flux:button icon="arrow-down-tray" wire:click="export" wire:loading.attr="disabled">
                        {{ __('Export Roles') }}
                    </flux:button>
                @endif
            </div>

            {{-- Import Section --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center gap-2">
                    <flux:icon.arrow-up-tray class="size-5 text-zinc-500" />
                    <flux:heading size="sm">{{ __('Import') }}</flux:heading>
                </div>
                <flux:text class="mb-1 text-sm text-zinc-500">
                    {{ __('Upload a CSV or Excel file to create new roles. Expected columns:') }}
                </flux:text>
                <p class="mb-4 font-mono text-xs text-zinc-400">name, guard_name, permissions</p>

                @if ($importStatus === 'queued')
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <flux:icon.arrow-path class="size-4 animate-spin" />
                        {{ __('Import queued, processing in background…') }}
                    </div>
                @elseif ($importStatus === 'done')
                    <div class="flex items-center gap-2">
                        <flux:badge color="green" icon="check-circle">{{ __('Import complete') }}</flux:badge>
                        @if ($importFailureCount > 0)
                            <flux:badge color="yellow">
                                {{ __(':count row(s) skipped', ['count' => $importFailureCount]) }}
                            </flux:badge>
                        @endif
                    </div>
                @elseif ($importStatus === 'failed')
                    <flux:badge color="red" icon="x-circle">{{ __('Import failed. Please try again.') }}</flux:badge>
                @else
                    <flux:field>
                        <flux:label>{{ __('File') }}</flux:label>
                        <input type="file"
                               wire:model="uploadedFile"
                               accept=".csv,.xlsx,.xls,.txt"
                               class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-600 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                        <flux:error name="uploadedFile" />
                    </flux:field>
                @endif
            </div>

        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Close') }}</flux:button>
            @if ($importStatus === '' || $importStatus === 'done' || $importStatus === 'failed')
                <flux:button variant="primary" wire:click="previewImport" wire:loading.attr="disabled" wire:target="previewImport,uploadedFile">
                    <span wire:loading.remove wire:target="previewImport,uploadedFile">{{ __('Preview Import') }}</span>
                    <span wire:loading wire:target="previewImport,uploadedFile">{{ __('Reading file…') }}</span>
                </flux:button>
            @endif
        </div>

    @elseif ($step === 'preview')

        {{-- Preview Table --}}
        <div class="mb-4 flex items-center gap-3">
            @if ($validCount > 0)
                <flux:badge color="green" icon="check-circle">
                    {{ __(':count valid row(s)', ['count' => $validCount]) }}
                </flux:badge>
            @endif
            @if ($invalidCount > 0)
                <flux:badge color="red" icon="x-circle">
                    {{ __(':count invalid row(s) will be skipped', ['count' => $invalidCount]) }}
                </flux:badge>
            @endif
        </div>

        <div class="max-h-72 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Name') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Guard') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Permissions') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @foreach ($previewRows as $i => $row)
                        <tr class="{{ $row['valid'] ? '' : 'bg-red-50 dark:bg-red-900/10' }}">
                            <td class="px-3 py-2 text-xs text-zinc-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2">{{ $row['data']['name'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-500">{{ $row['data']['guard_name'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-500">{{ $row['data']['permissions'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($row['valid'])
                                    <flux:badge color="green" size="sm">{{ __('Valid') }}</flux:badge>
                                @else
                                    <div class="space-y-0.5">
                                        @foreach ($row['errors'] as $field => $messages)
                                            @foreach ($messages as $message)
                                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @endforeach
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="backToIdle">{{ __('Back') }}</flux:button>
            @if ($validCount > 0)
                <flux:button variant="primary" wire:click="confirmImport" wire:loading.attr="disabled">
                    {{ __('Import :count row(s)', ['count' => $validCount]) }}
                </flux:button>
            @endif
        </div>

    @endif

</flux:modal>
