<flux:modal wire:model="show" class="w-full max-w-md">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <flux:icon name="identification" class="size-5 text-amber-600 dark:text-amber-400" />
        </div>
        <div>
            <flux:heading>{{ __('Impersonate User') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('You will be logged in as this user.') }}</flux:text>
        </div>
    </div>

    <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
        {{ __('You are about to impersonate') }}
        <strong class="font-semibold text-zinc-900 dark:text-white">{{ $userName }}</strong>
        <span class="text-zinc-400">({{ $userEmail }})</span>.
        {{ __('All actions taken during impersonation will be performed as this user.') }}
    </p>

    <div class="mt-4">
        <flux:textarea
            wire:model="reason"
            :label="__('Reason for impersonation')"
            :placeholder="__('Describe why you need to impersonate this user...')"
            rows="3"
            required
        />
        @error('reason')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-5 flex justify-end gap-2">
        <flux:button variant="filled" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" wire:click="impersonate" wire:loading.attr="disabled">
            {{ __('Impersonate') }}
        </flux:button>
    </div>
</flux:modal>
