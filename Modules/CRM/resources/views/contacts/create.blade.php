<div class="p-4">
    <h2 class="text-lg font-semibold mb-3">{{ __('Create Contact') }}</h2>

    <form wire:submit.prevent="save">
        <div class="mb-2">
            <label class="block text-sm">{{ __('Name') }}</label>
            <input wire:model.defer="name" class="w-full rounded border px-2 py-1" />
            @error('name') <div class="text-red-600">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Email') }}</label>
            <input wire:model.defer="email" class="w-full rounded border px-2 py-1" />
            @error('email') <div class="text-red-600">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Phone') }}</label>
            <input wire:model.defer="phone" class="w-full rounded border px-2 py-1" />
            @error('phone') <div class="text-red-600">{{ $message }}</div> @enderror
        </div>

        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white">{{ __('Save') }}</button>
            <a href="{{ route('crm.contacts.index') }}" class="rounded border px-3 py-1">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
