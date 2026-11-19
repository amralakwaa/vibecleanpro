{{--
    The closing conversion band an editor can place with a `cta` block
    (and any template may reuse). Presentation only - the caller passes
    real quote / WhatsApp / phone links. Blue fill = the one conversion
    action, green = WhatsApp, phone stays a quiet text link; the same
    band the redesigned pages end with, so an editor-placed CTA never
    looks like a different site.
--}}
@props([
    'title',
    'description' => null,
    'quoteUrl' => null,
    'whatsappUrl' => null,
    'phoneUrl' => null,
])

<section {{ $attributes->class(['surface-atmos relative isolate overflow-hidden text-white']) }}>
    <div class="glow-primary absolute -top-24 -end-24 w-[24rem] h-[24rem] -z-10 opacity-60" aria-hidden="true"></div>
    <x-public.container width="wide" class="py-16 md:py-24">
        <div class="max-w-2xl reveal">
            <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">{{ $title }}</h2>

            @if ($description)
                <p class="mt-4 text-lg text-white/85 leading-relaxed">{{ $description }}</p>
            @endif

            @if ($quoteUrl || $whatsappUrl || $phoneUrl)
                {{-- WhatsApp and call lead (owner decision 2026-11-19); the
                     quote form is the quieter path, and leads only alone. --}}
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp" class="shadow-lg shadow-primary-900/40">تواصل عبر واتساب</x-public.button>
                    @endif

                    @if ($phoneUrl)
                        <x-public.button :href="$phoneUrl" variant="cta" size="lg" icon="phone">اتصل الآن</x-public.button>
                    @endif

                    @if ($quoteUrl && ($whatsappUrl || $phoneUrl))
                        <a href="{{ $quoteUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-white/80 underline underline-offset-4 hover:text-white transition-colors">أو اطلب عرض سعر مكتوب</a>
                    @elseif ($quoteUrl)
                        <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40">اطلب عرض سعر</x-public.button>
                    @endif
                </div>
            @endif
        </div>
    </x-public.container>
</section>
