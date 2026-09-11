{{--
    A priced package/variant of a service (e.g. "شقة - غرفتين", "فيلا أقل
    من 300م²") - presentational only. A package is a pricing unit *within*
    a Service page, never a routable/indexable entity of its own: it has
    no slug, no SEO metadata, no canonical - see the Phase 5 report's
    competitor-research note on this. `previousPrice` must only be passed
    when a real, current discount applies - never invent a "before" price
    to manufacture a percentage-off badge.
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
    $discountPercent = $previousPrice && $previousPrice > $price
        ? (int) round((($previousPrice - $price) / $previousPrice) * 100)
        : null;
@endphp

<x-public.card class="relative flex flex-col h-full">
    @if ($discountPercent)
        <x-public.badge tone="accent" class="absolute top-4 start-4">خصم {{ $discountPercent }}%</x-public.badge>
    @endif

    <h3 class="font-semibold text-neutral-900 text-lg">{{ $name }}</h3>
    @if ($variant)
        <p class="mt-0.5 text-sm text-neutral-500">{{ $variant }}</p>
    @endif

    <div class="mt-4 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-primary-700">{{ $price }}</span>
        <span class="text-sm text-neutral-500">ريال</span>
        @if ($previousPrice)
            <span class="text-sm text-neutral-400 line-through">{{ $previousPrice }} ريال</span>
        @endif
    </div>

    @if (count($includedItems))
        <ul class="mt-4 space-y-1.5 text-sm text-neutral-600">
            @foreach ($includedItems as $item)
                <li class="flex items-start gap-2">
                    <x-public.icon name="check" class="w-4 h-4 mt-0.5 text-primary-600 shrink-0" />
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($note)
        <p class="mt-3 text-xs text-neutral-400">{{ $note }}</p>
    @endif

    @if ($ctaUrl)
        <x-public.button :href="$ctaUrl" variant="primary" class="mt-5 w-full">{{ $ctaLabel }}</x-public.button>
    @endif
</x-public.card>
