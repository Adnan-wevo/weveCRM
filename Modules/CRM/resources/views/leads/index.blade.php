<div>
    <div class="flex items-center mb-4">
        <input wire:model.debounce.300ms="search" type="text" placeholder="Search leads..." class="input" />
        <button wire:click="$emit('openCreateLead')" class="btn ml-2">New Lead</button>
    </div>

    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th>Source</th>
                <th>Status</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leads as $lead)
            <tr>
                <td>{{ $lead->source }}</td>
                <td>{{ $lead->status }}</td>
                <td>{{ optional($lead->contact)->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $leads->links() }}
    </div>
</div>
