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
        @case('play')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 5.5c0-.78.84-1.27 1.52-.89l9.2 5.1c.7.39.7 1.4 0 1.79l-9.2 5.1c-.68.38-1.52-.11-1.52-.89V5.5Z" />
            @break

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

        @case('minus')
            <path stroke-linecap="round" d="M6 12h12" />
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

        @case('lock')
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-6a1.5 1.5 0 0 1 1.5-1.5Z" />
            @break

        @case('scale')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v16.5m0 0c-1.35 0-2.64.24-3.83.69M12 20.25c1.35 0 2.64.24 3.83.69M5.25 5.25c2.2-.31 4.46-.47 6.75-.47s4.55.16 6.75.47" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 5 4.25 13c.6.5 1.4.8 2.5.8s1.9-.3 2.5-.8L6.75 5ZM17.25 5l-2.5 8c.6.5 1.4.8 2.5.8s1.9-.3 2.5-.8L17.25 5Z" />
            @break

        @case('calendar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 5.25h15a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75V6a.75.75 0 0 1 .75-.75Z" />
            @break

        @case('clipboard')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25H7.5A1.5 1.5 0 0 0 6 6.75v12a1.5 1.5 0 0 0 1.5 1.5h9a1.5 1.5 0 0 0 1.5-1.5v-12a1.5 1.5 0 0 0-1.5-1.5H15" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25A1.5 1.5 0 0 1 10.5 3.75h3A1.5 1.5 0 0 1 15 5.25V6H9v-.75Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 13 1.75 1.75L14.75 11" />
            @break

        @case('badge-check')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-6.286A11.96 11.96 0 0 1 3.6 6 12 12 0 0 0 3 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15c-3.2 0-6.1-1.25-8.25-3.286Z" />
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

        @case('tiktok')
            <path fill="currentColor" stroke="none" d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5 2.59 2.59 0 0 1-2.59-2.59 2.59 2.59 0 0 1 2.59-2.59c.27 0 .53.04.78.11V9.7a5.72 5.72 0 0 0-.78-.05 5.73 5.73 0 0 0-5.73 5.73A5.73 5.73 0 0 0 9.86 21a5.73 5.73 0 0 0 5.73-5.73V9.04a7.35 7.35 0 0 0 4.28 1.37V7.32a4.28 4.28 0 0 1-3.27-1.5Z" />
            @break

        @case('facebook')
            <path fill="currentColor" stroke="none" d="M13.4 21v-8.15h2.73l.41-3.18H13.4V7.7c0-.92.25-1.55 1.57-1.55h1.68V3.18A22.5 22.5 0 0 0 14.2 3c-2.3 0-3.87 1.4-3.87 3.98v2.34H7.6v3.18h2.73V21h3.07Z" />
            @break

        @case('instagram')
            <path fill="currentColor" stroke="none" d="M12 7.38a4.62 4.62 0 1 0 0 9.24 4.62 4.62 0 0 0 0-9.24Zm0 7.62a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm5.88-7.81a1.08 1.08 0 1 1-2.16 0 1.08 1.08 0 0 1 2.16 0ZM20.94 8.2a5.35 5.35 0 0 0-1.36-3.78A5.38 5.38 0 0 0 15.8 3.06c-1.49-.08-5.96-.08-7.45 0a5.37 5.37 0 0 0-3.78 1.35A5.38 5.38 0 0 0 3.21 8.2c-.09 1.49-.09 5.96 0 7.45a5.35 5.35 0 0 0 1.36 3.78 5.4 5.4 0 0 0 3.78 1.36c1.49.09 5.96.09 7.45 0a5.35 5.35 0 0 0 3.78-1.36 5.38 5.38 0 0 0 1.36-3.78c.09-1.49.09-5.95 0-7.44Zm-1.93 9.04a3.05 3.05 0 0 1-1.72 1.72c-1.19.47-4.01.36-5.33.36s-4.14.1-5.33-.36a3.05 3.05 0 0 1-1.72-1.72C4.44 16.05 4.55 13.23 4.55 11.91s-.1-4.14.36-5.33A3.05 3.05 0 0 1 6.63 4.86C7.82 4.39 10.64 4.5 11.96 4.5s4.14-.1 5.33.36a3.05 3.05 0 0 1 1.72 1.72c.47 1.19.36 4.01.36 5.33s.11 4.14-.36 5.33Z" />
            @break

        @case('snapchat')
            <path fill="currentColor" stroke="none" d="M12.16 3c1.76 0 3.47.95 4.2 2.93.3.8.36 2.52.33 3.39a.43.43 0 0 0 .28.42c.34.13.66.2.97.22.48.03.8.27.8.63 0 .35-.3.65-.89.72-.94.11-1.66.39-2.14.83-.75.68-1.53 1.87-2.05 2.35a2.7 2.7 0 0 1-.56.36c-.38.19-.38.46-.27.69.24.47.68.71 1.23.95.79.35 1.43.54 1.67.97.16.29.1.66-.18.93-.43.41-1.21.56-1.76.62-.32.03-.6.09-.68.23-.15.27-.21.65-.73.77a4.5 4.5 0 0 1-1.16.17c-.73 0-1.31-.25-2.09-.56-.51-.2-1.1-.44-1.82-.56a4.52 4.52 0 0 0-.78-.07c-.4 0-.72.07-.97.14-.39.12-.6.04-.72-.18-.08-.14-.36-.2-.68-.23-.55-.06-1.33-.21-1.76-.62-.28-.27-.34-.64-.18-.93.24-.43.88-.62 1.67-.97.55-.24.99-.48 1.23-.95.11-.23.11-.5-.27-.69a2.7 2.7 0 0 1-.56-.36c-.52-.48-1.3-1.67-2.05-2.35-.48-.44-1.2-.72-2.14-.83-.59-.07-.89-.37-.89-.72 0-.36.32-.6.8-.63.31-.02.63-.09.97-.22a.43.43 0 0 0 .28-.42c-.03-.87-.03-2.59.33-3.39C8.53 3.95 10.24 3 12 3h.16Z" />
            @break
    @endswitch
</svg>
