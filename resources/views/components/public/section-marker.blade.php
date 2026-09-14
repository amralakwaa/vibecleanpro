{{--
    Editorial moment marker: ٠٢ — label ─────────────

    Replaces the centered eyebrow+title stack as the page's rhythm device,
    so sections are separated by structure rather than by swapping the
    background color every time.

    The number is passed in by the caller and is never auto-incremented:
    only moments that genuinely form a sequence get one, otherwise the
    numbering becomes the same decoration it was meant to replace. Pass
    no :number at all for an unnumbered marker.

    The rule is aria-hidden. By default the label is a plain <p>, so this
    never competes with the real heading that follows it.

    Set :heading="true" when this marker is the section's ONLY label - the
    label then renders as a real <h2> so the section is reachable by
    heading navigation, instead of being a section a screen-reader user
    cannot find. Leave it false wherever a separate <h2> follows, so the
    same text is never announced twice.
--}}
@props([
    'number' => null,
    'label',
    'heading' => false,
])

<div {{ $attributes->class(['flex items-center gap-4']) }}>
    @if ($number)
        <span class="font-display text-sm font-medium text-primary-600 tabular-nums">{{ $number }}</span>
    @endif

    <{{ $heading ? 'h2' : 'p' }} class="text-sm font-medium tracking-wide text-neutral-500 shrink-0">{{ $label }}</{{ $heading ? 'h2' : 'p' }}>

    <span class="h-px grow bg-neutral-200" aria-hidden="true"></span>
</div>
