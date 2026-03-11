@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}
$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
$pageName = $paginator->getPageName();
$isEmpty   = $paginator->count() === 0;
@endphp

<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center gap-1">

    {{-- Previous --}}
    @if ($paginator->onFirstPage() || $isEmpty)
        <span class="inline-flex size-7 cursor-not-allowed items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-600" aria-disabled="true">
            <svg class="size-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
        </span>
    @else
        <button type="button"
            wire:click="previousPage('{{ $pageName }}')"
            x-on:click="{{ $scrollIntoViewJsSnippet }}"
            class="inline-flex size-7 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800"
            aria-label="{{ __('pagination.previous') }}"
        >
            <svg class="size-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
        </button>
    @endif

    {{-- Page Numbers --}}
    @if ($isEmpty)
        <span class="inline-flex size-7 items-center justify-center rounded-md border border-zinc-900 bg-zinc-900 text-xs font-semibold text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">
            1
        </span>
    @else
        @php
            $current  = $paginator->currentPage();
            $last     = $paginator->lastPage();
            // Build the visible page set: first, window of 3 around current, last
            $show = collect([1]);
            $rangeStart = max(2, $current - 1);
            $rangeEnd   = min($last - 1, $current + 1);
            if ($rangeStart <= $rangeEnd) {
                foreach (range($rangeStart, $rangeEnd) as $p) {
                    $show->push($p);
                }
            }
            $show->push($last);
            $show = $show->unique()->sort()->values();
        @endphp

        @foreach ($show as $i => $page)
            {{-- Ellipsis before this page? --}}
            @if ($i > 0 && $page > $show[$i - 1] + 1)
                <span class="inline-flex size-7 items-center justify-center text-xs text-zinc-400">…</span>
            @endif

            @if ($page == $current)
                <span aria-current="page" class="inline-flex size-7 items-center justify-center rounded-md border border-zinc-900 bg-zinc-900 text-xs font-semibold text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">
                    {{ $page }}
                </span>
            @else
                <button type="button"
                    wire:key="paginator-page{{ $page }}-{{ $pageName }}"
                    wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    class="inline-flex size-7 items-center justify-center rounded-md border border-zinc-200 bg-white text-xs text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800"
                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                >
                    {{ $page }}
                </button>
            @endif
        @endforeach
    @endif

    {{-- Next --}}
    @if ($isEmpty || ! $paginator->hasMorePages())
        <span class="inline-flex size-7 cursor-not-allowed items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-600" aria-disabled="true">
            <svg class="size-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
        </span>
    @else
        <button type="button"
            wire:click="nextPage('{{ $pageName }}')"
            x-on:click="{{ $scrollIntoViewJsSnippet }}"
            class="inline-flex size-7 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800"
            aria-label="{{ __('pagination.next') }}"
        >
            <svg class="size-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
        </button>
    @endif

</nav>
