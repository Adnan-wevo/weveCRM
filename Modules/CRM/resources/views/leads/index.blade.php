<div>
    <div class="flex items-center mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search leads..." class="input" />
        <a href="{{ route('crm.leads.create') }}" class="btn ml-2">{{ __('New Lead') }}</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded bg-green-50 px-4 py-2 text-green-700">{{ session('success') }}</div>
    @endif

    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th>{{ __('Source') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Contact') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leads as $lead)
            <tr>
                <td>{{ $lead->source }}</td>
                <td>{{ $lead->status }}</td>
                <td>{{ optional($lead->contact)->name }}</td>
                <td>
                    <a href="{{ route('crm.leads.edit', $lead) }}" class="text-blue-600 mr-2">{{ __('Edit') }}</a>
                    <button wire:click="deleteLead('{{ $lead->id }}')" onclick="if(!confirm('{{ __('Are you sure?') }}')) return false;" class="text-red-600">{{ __('Delete') }}</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $leads->links() }}
    </div>
</div>
