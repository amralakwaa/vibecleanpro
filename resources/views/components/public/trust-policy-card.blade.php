{{--
    One policy in the Trust Center family, as a card. Used by the hub grid
    and by the "related policies" strip at the foot of every policy page.
    The title is the real DB page title; the icon, eyebrow and blurb are
    the family's presentation metadata (TrustPolicies) - navigation, not a
    business claim. The accent is one of the two brand chip surfaces so the
    grid reads coordinated, never a rainbow.

    @param string      $url
    @param string      $title
    @param string      $icon
    @param string      $eyebrow
    @param string|null $blurb
    @param string      $accent  primary | ink
--}}
@props([
    'url',
    'title',
    'icon' => 'shield-check',
    'eyebrow' => null,
    'blurb' => null,
    'accent' => 'primary',
])

@php
    $chip = $accent === 'ink'
        ? 'bg-ink-900 text-white'
        : 'bg-primary-600 text-white';
@endphp

<a href="{{ $url }}" class="group relative flex h-full flex-col rounded-3xl bg-white p-6 md:p-7 ring-1 ring-ink-950/8 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-950/5 hover:ring-primary-300">
    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl {{ $chip }} shadow-sm shrink-0" aria-hidden="true">
        <x-public.icon :name="$icon" class="w-6 h-6" />
    </span>

    @if ($eyebrow)
        <p class="mt-5 text-xs font-medium tracking-wide uppercase text-primary-700">{{ $eyebrow }}</p>
    @endif

    <h3 class="mt-1.5 font-display text-lg md:text-xl font-medium tracking-tight text-ink-950 group-hover:text-primary-700 transition-colors text-balance">{{ $title }}</h3>

    @if ($blurb)
        <p class="mt-2 text-sm text-neutral-600 leading-relaxed">{{ $blurb }}</p>
    @endif

    <span class="mt-5 pt-1 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 mt-auto">
        اطّلع على التفاصيل
        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform group-hover:-translate-x-1 rtl:group-hover:translate-x-1" />
    </span>
</a>
