<div class="p-4">
    <h2 class="text-lg font-semibold mb-3">{{ __('Log Call') }}</h2>

    <form wire:submit="save">
        <div class="mb-2">
            <label class="block text-sm">{{ __('Contact') }}</label>
            <select wire:model="contact_id" class="w-full rounded border px-2 py-1">
                <option value="">{{ __('— None —') }}</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                @endforeach
            </select>
            @error('contact_id') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Direction') }}</label>
            <select wire:model="direction" class="w-full rounded border px-2 py-1">
                <option value="outbound">{{ __('Outbound') }}</option>
                <option value="inbound">{{ __('Inbound') }}</option>
            </select>
            @error('direction') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Called At') }}</label>
            <input wire:model="called_at" type="datetime-local" class="w-full rounded border px-2 py-1" />
            @error('called_at') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Duration (seconds)') }}</label>
            <input wire:model="duration" type="number" min="0" class="w-full rounded border px-2 py-1" />
            @error('duration') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Notes') }}</label>
            <textarea wire:model="notes" rows="4" class="w-full rounded border px-2 py-1"></textarea>
            @error('notes') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white">{{ __('Save') }}</button>
            <a href="{{ route('crm.calls.index') }}" class="rounded border px-3 py-1">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
