{{--
    Header architecture (see Phase 5 report, item 8). $navItems is a plain
    [label => url] array so this component never assumes which routes
    exist yet - today it only points at real destinations (see the layout
    that renders this for what is wired in now vs. reserved for later).
--}}
@props(['businessProfile', 'navItems' => [], 'whatsappUrl' => null, 'phoneUrl' => null])

<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-neutral-200">
    <x-public.container width="wide">
        <div class="flex items-center justify-between h-16 md:h-18">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-bold text-primary-800">
                @if ($businessProfile?->logo)
                    <img src="{{ $businessProfile->logo->url() }}" alt="{{ $businessProfile->name }}" class="h-9 w-auto">
                @else
                    <span class="w-9 h-9 rounded-lg bg-primary-600 text-white flex items-center justify-center">
                        <x-public.icon name="sparkles" class="w-5 h-5" />
                    </span>
                @endif
                <span class="text-lg">{{ $businessProfile->name ?? config('app.name') }}</span>
            </a>

            <nav class="hidden lg:flex items-center gap-7" aria-label="التنقل الرئيسي">
                @foreach ($navItems as $label => $url)
                    <a href="{{ $url }}" class="text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden lg:flex items-center gap-3">
                @if ($phoneUrl)
                    <x-public.button href="{{ $phoneUrl }}" variant="ghost" size="sm" icon="phone">اتصل بنا</x-public.button>
                @endif
                @if ($whatsappUrl)
                    <x-public.button href="{{ $whatsappUrl }}" external variant="cta" size="sm" icon="whatsapp">واتساب</x-public.button>
                @endif
            </div>

            <button
                type="button"
                @click="mobileOpen = ! mobileOpen"
                class="lg:hidden -me-2 p-2.5 rounded-lg text-neutral-700 hover:bg-neutral-100"
                :aria-expanded="mobileOpen"
                aria-controls="mobile-nav"
                aria-label="فتح القائمة"
            >
                <x-public.icon name="menu" x-show="! mobileOpen" class="w-6 h-6" />
                <x-public.icon name="close" x-show="mobileOpen" x-cloak class="w-6 h-6" />
            </button>
        </div>
    </x-public.container>

    {{--
        Plain x-show, deliberately no x-transition: Alpine's transition
        relies on requestAnimationFrame to clear the enter state, which
        real browsers throttle for a backgrounded/inactive tab - a user
        who taps the menu then switches tabs mid-animation could get it
        stuck open-but-invisible. Not worth that risk for a menu open/
        close (see the Phase 5 report's Motion guidance: minimal, never
        at the cost of reliability).
    --}}
    <div
        id="mobile-nav"
        x-show="mobileOpen"
        x-cloak
        class="lg:hidden border-t border-neutral-200 bg-white"
    >
        <x-public.container class="py-4">
            <nav class="flex flex-col gap-1" aria-label="التنقل - جوال">
                @foreach ($navItems as $label => $url)
                    <a href="{{ $url }}" class="px-2 py-2.5 rounded-lg text-neutral-700 font-medium hover:bg-neutral-50">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-3 pt-3 border-t border-neutral-100 flex flex-col gap-2">
                @if ($whatsappUrl)
                    <x-public.button href="{{ $whatsappUrl }}" external variant="cta" icon="whatsapp" class="w-full">
                        تواصل عبر واتساب
                    </x-public.button>
                @endif
                @if ($phoneUrl)
                    <x-public.button href="{{ $phoneUrl }}" variant="secondary" icon="phone" class="w-full">
                        اتصل بنا
                    </x-public.button>
                @endif
            </div>
        </x-public.container>
    </div>
</header>
