{{--
    Minimal server-side pagination matching the design tokens - not
    Laravel's default Tailwind partial, so it never drifts from the rest
    of the system. RTL-safe (logical properties only, no left/right).
--}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav aria-label="ترقيم الصفحات" class="mt-10 flex items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 rounded-lg text-sm text-neutral-300 cursor-not-allowed">
                <x-public.icon name="arrow-start" class="w-4 h-4 rotate-180 rtl:rotate-0" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                class="px-3 py-2 rounded-lg text-sm text-neutral-600 hover:bg-neutral-100">
                <x-public.icon name="arrow-start" class="w-4 h-4 rotate-180 rtl:rotate-0" />
            </a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page === $paginator->currentPage())
                <span aria-current="page" class="w-9 h-9 flex items-center justify-center rounded-lg bg-primary-600 text-white text-sm font-medium">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $url }}" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm text-neutral-600 hover:bg-neutral-100">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                class="px-3 py-2 rounded-lg text-sm text-neutral-600 hover:bg-neutral-100">
                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
            </a>
        @else
            <span class="px-3 py-2 rounded-lg text-sm text-neutral-300 cursor-not-allowed">
                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
            </span>
        @endif
    </nav>
@endif
