@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'start', // start | center
    'level' => 2, // heading level: 1 for page H1, 2/3 for section headings
])

@php
    $alignClass = $align === 'center' ? 'text-center mx-auto' : 'text-start';
    $tag = 'h'.$level;
@endphp

<div {{ $attributes->class(['max-w-2xl', $alignClass]) }}>
    @if ($eyebrow)
        <p class="text-sm font-semibold text-primary-600 mb-2">{{ $eyebrow }}</p>
    @endif

    <{{ $tag }} class="text-2xl md:text-3xl font-semibold tracking-tight text-ink-950">
        {{ $title }}
    </{{ $tag }}>

    @if ($description)
        <p class="mt-3 text-neutral-600 leading-relaxed">{{ $description }}</p>
    @endif
</div>
