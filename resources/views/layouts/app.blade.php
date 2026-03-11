<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex flex-col p-0! overflow-y-auto">

        {{-- Page Header: breadcrumb left, live clock right --}}
        @php
            $segments = array_filter(explode('/', trim(request()->path(), '/')));
            $breadcrumbs = [];
            $url = '';
            foreach ($segments as $segment) {
                $url .= '/' . $segment;
                $breadcrumbs[] = [
                    'label' => ucwords(str_replace(['-', '_'], ' ', $segment)),
                    'url'   => $url,
                ];
            }
        @endphp

        <div class="sticky top-0 z-10 flex flex-col gap-2 border-b border-[#1a7a9e] bg-[#0c4d65] px-4 py-3 dark:border-[#0a3d52] dark:bg-[#072d3d] sm:flex-row sm:items-center sm:justify-between sm:px-6">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-1 text-sm text-[#cde9f4]/70 dark:text-[#cde9f4]/60" aria-label="Breadcrumb">
                <span class="flex items-center gap-1">
                    <flux:icon name="home" class="size-4" />
                </span>
                @foreach ($breadcrumbs as $crumb)
                    <flux:icon name="chevron-right" class="size-3.5 text-[#1a7a9e] dark:text-white/30" />
                    <span class="{{ $loop->last ? 'font-medium text-white' : 'text-[#cde9f4]/70 dark:text-[#cde9f4]/60' }}">{{ $crumb['label'] }}</span>
                @endforeach
            </nav>

            {{-- Live Clock --}}
            <div
                x-data="{
                    now: new Date(),
                    get time() {
                        return this.now.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                    },
                    get date() {
                        return this.now.toLocaleDateString('en-MY', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                    }
                }"
                x-init="setInterval(() => now = new Date(), 1000)"
                class="flex items-center gap-2 self-end text-sm text-[#cde9f4]/70 dark:text-[#cde9f4]/60 sm:self-auto"
            >
                <flux:icon name="clock" class="size-4 text-[#1b96c6]" />
                <span x-text="date" class="hidden sm:inline"></span>
                <span class="hidden sm:inline text-[#1a7a9e] dark:text-white/30">|</span>
                <span x-text="time" class="font-mono font-medium text-white"></span>
            </div>
        </div>

        {{-- Page Content --}}
        <div class="flex-1 p-6 bg-zinc-50 dark:bg-zinc-900">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col items-center justify-between gap-2 text-xs text-zinc-400 dark:text-zinc-500 sm:flex-row">
                <span>&copy; {{ date('Y') }} <span class="font-medium text-zinc-500 dark:text-zinc-400">{{ config('app.name') }}</span> by <a href="https://wevetel.com" target="_blank" class="font-medium text-[#1b96c6] hover:underline">Wevetel Sdn. Bhd.</a> All rights reserved.</span>
                <span>Powered by <span class="font-medium text-zinc-500 dark:text-zinc-400">Laravel</span> &amp; <span class="font-medium text-zinc-500 dark:text-zinc-400">Livewire</span></span>
            </div>
        </footer>

    </flux:main>
</x-layouts::app.sidebar>
