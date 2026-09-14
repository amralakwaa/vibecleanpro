{{--
    The recurring bottom-of-template conversion band. Presentation only -
    the caller passes real WhatsApp/phone links (built from BusinessProfile
    by the page template), this component never fetches data itself.
--}}
@props([
    'title',
    'description' => null,
    'quoteUrl' => null,
    'whatsappUrl' => null,
    'phoneUrl' => null,
])

@php
    // Three distinct roles on this dark navy band, so nothing competes:
    // the blue "cta" fill is the conversion action (request a quote),
    // WhatsApp is always its own green channel, and phone stays a muted
    // ghost. $secondaryClass is the ghost treatment and is only ever
    // correct ON a dark surface - never reuse it over a light one.
    $secondaryClass = '!bg-white/10 !text-white !border-white/20 hover:!bg-white/20';
@endphp

<div {{ $attributes->class(['rounded-3xl bg-ink-950 text-white px-6 py-10 md:px-12 md:py-14 text-center']) }}>
    <h2 class="text-2xl md:text-3xl font-bold">{{ $title }}</h2>

    @if ($description)
        <p class="mt-3 text-ink-200 max-w-xl mx-auto">{{ $description }}</p>
    @endif

    @if ($quoteUrl || $whatsappUrl || $phoneUrl)
        <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
            @if ($quoteUrl)
                <x-public.button href="{{ $quoteUrl }}" variant="cta" size="lg" icon="check-circle">
                    طلب خدمة
                </x-public.button>
            @endif

            @if ($whatsappUrl)
                <x-public.button href="{{ $whatsappUrl }}" external variant="whatsapp" size="lg" icon="whatsapp">
                    تواصل عبر واتساب
                </x-public.button>
            @endif

            @if ($phoneUrl)
                <x-public.button href="{{ $phoneUrl }}" variant="secondary" size="lg" icon="phone" :class="$secondaryClass">
                    اتصل بنا الآن
                </x-public.button>
            @endif
        </div>
    @endif
</div>
