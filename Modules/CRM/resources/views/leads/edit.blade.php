<div class="p-4">
    <h2 class="text-lg font-semibold mb-3">{{ __('Edit Lead') }}</h2>

    <form wire:submit="save">
        <div class="mb-2">
            <label class="block text-sm">{{ __('Source') }}</label>
            <input wire:model="source" class="w-full rounded border px-2 py-1" />
            @error('source') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-sm">{{ __('Status') }}</label>
            <select wire:model="status" class="w-full rounded border px-2 py-1">
                <option value="new">{{ __('New') }}</option>
                <option value="contacted">{{ __('Contacted') }}</option>
                <option value="qualified">{{ __('Qualified') }}</option>
                <option value="lost">{{ __('Lost') }}</option>
            </select>
            @error('status') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white">{{ __('Save') }}</button>
            <a href="{{ route('crm.leads.index') }}" class="rounded border px-3 py-1">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
