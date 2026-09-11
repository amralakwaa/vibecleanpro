{{--
    The single content-width authority for the public site: every section
    should wrap its content in this instead of hand-rolling max-w-* /
    px-* per component, so the whole site reads as one consistent grid.
--}}
@props(['width' => 'default']) {{-- default | narrow | wide --}}

@php
    $widths = [
        'narrow' => 'max-w-3xl',
        'default' => 'max-w-6xl',
        'wide' => 'max-w-7xl',
    ];
@endphp

<div {{ $attributes->class(['mx-auto px-4 sm:px-6 lg:px-8', $widths[$width]]) }}>
    {{ $slot }}
</div>
