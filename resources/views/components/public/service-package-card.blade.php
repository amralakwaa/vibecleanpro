{{--
    A priced package/variant of a service (e.g. "شقة - غرفتين", "فيلا أقل
    من 300م²") - presentational only. A package is a pricing unit *within*
    a Service page, never a routable/indexable entity of its own. The
    previous price is shown only when it is a real number above the
    current one; no percentage badge is computed from it.
--}}
@props([
    'name',
    'variant' => null,
    'price',
    'previousPrice' => null,
    'includedItems' => [],
    'ctaLabel' => 'اطلب هذه الباقة',
    'ctaUrl' => null,
    'note' => null,
])

@php
    $format = fn ($amount) => \App\Support\Pricing\PublicPrice::format((float) $amount);
    $showPrevious = is_numeric($previousPrice) && is_numeric($price) && (float) $previousPrice > (float) $price;
@endphp

<article {{ $attributes->class(['flex flex-col border-t-2 border-ink-950 pt-5']) }}>
    <h3 class="font-display text-xl font-medium tracking-tight text-ink-950">{{ $name }}</h3>
    @if ($variant)
        <p class="mt-0.5 text-sm text-neutral-500">{{ $variant }}</p>
    @endif

    <p class="mt-4 flex flex-wrap items-baseline gap-x-3">
        <span class="font-display text-2xl font-medium text-ink-950 tabular-nums">{{ is_numeric($price) ? $format($price) : $price }}</span>
        @if ($showPrevious)
            <s class="text-sm text-neutral-500 tabular-nums">{{ $format($previousPrice) }}</s>
        @endif
    </p>

    @if (count($includedItems))
        <ul class="mt-4">
            @foreach ($includedItems as $item)
                <li class="flex items-start gap-2.5 py-2 border-b border-neutral-200 text-sm text-neutral-700">
                    <x-public.icon name="check" class="w-4 h-4 mt-0.5 text-primary-600 shrink-0" />
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($note)
        <p class="mt-3 text-xs text-neutral-500">{{ $note }}</p>
    @endif

    @if ($ctaUrl)
        <a href="{{ $ctaUrl }}" class="mt-5 inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
            {{ $ctaLabel }}
            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
        </a>
    @endif
</article>
