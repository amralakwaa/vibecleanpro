{{--
    Same card markup as pages/offers-index.blade.php's inline offer card,
    extracted so the homepage can reuse it too (see the Phase 3 report -
    offers-index.blade.php itself is untouched this phase, page templates
    are out of scope).
--}}
@props(['offer', 'url'])

<x-public.card :padded="false" class="relative overflow-hidden flex flex-col h-full">
    <div class="aspect-[16/9] bg-neutral-100 overflow-hidden">
        @if ($offer->featuredMedia)
            <img src="{{ $offer->featuredMedia->url() }}" alt="{{ $offer->featuredMedia->alt_text ?? '' }}"
                loading="lazy" class="w-full h-full object-cover" width="480" height="270">
        @else
            <div class="w-full h-full flex items-center justify-center text-primary-300">
                <x-public.icon name="sparkles" class="w-9 h-9" />
            </div>
        @endif
    </div>
    <div class="p-5">
        @if ($offer->availability() === \App\Enums\OfferAvailability::Scheduled)
            <x-public.badge tone="neutral" class="mb-2">قريبًا</x-public.badge>
        @endif
        @if ($offer->discount_label)
            <x-public.badge tone="accent" class="mb-2">{{ $offer->discount_label }}</x-public.badge>
        @endif
        <h3 class="font-semibold text-ink-950">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $offer->title }}
            </a>
        </h3>
    </div>
</x-public.card>
