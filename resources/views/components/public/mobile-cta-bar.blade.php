{{--
    Mobile conversion bar: three actions with clear hierarchy.
    1. Call (blue, dominant width) — most customers prefer to ring
    2. Quote (secondary, compact) — the written-estimate path
    3. WhatsApp (green, icon-only) — async channel
    With no phone on file the quote button takes the dominant slot again.

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
        <div class="flex items-center gap-2 p-3">
            @if ($phoneUrl)
                <x-public.button :href="$phoneUrl" variant="cta" size="md" icon="phone" class="grow min-h-12">
                    اتصل الآن
                </x-public.button>
            @endif

            @if ($quoteUrl)
                <x-public.button
                    :href="$quoteUrl"
                    :variant="$phoneUrl ? 'secondary' : 'cta'"
                    size="md"
                    icon="check-circle"
                    :class="$phoneUrl ? 'shrink-0 min-h-12 !px-4' : 'grow min-h-12'"
                >
                    طلب خدمة
                </x-public.button>
            @endif

            @if ($whatsappUrl)
                <x-public.button
                    :href="$whatsappUrl"
                    external
                    variant="whatsapp"
                    size="md"
                    icon="whatsapp"
                    class="shrink-0 !px-4 min-h-12 min-w-12"
                    aria-label="تواصل عبر واتساب"
                />
            @endif
        </div>
    </div>
@endif
