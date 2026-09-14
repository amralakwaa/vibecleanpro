{{--
    Hero for a typed detail page (currently Service). Deliberately NOT the
    homepage hero: the homepage opens with a full-bleed photograph at
    viewport height because its job is to establish the brand, while a
    detail page's job is to answer "what is this, and can I get it" as
    fast as possible. So this one is compressed, carries a breadcrumb, and
    sets the photograph BESIDE the copy rather than behind it - the text
    never sits on top of an image here, so nothing depends on how light or
    dark that photograph happens to be.

    Two states, chosen by the caller passing $image or not:
      - with a real image: a split, the photo filling its half with a
        fixed editorial ratio, square corners, no card and no shadow.
      - without one: a typographic hero. It never substitutes stock art.

    One solid blue CTA; WhatsApp is the green secondary and is optional.
--}}
@props([
    'breadcrumbs' => null,
    'eyebrow' => null,
    'heading',
    'description' => null,
    'image' => null,
    'ctaUrl',
    'ctaLabel',
    'whatsappUrl' => null,
    'whatsappLabel' => 'واتساب',
])

<section class="bg-white border-b border-neutral-200">
    <x-public.container width="wide" class="py-10 md:py-14">
        <div @class(['grid gap-8 lg:gap-14 items-center', 'lg:grid-cols-[1.05fr_1fr]' => (bool) $image])>
            <div>
                @if ($breadcrumbs)
                    <x-public.breadcrumb :items="$breadcrumbs" class="mb-6" />
                @endif

                @if ($eyebrow)
                    <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-primary-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>
                        {{ $eyebrow }}
                    </p>
                @endif

                <h1 @class([
                    'font-display font-medium tracking-tight text-ink-950 text-balance',
                    'text-[1.75rem] leading-tight md:text-5xl md:leading-[1.1]',
                    'mt-4' => $eyebrow,
                ])>
                    {{ $heading }}
                </h1>

                @if ($description)
                    <p class="mt-4 text-neutral-600 leading-relaxed max-w-xl">{{ $description }}</p>
                @endif

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="check-circle">{{ $ctaLabel }}</x-public.button>

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">
                            {{ $whatsappLabel }}
                        </x-public.button>
                    @endif
                </div>

                {{ $slot }}
            </div>

            @if ($image)
                <div class="lg:order-last">
                    <img
                        src="{{ $image->url() }}"
                        alt="{{ $image->alt_text ?? $heading }}"
                        fetchpriority="high"
                        width="900"
                        height="675"
                        class="w-full aspect-[3/2] lg:aspect-[4/3] object-cover"
                    >
                </div>
            @endif
        </div>
    </x-public.container>
</section>
