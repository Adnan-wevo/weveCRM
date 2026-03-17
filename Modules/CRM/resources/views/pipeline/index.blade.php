<div>
    <div class="flex items-center mb-4 gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search deals...') }}" class="input" />
        <select wire:model.live="filterStage" class="rounded border px-2 py-1">
            <option value="">{{ __('All Stages') }}</option>
            @foreach($stages as $stage)
                <option value="{{ $stage }}">{{ ucfirst($stage) }}</option>
            @endforeach
        </select>
        <a href="{{ route('crm.pipeline.create') }}" class="btn ml-auto">{{ __('New Deal') }}</a>
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
                <th class="text-left p-2">{{ __('Title') }}</th>
                <th class="text-left p-2">{{ __('Stage') }}</th>
                <th class="text-left p-2">{{ __('Status') }}</th>
                <th class="text-left p-2">{{ __('Value') }}</th>
                <th class="text-left p-2">{{ __('Contact') }}</th>
                <th class="text-left p-2">{{ __('Owner') }}</th>
                <th class="text-left p-2">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deals as $deal)
            <tr class="border-t">
                <td class="p-2">{{ $deal->title }}</td>
                <td class="p-2">
                    <span class="rounded px-2 py-0.5 text-xs font-medium
                        @if($deal->stage === 'won') bg-green-100 text-green-700
                        @elseif($deal->stage === 'lost') bg-red-100 text-red-700
                        @elseif($deal->stage === 'negotiation') bg-yellow-100 text-yellow-700
                        @else bg-blue-100 text-blue-700 @endif">
                        {{ ucfirst($deal->stage) }}
                    </span>
                </td>
                <td class="p-2">{{ ucfirst($deal->status) }}</td>
                <td class="p-2">{{ $deal->currency }} {{ number_format($deal->value, 2) }}</td>
                <td class="p-2">{{ optional($deal->contact)->name ?? '—' }}</td>
                <td class="p-2">{{ optional($deal->owner)->name ?? '—' }}</td>
                <td class="p-2">
                    <a href="{{ route('crm.pipeline.edit', $deal) }}" class="text-blue-600 mr-2">{{ __('Edit') }}</a>
                    <button wire:click="deleteDeal('{{ $deal->id }}')" onclick="if(!confirm('{{ __('Are you sure?') }}')) return false;" class="text-red-600">{{ __('Delete') }}</button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-4 text-center text-gray-500">{{ __('No deals found.') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $deals->links() }}
    </div>
</div>
