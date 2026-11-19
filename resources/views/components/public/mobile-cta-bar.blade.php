{{--
    Mobile conversion bar: WhatsApp and Call side by side at equal width,
    the two channels Riyadh customers actually use. The written quote stays
    in the header and on each page; it takes the bar only when the business
    has neither channel on file.

    Fixed height, safe-area aware, lg:hidden; the layout gives <main>
    matching bottom padding so this never covers page content.
--}}
@props(['quoteUrl' => null, 'whatsappUrl' => null, 'phoneUrl' => null])

@if ($quoteUrl || $whatsappUrl || $phoneUrl)
    {{-- While a page's own primary action ([data-hero-cta]) is on screen
         the bar slides away, so a visitor never sees two "request" buttons
         at once. One IntersectionObserver, no scroll listeners; without
         JS the bar simply stays. --}}
    <div
        x-data="{ hidden: false }"
        x-init="const target = document.querySelector('[data-hero-cta]'); if (target && 'IntersectionObserver' in window) { new IntersectionObserver((entries) => hidden = entries[entries.length - 1].isIntersecting, { threshold: 0.4 }).observe(target); }"
        :class="hidden ? 'translate-y-full pointer-events-none' : 'translate-y-0'"
        :aria-hidden="hidden"
        class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-neutral-200 pb-[env(safe-area-inset-bottom)] transition-transform duration-300"
    >
        {{-- WhatsApp and call share the bar as equal primaries (owner
             decision 2026-11-19); the quote form only fills the bar when
             neither channel exists. --}}
        <div class="flex items-center gap-2 p-3">
            @if ($whatsappUrl)
                <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="md" icon="whatsapp" class="grow basis-0 min-h-12">
                    واتساب
                </x-public.button>
            @endif

            @if ($phoneUrl)
                <x-public.button :href="$phoneUrl" variant="cta" size="md" icon="phone" class="grow basis-0 min-h-12">
                    اتصل الآن
                </x-public.button>
            @endif

            @if ($quoteUrl && ! $whatsappUrl && ! $phoneUrl)
                <x-public.button :href="$quoteUrl" variant="cta" size="md" icon="check-circle" class="grow min-h-12">
                    طلب خدمة
                </x-public.button>
            @endif
        </div>
    </div>
@endif
