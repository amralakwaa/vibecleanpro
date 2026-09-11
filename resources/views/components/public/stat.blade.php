{{-- A single real number/fact, e.g. "12 سنة خبرة". Never invent the value
     the caller passes in - see the Trust System note in the Phase 5
     report: only real, known figures belong here. --}}
@props(['value', 'label'])

<div {{ $attributes->class(['text-center']) }}>
    <p class="text-3xl md:text-4xl font-bold text-primary-700">{{ $value }}</p>
    <p class="mt-1 text-sm text-neutral-600">{{ $label }}</p>
</div>
