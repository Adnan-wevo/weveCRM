@props([
    'sidebar' => false,
])

@if($sidebar)
    <span class="flex w-full items-center justify-center">
        <img src="{{ asset('branding/wevetel.png') }}" alt="Wevetel" class="h-9 w-auto max-w-full" />
    </span>
@else
    <flux:brand {{ $attributes->except(['href', 'wire:navigate']) }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <x-app-logo-icon class="h-8 w-auto" />
        </x-slot>
    </flux:brand>
@endif

