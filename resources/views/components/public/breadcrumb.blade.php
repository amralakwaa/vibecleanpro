{{--
    Renders the *visible* trail only. The BreadcrumbList JSON-LD is already
    produced by App\Seo\StructuredDataGenerator and emitted in <head> by
    the public layout - this component must never re-derive or duplicate
    that logic (see Phase 5 report, item 18).

    @param array<int, \App\Seo\ValueObjects\BreadcrumbItem> $items
--}}
@props(['items'])

@if (count($items) > 1)
    <nav aria-label="breadcrumb" {{ $attributes->class(['text-sm']) }}>
        <ol class="flex flex-wrap items-center gap-2 text-neutral-500">
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    @if (! $loop->first)
                        <span aria-hidden="true" class="text-neutral-300">/</span>
                    @endif

                    @if ($item->url && ! $loop->last)
                        <a href="{{ $item->url }}" class="hover:text-primary-600 transition-colors">{{ $item->label }}</a>
                    @else
                        <span class="text-neutral-700 font-medium" @if ($loop->last) aria-current="page" @endif>{{ $item->label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
