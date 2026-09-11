@props([
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

<div class="relative bg-primary-900 text-white overflow-hidden">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="absolute inset-0 w-full h-full object-cover opacity-25" loading="eager" width="1600" height="700">
        <div class="absolute inset-0 bg-gradient-to-t from-primary-950/90 via-primary-900/70 to-primary-900/40"></div>
    @endif

    <x-public.container width="wide" class="relative py-14 md:py-20">
        @if ($breadcrumbs)
            <x-public.breadcrumb :items="$breadcrumbs" class="[&_a]:text-primary-200 [&_a:hover]:text-white [&_span]:text-white mb-5" />
        @endif

        <h1 class="text-3xl md:text-5xl font-bold tracking-tight max-w-2xl">{{ $heading }}</h1>

        @if ($subheading)
            <p class="mt-4 text-primary-100 max-w-xl leading-relaxed">{{ $subheading }}</p>
        @endif

        @if ($ctaLabel && $ctaUrl)
            <div class="mt-7">
                <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="whatsapp">{{ $ctaLabel }}</x-public.button>
            </div>
        @endif

        {{ $slot ?? '' }}
    </x-public.container>
</div>
