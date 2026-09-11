@props(['tone' => 'primary']) {{-- primary | neutral | accent | success --}}

@php
    $tones = [
        'primary' => 'bg-primary-50 text-primary-700',
        'neutral' => 'bg-neutral-100 text-neutral-700',
        'accent' => 'bg-accent-50 text-accent-700',
        'success' => 'bg-success-50 text-success-600',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold', $tones[$tone]]) }}>
    {{ $slot }}
</span>
