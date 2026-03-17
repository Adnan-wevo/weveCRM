<div class="p-4">
    <h2 class="mb-3 text-lg font-semibold">{{ __('Form Builder') }}</h2>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-50 px-3 py-2 text-green-700">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="mb-6 space-y-3 rounded border p-4">
        <div>
            <label class="mb-1 block text-sm">{{ __('Form Name') }}</label>
            <input wire:model="name" class="w-full rounded border px-2 py-1" placeholder="{{ __('Lead Capture Form') }}" />
            @error('name') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm">{{ __('Schema (JSON)') }}</label>
            <textarea wire:model="schema" rows="8" class="w-full rounded border px-2 py-1 font-mono text-sm"></textarea>
            @error('schema') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white">{{ __('Save Form') }}</button>
    </form>

    <div>
        <h3 class="mb-2 text-sm font-semibold">{{ __('Saved Forms') }}</h3>
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="px-2 py-1 text-left">{{ __('Name') }}</th>
                    <th class="px-2 py-1 text-left">{{ __('Active') }}</th>
                    <th class="px-2 py-1 text-left">{{ __('Created') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr>
                        <td class="px-2 py-1">{{ $form->name }}</td>
                        <td class="px-2 py-1">{{ $form->is_active ? __('Yes') : __('No') }}</td>
                        <td class="px-2 py-1">{{ $form->created_at?->toDateTimeString() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-2 py-2 text-sm text-gray-500">{{ __('No forms yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $forms->links() }}
        </div>
    </div>
</div>
