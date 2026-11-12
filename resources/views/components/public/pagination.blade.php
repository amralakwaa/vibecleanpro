{{--
    Minimal server-side pagination matching the design tokens - not
    Laravel's default Tailwind partial, so it never drifts from the rest
    of the system. RTL-safe (logical properties only, no left/right).
--}}
@props(['paginator'])

@php
    // Page 1 is the bare index URL, never ?page=1: that duplicate of /blog or
    // /projects is what Search Console reported Google following.
    $pageUrl = function (int $page) use ($paginator): string {
        $url = $paginator->url($page);
        if ($page !== 1) {
            return $url;
        }
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        unset($query[$paginator->getPageName()]);

        return strtok($url, '?').($query ? '?'.http_build_query($query) : '');
    };
@endphp

@if ($paginator->hasPages())
    <nav aria-label="ترقيم الصفحات" class="mt-10 flex items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 rounded-lg text-sm text-neutral-300 cursor-not-allowed">
                <x-public.icon name="arrow-start" class="w-4 h-4 rotate-180 rtl:rotate-0" />
            </span>
        @else
            <a href="{{ $pageUrl($paginator->currentPage() - 1) }}" rel="prev"
                class="px-3 py-2 rounded-lg text-sm text-neutral-600 hover:bg-neutral-100">
                <x-public.icon name="arrow-start" class="w-4 h-4 rotate-180 rtl:rotate-0" />
            </a>
        @endif

        @foreach (range(1, $paginator->lastPage()) as $page)
            @if ($page === $paginator->currentPage())
                <span aria-current="page" class="w-9 h-9 flex items-center justify-center rounded-lg bg-primary-600 text-white text-sm font-medium">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $pageUrl($page) }}" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm text-neutral-600 hover:bg-neutral-100">
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
