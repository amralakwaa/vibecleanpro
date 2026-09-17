{{--
    Section-transition wave. Two shapes only, so every transition on the
    site is recognisably the same brand gesture:

      soft  - a gentle double crest, used where a light section meets a
              deep one (hero -> content, content -> offer moment)
      curve - a single shallow arc, used where two light surfaces meet

    The wave takes the colour of the section it *introduces* (pass the
    Tailwind text colour of that section's background) and is placed at
    the top or bottom edge of the section it sits in. Purely decorative:
    aria-hidden, no text, no interaction. SVG so it scales crisp at any
    width; preserveAspectRatio="none" keeps the height stable on phones.
--}}
@props([
    'shape' => 'soft', // soft | curve
    'position' => 'bottom', // top | bottom
    'flip' => false,
])

@php
    $paths = [
        'soft' => 'M0,64 C240,96 360,0 600,32 C840,64 960,112 1200,72 C1320,52 1380,40 1440,44 L1440,120 L0,120 Z',
        'curve' => 'M0,96 C360,20 1080,20 1440,96 L1440,120 L0,120 Z',
    ];
@endphp

<div
    aria-hidden="true"
    {{ $attributes->class([
        'pointer-events-none absolute inset-x-0 leading-[0]',
        'bottom-0' => $position === 'bottom',
        'top-0' => $position === 'top',
        'rotate-180' => ($position === 'top') !== $flip,
    ]) }}
>
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="block w-full h-10 sm:h-14 md:h-20" fill="currentColor">
        <path d="{{ $paths[$shape] }}" />
    </svg>
</div>
