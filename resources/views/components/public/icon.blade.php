@props(['name'])

{{--
    Single icon strategy for the whole public site: one Blade component,
    hand-authored inline SVGs, no icon package. The set below is
    deliberately small - only what the design system actually uses (see
    the Phase 5 report, item 13). Add a new `@case` here before reaching
    for a library.
--}}
@php
    $base = 'w-5 h-5 shrink-0';
@endphp
<svg {{ $attributes->class([$base])->merge(['fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke' => 'currentColor', 'stroke-width' => 1.75, 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('whatsapp')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 13.5c0 3.038 2.462 5.5 5.5 5.5.98 0 1.9-.256 2.696-.705L18.5 19l-.72-2.79A5.478 5.478 0 0 0 18.5 13.5c0-3.038-2.462-5.5-5.5-5.5S7 10.462 7 13.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 12c0 1.657 1.343 3 3 3M10.5 12c0-.5.2-.9.5-1.2M10.5 12h0" />
            @break

        @case('phone')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h2.086c.414 0 .78.27.902.665l.857 2.78a.94.94 0 0 1-.272.98l-1.36 1.224a12.06 12.06 0 0 0 5.388 5.388l1.224-1.36a.94.94 0 0 1 .98-.272l2.78.857c.396.122.665.488.665.902V17.75a1.5 1.5 0 0 1-1.5 1.5h-.75C10.16 19.25 4.75 13.84 4.75 7.5v-1.5a1.5 1.5 0 0 1 1.5-1.5Z" />
            @break

        @case('map-pin')
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 5.25-7.5 10.5-7.5 10.5S4.5 15.75 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            @break

        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            @break

        @case('check-circle')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            @break

        @case('shield-check')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5c2.5 1.4 4.4 1.8 6.5 1.8v6.2c0 5-3 7.6-6.5 9-3.5-1.4-6.5-4-6.5-9V5.3c2.1 0 4-.4 6.5-1.8Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12 1.75 1.75L14.75 10" />
            @break

        @case('sparkles')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 3.5 10.8 7l3.7 1.5-3.7 1.5-1.3 3.5-1.3-3.5L4 8.5l3.7-1.5 1.8-3.5ZM18 13l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2Z" />
            @break

        @case('clock')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.75M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />
            @break

        @case('mail')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75V6.75Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.25 7.5 6 7.5-6" />
            @break

        @case('menu')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            @break

        @case('close')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
            @break

        @case('chevron-down')
            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 8.25 6.75 7.5 6.75-7.5" />
            @break

        @case('arrow-start')
            {{-- Points toward reading-start: left in LTR, but we mirror it
                 with rtl:rotate-180 wherever it is used, so it always
                 points toward "more" in the reader's own direction. --}}
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6 19.5 12 13.5 18M19.5 12H4.5" />
            @break

        @case('star')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" fill="currentColor" stroke="none" d="m12 3.5 2.6 5.3 5.9.85-4.25 4.15 1 5.85L12 16.9l-5.25 2.75 1-5.85L3.5 9.65l5.9-.85L12 3.5Z" />
            @break

        @case('star-outline')
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.5 2.6 5.3 5.9.85-4.25 4.15 1 5.85L12 16.9l-5.25 2.75 1-5.85L3.5 9.65l5.9-.85L12 3.5Z" />
            @break

        @case('quote')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h3v3.75c0 2.07-1.68 3.75-3.75 3.75v-1.5A2.25 2.25 0 0 0 9 12H7.5V8.25ZM14.25 8.25h3v3.75c0 2.07-1.68 3.75-3.75 3.75v-1.5A2.25 2.25 0 0 0 15.75 12h-1.5V8.25Z" />
            @break

        @case('briefcase')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V5.25A1.5 1.5 0 0 1 10.5 3.75h3A1.5 1.5 0 0 1 15 5.25v1.5M4.5 9.75h15a.75.75 0 0 1 .75.75v7.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-7.5a.75.75 0 0 1 .75-.75Z" />
            @break

        @case('alert-triangle')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008M10.7 4.6 2.9 18a1.5 1.5 0 0 0 1.3 2.25h15.6a1.5 1.5 0 0 0 1.3-2.25L13.3 4.6a1.5 1.5 0 0 0-2.6 0Z" />
            @break

        @case('info')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m0-9h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            @break

        @case('inbox')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h4.13a2 2 0 0 1 1.79 1.11l.35.69a2 2 0 0 0 1.79 1.1h.38a2 2 0 0 0 1.79-1.1l.35-.69A2 2 0 0 1 16.12 12h4.13M3.75 12l1.32-5.52A1.5 1.5 0 0 1 6.53 5.25h10.94a1.5 1.5 0 0 1 1.46 1.23L20.25 12M3.75 12v5.25a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V12" />
            @break

        @case('home')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 4l7.5 6.5M6 9.25V19a.75.75 0 0 0 .75.75H10v-4.5a2 2 0 0 1 4 0v4.5h3.25a.75.75 0 0 0 .75-.75V9.25" />
            @break

        @case('building')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 20.25V4.5a.75.75 0 0 1 .75-.75h8a.75.75 0 0 1 .75.75v15.75M5.25 20.25h13.5M14.75 20.25V13a.75.75 0 0 1 .75-.75h2.75a.75.75 0 0 1 .75.75v7.25M8.25 7.5h1.5m-1.5 3.5h1.5m-1.5 3.5h1.5" />
            @break

        @case('users')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 11.25a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.5 19.25a5.5 5.5 0 0 1 11 0M15.5 6.6a3 3 0 0 1 0 5.66M18 13.4a5.48 5.48 0 0 1 3 4.85" />
            @break
    @endswitch
</svg>
