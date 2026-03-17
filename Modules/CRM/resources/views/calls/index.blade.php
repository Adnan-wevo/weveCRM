<div>
    <div class="flex items-center mb-4 gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search calls...') }}" class="input" />
        <a href="{{ route('crm.calls.create') }}" class="btn ml-auto">{{ __('Log Call') }}</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded bg-green-50 px-4 py-2 text-green-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded bg-red-50 px-4 py-2 text-red-700">{{ session('error') }}</div>
    @endif

    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th class="text-left p-2">{{ __('Called At') }}</th>
                <th class="text-left p-2">{{ __('Contact') }}</th>
                <th class="text-left p-2">{{ __('Direction') }}</th>
                <th class="text-left p-2">{{ __('Duration (s)') }}</th>
                <th class="text-left p-2">{{ __('User') }}</th>
                <th class="text-left p-2">{{ __('Notes') }}</th>
                <th class="text-left p-2">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($calls as $call)
            <tr class="border-t">
                <td class="p-2">{{ optional($call->called_at)->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="p-2">{{ optional($call->contact)->name ?? '—' }}</td>
                <td class="p-2">{{ ucfirst($call->direction) }}</td>
                <td class="p-2">{{ $call->duration ?? '—' }}</td>
                <td class="p-2">{{ optional($call->user)->name ?? '—' }}</td>
                <td class="p-2">{{ \Illuminate\Support\Str::limit((string) $call->notes, 60) }}</td>
                <td class="p-2">
                    <button wire:click="deleteCall('{{ $call->id }}')" onclick="if(!confirm('{{ __('Are you sure?') }}')) return false;" class="text-red-600">{{ __('Delete') }}</button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-4 text-center text-gray-500">{{ __('No calls found.') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $calls->links() }}
    </div>
</div>
