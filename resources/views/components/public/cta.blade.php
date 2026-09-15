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

<section {{ $attributes->class(['bg-ink-950 text-white']) }}>
    <x-public.container width="wide" class="py-16 md:py-24">
        <div class="max-w-2xl">
            <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">{{ $title }}</h2>

            @if ($description)
                <p class="mt-4 text-ink-200 leading-relaxed">{{ $description }}</p>
            @endif

            @if ($quoteUrl || $whatsappUrl || $phoneUrl)
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    @if ($quoteUrl)
                        <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>
                    @endif

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">تواصل عبر واتساب</x-public.button>
                    @endif

                    @if ($phoneUrl)
                        <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-ink-200 hover:text-white transition-colors">
                            <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </x-public.container>
</section>
