<div>
    <div class="flex items-center mb-4">
        <input wire:model.debounce.300ms="search" type="text" placeholder="Search contacts..." class="input" />
        <a href="{{ route('crm.contacts.create') }}" class="btn ml-2">New</a>
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
                <td>
                    <a href="{{ route('crm.contacts.edit', $contact) }}" class="text-blue-600">{{ $contact->name }}</a>
                </td>
                <td>{{ $contact->email }}</td>
                <td>{{ $contact->phone }}</td>
                <td>{{ $contact->company }}</td>
                <td>
                    <button onclick="if(!confirm('{{ __('Are you sure?') }}')) return false;" wire:click="deleteContact({{ $contact->id }})" class="text-red-600">{{ __('Delete') }}</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $contacts->links() }}
    </div>
</div>
