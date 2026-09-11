{{--
    Light Premium Hero (Direction 1: Deep Petrol Ink). Deliberately NOT a
    dark full-bleed band - the page's own warm-ivory background shows
    through, a real photo (when given) sits beside the text as its own
    bounded element, and Deep Petrol/Terracotta are used only for small,
    specific accents (eyebrow, heading color, CTA button) - never as a
    screen-filling color. See the Phase 2 report for the full rationale.
--}}
@props([
    'eyebrow' => null,
    'heading',
    'subheading' => null,
    'image' => null, // Media|null - absolute URL string also accepted
    'ctaLabel' => null,
    'ctaUrl' => null,
    'breadcrumbs' => null,
])

@php
    $imageUrl = is_string($image) ? $image : $image?->url();
    $imageAlt = is_string($image) ? $heading : ($image?->alt_text ?? $heading);
@endphp

<div class="relative overflow-hidden">
    <x-public.container width="wide" class="py-14 md:py-20">
        <div @class(['grid gap-10 items-center', 'lg:grid-cols-2' => $imageUrl])>
            <div @class(['max-w-2xl' => ! $imageUrl])>
                @if ($breadcrumbs)
                    <x-public.breadcrumb :items="$breadcrumbs" class="mb-5" />
                @endif

                @if ($eyebrow)
                    <p class="flex items-center gap-2 text-sm font-medium tracking-wide text-primary-700 mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-500"></span>
                        {{ $eyebrow }}
                    </p>
                @endif

                <h1 class="text-3xl md:text-5xl font-semibold tracking-tight text-primary-900 text-balance">
                    {{ $heading }}
                </h1>

                @if ($subheading)
                    <p class="mt-4 text-neutral-600 leading-relaxed max-w-xl">{{ $subheading }}</p>
                @endif

                @if ($ctaLabel && $ctaUrl)
                    <div class="mt-7">
                        <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="whatsapp">{{ $ctaLabel }}</x-public.button>
                    </div>
                @endif

                {{ $slot ?? '' }}
            </div>

            @if ($imageUrl)
                <div class="relative">
                    <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}"
                        class="w-full aspect-[4/3] object-cover rounded-3xl shadow-sm shadow-primary-900/10"
                        loading="eager" width="900" height="675">
                </div>
            @endif
        </div>
    </x-public.container>
</div>
