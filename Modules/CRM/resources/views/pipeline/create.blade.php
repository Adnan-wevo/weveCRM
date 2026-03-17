<div class="p-4">
    <h2 class="text-lg font-semibold mb-3">{{ __('Create Deal') }}</h2>

    <form wire:submit="save">
        <div class="mb-2">
            <label class="block text-sm">{{ __('Title') }}</label>
            <input wire:model="title" class="w-full rounded border px-2 py-1" />
            @error('title') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

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
            <label class="block text-sm">{{ __('Stage') }}</label>
            <select wire:model="stage" class="w-full rounded border px-2 py-1">
                @foreach($stages as $stage)
                    <option value="{{ $stage }}">{{ ucfirst($stage) }}</option>
                @endforeach
            </select>
            @error('stage') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Status') }}</label>
            <select wire:model="status" class="w-full rounded border px-2 py-1">
                @foreach($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2 flex gap-2">
            <div class="flex-1">
                <label class="block text-sm">{{ __('Value') }}</label>
                <input wire:model="value" type="number" step="0.01" min="0" class="w-full rounded border px-2 py-1" />
                @error('value') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div class="w-28">
                <label class="block text-sm">{{ __('Currency') }}</label>
                <input wire:model="currency" class="w-full rounded border px-2 py-1" maxlength="10" />
                @error('currency') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white">{{ __('Save') }}</button>
            <a href="{{ route('crm.pipeline.index') }}" class="rounded border px-3 py-1">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
