{{--
    One step in a process ("steps" content block). Each step is a card with a
    prominent numbered badge, so a process reads as a designed sequence on
    every viewport (including the phone) rather than a plain numbered list.
    The badge number is shown in Arabic-Indic digits (١، ٢ …) to match the
    locale; the parent "steps" block lays the cards out (stack on mobile,
    row on desktop).

    The caller passes a 1-based integer.
--}}
@props(['number', 'title', 'description' => null])

@php
    $displayNumber = strtr((string) $number, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
@endphp

<li {{ $attributes->class(['relative flex items-start gap-4 md:block bg-white rounded-2xl border border-neutral-200 p-5 transition-shadow duration-150 hover:shadow-md hover:shadow-neutral-900/5']) }}>
    <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-full bg-primary-600 text-white font-display text-lg font-semibold ring-4 ring-primary-50 md:mb-4">{{ $displayNumber }}</span>
    <div>
        <p class="font-medium text-ink-950">{{ $title }}</p>
        @if ($description)
            <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
        @endif
    </div>
</li>
