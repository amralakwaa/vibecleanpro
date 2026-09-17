{{--
    Hero for a typed detail page (Service) - V2.

    Deliberately NOT the homepage hero. The homepage opens on a deep navy
    field with the photograph behind the copy because its job is to
    establish the brand; a service page's job is to close one decision,
    so this hero is light, compressed and factual: a tinted field, the
    service's own photograph set BESIDE the copy as a framed panel with a
    navy plate behind it (depth without putting text on a picture), a
    breadcrumb, the price stated once in its own panel, one blue action
    and WhatsApp beside it. The wave at the foot hands over to the white
    content that follows, so the field reads as a moment, not a header.

    Two states, chosen by the caller passing $image or not:
      - with a real image: the split composition above.
      - without one: the same field, typographic only. Never stock art
        invented here - the picture is whatever the editor attached.

    Everything printed is real: the price comes from PublicPrice (null
    for quote-only or hidden, so nothing renders and no placeholder is
    shown), the trust cues are passed in only when the relations exist,
    and the offer cue is the editor's active offer on this service.
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
    'price' => null, // \App\Support\Pricing\PublicPrice|null - rendered only when real
    'cues' => [],    // [['icon' => 'map-pin', 'text' => '...'], ...] - real facts only
    'offer' => null, // ['url' => ..., 'title' => ..., 'label' => ...] - an active offer on this service
])

<section class="surface-tint relative isolate overflow-hidden">
    <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>

    <x-public.container width="wide" class="relative pt-8 pb-20 md:pt-12 md:pb-28 lg:pb-32">
        <div @class(['grid gap-10 lg:gap-16 items-center', 'lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]' => (bool) $image])>
            <div class="max-w-2xl">
                @if ($breadcrumbs)
                    <x-public.breadcrumb :items="$breadcrumbs" class="mb-6" />
                @endif

                @if ($eyebrow)
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>
                        {{ $eyebrow }}
                    </p>
                @endif

                <h1 @class([
                    'font-display font-medium tracking-tight text-ink-950 text-balance',
                    'text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08]',
                    'mt-5' => $eyebrow,
                ])>
                    {{ $heading }}
                </h1>

                @if ($description)
                    <p class="mt-4 text-lg text-neutral-600 leading-relaxed max-w-xl">{{ $description }}</p>
                @endif

                {{-- The admin-entered price, stated once, in its own panel
                     with the editor's caveat beneath it. A quote-only or
                     hidden price renders nothing at all - the blue action
                     below is the honest answer to "how much". --}}
                @if ($price)
                    <dl class="mt-6 inline-flex flex-col gap-1 rounded-2xl bg-white ring-1 ring-ink-950/5 shadow-sm px-5 py-4 max-w-xl" data-price>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <dt class="text-sm text-neutral-500">السعر</dt>
                            <dd class="font-display text-2xl md:text-3xl font-medium text-ink-950 tabular-nums">{{ $price->label() }}</dd>
                        </div>
                        @if ($price->note)
                            <dd class="text-sm text-neutral-500 leading-relaxed">{{ $price->note }}</dd>
                        @endif
                    </dl>
                @endif

                <div class="mt-7 flex flex-wrap items-center gap-3" data-hero-cta>
                    <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-600/25">{{ $ctaLabel }}</x-public.button>

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">
                            {{ $whatsappLabel }}
                        </x-public.button>
                    @endif
                </div>

                {{-- An active offer on this service is a real commercial
                     cue: the editor's title and value statement, linked -
                     never a price here, the offer moment lower down owns
                     the numbers. --}}
                @if ($offer)
                    <a href="{{ $offer['url'] }}" class="group mt-6 inline-flex items-center gap-3 min-h-11 rounded-full bg-ink-950 text-white ps-1.5 pe-4 py-1.5 text-sm transition-colors hover:bg-ink-900">
                        <span class="inline-flex items-center rounded-full bg-primary-500 px-2.5 py-1 text-xs font-medium text-white">عرض متاح</span>
                        <span class="font-medium">{{ $offer['title'] }}</span>
                        @if (! empty($offer['label']))
                            <span class="hidden sm:inline text-primary-200">{{ $offer['label'] }}</span>
                        @endif
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                    </a>
                @endif

                @if ($cues !== [])
                    <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-neutral-600">
                        @foreach ($cues as $cue)
                            <li class="flex items-center gap-2">
                                <x-public.icon :name="$cue['icon']" class="w-4 h-4 text-primary-600" />
                                {{ $cue['text'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{ $slot }}
            </div>

            @if ($image)
                {{-- The photograph as a framed panel: a navy plate offset
                     behind it gives the picture weight on the tinted field
                     without a card border, and the tile hover scale keeps
                     the same touch as the homepage tiles. --}}
                <div class="relative lg:order-last reveal">
                    <div class="surface-atmos absolute inset-0 -translate-x-3 translate-y-3 md:-translate-x-5 md:translate-y-5 rounded-3xl -z-10" aria-hidden="true"></div>
                    <div class="group relative overflow-hidden rounded-3xl ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/15 bg-white">
                        <img
                            src="{{ $image->url() }}"
                            alt="{{ $image->alt_text ?? $heading }}"
                            fetchpriority="high"
                            width="{{ $image->width ?: 1200 }}"
                            height="{{ $image->height ?: 900 }}"
                            class="tile-media w-full aspect-[3/2] lg:aspect-[4/3] object-cover"
                        >
                    </div>
                </div>
            @endif
        </div>
    </x-public.container>

    {{-- The page background, not white: the editor's first block renders on the page field. --}}
    <x-public.wave shape="curve" position="bottom" class="text-background" />
</section>
