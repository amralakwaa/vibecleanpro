{{--
    One step in a process ("steps" content block). Editorial treatment:
    the numeral itself is the graphic - no coloured circle, no card
    surface, no connecting line, no SaaS stepper. A hairline above each
    step carries the rhythm instead, matching the homepage's process
    composition so a "steps" block reads the same wherever it is placed.

    The caller passes a 1-based integer; it is displayed zero-padded in
    Arabic-Indic digits (٠١، ٠٢ …) to match the section markers.
--}}
@props(['number', 'title', 'description' => null])

@php
    $displayNumber = strtr(
        str_pad((string) $number, 2, '0', STR_PAD_LEFT),
        ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'],
    );
@endphp

<li {{ $attributes->class(['border-t border-ink-950/15 pt-5']) }}>
    <span class="font-display text-4xl md:text-5xl font-light text-primary-600 tabular-nums">{{ $displayNumber }}</span>
    <p class="mt-4 font-medium text-ink-950">{{ $title }}</p>
    @if ($description)
        <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
    @endif
</li>
