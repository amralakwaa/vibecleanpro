{{--
    The single vertical-rhythm authority for the public site: every page
    section uses this instead of ad-hoc py-* values, so section spacing
    never drifts page to page (see the Phase 5 report's Spacing System).
--}}
@props([
    'tone' => 'default', // default | surface | tint | primary | muted
    'width' => 'default',
    'as' => 'section',
    'density' => 'normal', // tight | normal | feature
])

@php
    $tones = [
        'default' => '',
        'surface' => 'bg-white',
        'tint' => 'surface-tint',
        'muted' => 'bg-neutral-100',
        'primary' => 'bg-ink-950 text-white',
    ];

    // `normal` is deliberately the previous site-wide value, so every
    // page that does not opt in keeps its exact current rhythm. `tight`
    // is for a supporting strip that belongs to the block above it;
    // `feature` is for a moment that should be allowed to breathe.
    $densities = [
        'tight' => 'py-8 md:py-12',
        'normal' => 'py-14 md:py-20',
        'feature' => 'py-20 md:py-32',
    ];
@endphp

<{{ $as }} {{ $attributes->class([$densities[$density], $tones[$tone]]) }}>
    <x-public.container :width="$width">
        {{ $slot }}
    </x-public.container>
</{{ $as }}>
