{{--
    The single vertical-rhythm authority for the public site: every page
    section uses this instead of ad-hoc py-* values, so section spacing
    never drifts page to page (see the Phase 5 report's Spacing System).
--}}
@props([
    'tone' => 'default', // default | surface | primary | muted
    'width' => 'default',
    'as' => 'section',
])

@php
    $tones = [
        'default' => '',
        'surface' => 'bg-white',
        'muted' => 'bg-neutral-100',
        'primary' => 'bg-primary-900 text-white',
    ];
@endphp

<{{ $as }} {{ $attributes->class(['py-14 md:py-20', $tones[$tone]]) }}>
    <x-public.container :width="$width">
        {{ $slot }}
    </x-public.container>
</{{ $as }}>
