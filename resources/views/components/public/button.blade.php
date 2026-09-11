@props([
    'variant' => 'primary', // cta | primary | secondary | ghost | text
    'size' => 'md', // sm | md | lg
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconTrailing' => null,
    'external' => false,
    'loading' => false,
])

@php
    $tag = $href ? 'a' : 'button';

    $sizes = [
        'sm' => 'text-sm px-3.5 py-2 gap-1.5',
        'md' => 'text-[0.95rem] px-5 py-3 gap-2',
        'lg' => 'text-base px-7 py-3.5 gap-2',
    ];

    $variants = [
        // Reserved for the site's three conversion actions: WhatsApp,
        // Call, Request Quote - keep it exclusive so it stays meaningful.
        'cta' => 'bg-accent-500 text-white hover:bg-accent-600 active:bg-accent-700 shadow-sm shadow-accent-900/10',
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 shadow-sm shadow-primary-900/10',
        'secondary' => 'bg-white text-primary-700 border border-neutral-300 hover:border-primary-400 hover:bg-primary-50 active:bg-primary-100',
        'ghost' => 'bg-transparent text-primary-700 hover:bg-primary-50 active:bg-primary-100',
        'text' => 'bg-transparent text-primary-700 hover:text-primary-800 px-0 py-0 underline-offset-4 hover:underline',
    ];

    $isText = $variant === 'text';
@endphp
<{{ $tag }}
    {{ $attributes->class([
        'inline-flex items-center justify-center rounded-xl font-medium transition-colors duration-150',
        'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
        $isText ? 'gap-1.5' : $sizes[$size],
        $variants[$variant],
    ]) }}
    @if ($href)
        href="{{ $href }}"
        @if ($external) target="_blank" rel="noopener noreferrer" @endif
    @else
        type="{{ $type }}"
        @if ($loading) disabled @endif
    @endif
    @if ($loading) aria-busy="true" @endif
>
    @if ($loading)
        <svg class="w-[1.1em] h-[1.1em] animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
            <path class="opacity-75" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z" />
        </svg>
    @elseif ($icon)
        <x-public.icon :name="$icon" class="w-[1.1em] h-[1.1em]" />
    @endif

    {{ $slot }}

    @if ($iconTrailing)
        <x-public.icon :name="$iconTrailing" class="w-[1.1em] h-[1.1em] rtl:rotate-180" />
    @endif
</{{ $tag }}>
