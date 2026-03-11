{{--
    Toast notification stack.
    Listens for Livewire's dispatched 'notify' browser event:
        $this->dispatch('notify', type: 'success|error|warning|info', message: '...')
--}}
<div
    x-data="{
        toasts: [],
        add(type, message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message, visible: false });
            this.$nextTick(() => {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = true;
            });
            setTimeout(() => this.remove(id), 4500);
        },
        remove(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) {
                t.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 350);
            }
        }
    }"
    @notify.window="add($event.detail.type ?? 'info', $event.detail.message ?? '')"
    class="pointer-events-none fixed top-5 right-5 z-[9999] flex flex-col gap-2"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-250 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
            class="pointer-events-auto flex w-80 items-start gap-3 rounded-xl border bg-white px-4 py-3 shadow-lg dark:bg-zinc-800"
            :class="{
                'border-green-200 dark:border-green-700':  toast.type === 'success',
                'border-red-200   dark:border-red-700':    toast.type === 'error',
                'border-yellow-200 dark:border-yellow-700': toast.type === 'warning',
                'border-blue-200  dark:border-blue-700':   toast.type === 'info',
            }"
        >
            {{-- Icon --}}
            <div
                class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-green-100 dark:bg-green-900/40':  toast.type === 'success',
                    'bg-red-100   dark:bg-red-900/40':    toast.type === 'error',
                    'bg-yellow-100 dark:bg-yellow-900/40': toast.type === 'warning',
                    'bg-blue-100  dark:bg-blue-900/40':   toast.type === 'info',
                }"
            >
                {{-- success --}}
                <svg x-show="toast.type === 'success'" class="size-3.5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                {{-- error --}}
                <svg x-show="toast.type === 'error'" class="size-3.5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{-- warning --}}
                <svg x-show="toast.type === 'warning'" class="size-3.5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                {{-- info --}}
                <svg x-show="toast.type === 'info'" class="size-3.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>

            {{-- Message --}}
            <div class="flex-1 min-w-0">
                <p
                    x-text="toast.message"
                    class="text-sm font-medium leading-snug"
                    :class="{
                        'text-green-800  dark:text-green-300':  toast.type === 'success',
                        'text-red-800    dark:text-red-300':    toast.type === 'error',
                        'text-yellow-800 dark:text-yellow-300': toast.type === 'warning',
                        'text-blue-800   dark:text-blue-300':   toast.type === 'info',
                    }"
                ></p>
            </div>

            {{-- Close button --}}
            <button
                @click="remove(toast.id)"
                class="-mr-1 -mt-0.5 shrink-0 rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                aria-label="Dismiss"
            >
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
