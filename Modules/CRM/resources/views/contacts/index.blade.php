<div>
    <div class="flex items-center mb-4">
        <input wire:model.debounce.300ms="search" type="text" placeholder="Search contacts..." class="input" />
        <button wire:click="$emit('openCreateContact')" class="btn ml-2">New</button>
    </div>

    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Company</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contacts as $contact)
            <tr>
                <td>{{ $contact->name }}</td>
                <td>{{ $contact->email }}</td>
                <td>{{ $contact->phone }}</td>
                <td>{{ $contact->company }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $contacts->links() }}
    </div>
</div>
