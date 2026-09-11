{{--
    The recurring bottom-of-template conversion band. Presentation only -
    the caller passes real WhatsApp/phone links (built from BusinessProfile
    by the page template), this component never fetches data itself.
--}}
@props([
    'title',
    'description' => null,
    'whatsappUrl' => null,
    'phoneUrl' => null,
])

<div {{ $attributes->class(['rounded-3xl bg-primary-900 text-white px-6 py-10 md:px-12 md:py-14 text-center']) }}>
    <h2 class="text-2xl md:text-3xl font-bold">{{ $title }}</h2>

    @if ($description)
        <p class="mt-3 text-primary-100 max-w-xl mx-auto">{{ $description }}</p>
    @endif

    @if ($whatsappUrl || $phoneUrl)
        <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
            @if ($whatsappUrl)
                <x-public.button href="{{ $whatsappUrl }}" external variant="cta" size="lg" icon="whatsapp">
                    تواصل عبر واتساب
                </x-public.button>
            @endif

            @if ($phoneUrl)
                <x-public.button href="{{ $phoneUrl }}" variant="secondary" size="lg" icon="phone"
                    class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">
                    اتصل بنا الآن
                </x-public.button>
            @endif
        </div>
    @endif
</div>
