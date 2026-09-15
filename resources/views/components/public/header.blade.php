{{--
    Header. One conversion action only: "اطلب عرض سعر". Phone and WhatsApp
    used to sit here alongside it, which put three competing calls in the
    top-right corner of every page - they now live in the mobile menu's
    footer and in the site footer, where they support the decision instead
    of competing with it.

    $overlay is opt-in per page: the homepage's Evidence Hero is a
    full-bleed photograph, so the header rides transparently over it and
    only becomes an opaque surface once scrolled. Every other page keeps
    the opaque surface from the start, because their heroes are light and
    white-on-light would be unreadable.

    $primaryNav is the short desktop bar; $navItems is the full list, used
    by the roomier mobile menu (and the footer).
--}}
@props([
    'businessProfile',
    'navItems' => [],
    'primaryNav' => null,
    'quoteUrl' => null,
    'whatsappUrl' => null,
    'phoneUrl' => null,
    'overlay' => false,
])

@php
    $primaryNav ??= $navItems;
    $brandName = $businessProfile->name ?? config('app.name');
@endphp

<header
    x-data="{ scrolled: false, mobileOpen: false }"
    x-init="scrolled = window.scrollY > 24"
    @scroll.window="scrolled = window.scrollY > 24"
    @keydown.escape.window="mobileOpen = false"
    x-effect="document.body.style.overflow = mobileOpen ? 'hidden' : ''"
    {{-- The header is a stacking context (sticky + z-index), so the
         full-screen menu's own z-index is scoped INSIDE it - without
         lifting the header itself while the menu is open, the mobile CTA
         bar (also fixed, later in the DOM) paints over the menu and
         covers its contact row. Both dynamic concerns share one :class
         binding, because a second one would silently be dropped. --}}
    :class="[
        mobileOpen ? '!z-[60]' : '',
        @js($overlay) ? (scrolled ? 'bg-white/95 backdrop-blur border-b border-neutral-200' : 'bg-transparent border-b border-transparent') : ''
    ]"
    @class([
        'sticky top-0 z-40 transition-colors duration-200',
        'border-b' => ! $overlay,
        'bg-white/95 backdrop-blur border-neutral-200' => ! $overlay,
        // Overlay mode only works if the hero actually sits *behind* the
        // bar: pull the following section up by the header's own height,
        // otherwise white nav text lands on the page background.
        '-mb-16 md:-mb-20' => $overlay,
    ])
>
    <x-public.container width="wide">
        <div class="flex items-center justify-between h-16 md:h-20 gap-6">
            <a
                href="{{ url('/') }}"
                class="flex items-center gap-2.5 font-display font-medium text-lg shrink-0 transition-colors {{ $overlay ? '' : 'text-ink-950' }}"
                @if ($overlay) :class="scrolled ? 'text-ink-950' : 'text-white'" @endif
            >
                @if ($businessProfile?->logo)
                    <img src="{{ $businessProfile->logo->url() }}" alt="{{ $brandName }}" class="h-9 w-auto">
                @endif
                <span>{{ $brandName }}</span>
            </a>

            <nav class="hidden lg:flex items-center gap-8" aria-label="التنقل الرئيسي">
                @foreach ($primaryNav as $label => $url)
                    <a
                        href="{{ $url }}"
                        class="text-sm font-medium transition-colors {{ $overlay ? '' : 'text-neutral-700 hover:text-primary-700' }}"
                        @if ($overlay)
                            :class="scrolled ? 'text-neutral-700 hover:text-primary-700' : 'text-white/90 hover:text-white'"
                        @endif
                    >{{ $label }}</a>
                @endforeach
            </nav>

            <div class="hidden lg:block shrink-0">
                @if ($quoteUrl)
                    <x-public.button :href="$quoteUrl" variant="cta" size="sm" icon="check-circle">اطلب عرض سعر</x-public.button>
                @endif
            </div>

            <button
                type="button"
                @click="mobileOpen = true"
                class="lg:hidden -me-2 p-3 rounded-md transition-colors {{ $overlay ? '' : 'text-ink-950' }}"
                @if ($overlay) :class="scrolled ? 'text-ink-950' : 'text-white'" @endif
                aria-controls="mobile-nav"
                :aria-expanded="mobileOpen"
                aria-label="فتح القائمة"
            >
                <x-public.icon name="menu" class="w-6 h-6" />
            </button>
        </div>
    </x-public.container>

    {{--
        Full-screen mobile menu. No x-transition on purpose: Alpine's
        transitions clear their enter state via requestAnimationFrame,
        which browsers throttle in a backgrounded tab - a user who taps
        the menu then switches tabs could return to a menu stuck
        open-but-invisible. Reliability beats the animation here.
    --}}
    <div
        id="mobile-nav"
        x-show="mobileOpen"
        x-cloak
        class="lg:hidden fixed inset-0 z-50 bg-white flex flex-col overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-label="القائمة"
    >
        <div class="flex items-center justify-between h-16 px-4 border-b border-neutral-200 shrink-0">
            <span class="font-display font-medium text-lg text-ink-950">{{ $brandName }}</span>
            <button type="button" @click="mobileOpen = false" class="-me-2 p-3 text-ink-950 rounded-md" aria-label="إغلاق القائمة">
                <x-public.icon name="close" class="w-6 h-6" />
            </button>
        </div>

        <nav class="grow px-6 py-8" aria-label="التنقل - جوال">
            {{-- B2C block --}}
            <p class="text-xs font-medium tracking-wide text-neutral-500">للأفراد والمنازل</p>
            <ul class="mt-4 space-y-1">
                @foreach ($navItems as $label => $url)
                    @continue($label === 'للشركات')
                    <li>
                        <a href="{{ $url }}" class="block py-3 font-display text-2xl font-medium text-ink-950">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>

            {{-- B2B block, visually separated so the two audiences read as
                 two different doors rather than one long link list. --}}
            @if (isset($navItems['للشركات']))
                <div class="mt-8 pt-8 border-t border-neutral-200">
                    <p class="text-xs font-medium tracking-wide text-neutral-500">للشركات والمنشآت</p>
                    <a href="{{ $navItems['للشركات'] }}" class="mt-4 block py-3 font-display text-2xl font-medium text-primary-700">
                        حلول الشركات وعقود التشغيل
                    </a>
                </div>
            @endif
        </nav>

        <div class="px-6 pb-8 pt-6 border-t border-neutral-200 shrink-0 space-y-3">
            @if ($quoteUrl)
                <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="w-full">اطلب عرض سعر</x-public.button>
            @endif

            <div class="flex items-center gap-6 pt-1">
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 py-2 text-sm font-medium text-neutral-700">
                        <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> واتساب
                    </a>
                @endif
                @if ($phoneUrl)
                    <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 py-2 text-sm font-medium text-neutral-700">
                        <x-public.icon name="phone" class="w-4 h-4" /> اتصل بنا
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
