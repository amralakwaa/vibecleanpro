{{--
    Offer Detail - a commercial opportunity, never a shop, in the V2 system.

    The page answers, in order: what is the offer, is it available right
    now, what does it include, where, what are the actual terms, how do I
    take it. Everything shown is a real column or relation: the offer's
    own number (offer_price) and a struck-through "before" only when
    OfferPrice can prove the single covered service is genuinely dearer;
    the editor's discount_label is the one value statement, shown once.
    No countdown, no scarcity, no "today only": validity is a date.

    Status (Offer::availability(), unchanged) drives the whole surface,
    and the visitor should feel it before reading a word:
      Active    - the loud blue gradient hero (the homepage's offer
                  surface), blue conversion CTA + WhatsApp, and the navy
                  decision band at the foot
      Scheduled - a tinted, calmer hero with the real start date; no order
                  CTA, only an inquiry link, because an order button for an
                  offer that has not started would be misleading
      Expired   - a quiet neutral hero that says so plainly and points to
                  the current offers instead of pretending this one is live

    The picture is the offer's own media or, failing that, the covered
    service's photograph - never an invented sale visual. Content blocks
    stay owned by blocks.blade.php via the only/except contract.
--}}
@php
    use App\Enums\OfferAvailability;

    $availability = $offer->availability();
    $isActive = $availability === OfferAvailability::Active;
    $isScheduled = $availability === OfferAvailability::Scheduled;
    $isExpired = $availability === OfferAvailability::Expired;

    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', array_filter(['service' => $offerServices->first()?->id]));
    $offerPrice = \App\Support\Pricing\OfferPrice::forOffer($offer, $offerServices);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن عرض: '.$offer->title);
    $offersIndexUrl = route('public.offers.index');
    $image = $offer->featuredMedia ?? $offerServices->first()?->featuredMedia;

    $hasFaqSection = $faqs->isNotEmpty() && $page->contentBlocks->contains(fn ($block) => $block->type === 'faq' && $block->is_active);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Status hero - three states, three surfaces ===== --}}
    <section @class([
        'relative isolate overflow-hidden',
        'surface-offer text-white' => $isActive,
        'surface-tint' => $isScheduled,
        'bg-neutral-100' => $isExpired,
    ])>
        @if ($isActive)
            <div class="glow-primary absolute -top-24 -end-24 w-[30rem] h-[30rem] -z-10 opacity-80" aria-hidden="true"></div>
        @elseif ($isScheduled)
            <div class="glow-primary absolute -top-24 -end-24 w-[26rem] h-[26rem] -z-10 opacity-50" aria-hidden="true"></div>
        @endif

        <x-public.container width="wide" class="relative pt-8 pb-16 md:pt-12 md:pb-24">
            <div @class(['grid gap-10 lg:gap-16 items-center', 'lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]' => (bool) $image])>
                <div class="max-w-2xl">
                    <x-public.breadcrumb :items="$seo->breadcrumbs" :class="$isActive ? 'mb-6 [&_a]:text-white/80 [&_a:hover]:text-white [&_span]:text-white/90 [&_ol]:text-white/70' : 'mb-6'" />

                    {{-- Status eyebrow: three honest states, three colours. --}}
                    <p @class([
                        'inline-flex items-center gap-2.5 rounded-full px-3.5 py-1.5 text-sm font-medium',
                        'bg-white/15 text-white ring-1 ring-white/20 backdrop-blur-sm' => $isActive,
                        'bg-white/80 text-primary-700 ring-1 ring-primary-200/70' => $isScheduled,
                        'bg-white text-neutral-600 ring-1 ring-neutral-300' => $isExpired,
                    ])>
                        <span @class(['w-1.5 h-1.5 rounded-full', 'bg-success-400' => $isActive, 'bg-primary-600' => $isScheduled, 'bg-neutral-400' => $isExpired]) aria-hidden="true"></span>
                        @if ($isActive)
                            عرض متاح الآن
                        @elseif ($isScheduled)
                            يبدأ في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time>
                        @else
                            انتهى هذا العرض
                        @endif
                    </p>

                    <h1 @class([
                        'mt-5 font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-balance',
                        'text-white' => $isActive,
                        'text-ink-950' => ! $isActive,
                    ])>
                        {{ $page->title }}
                    </h1>

                    {{-- The one value statement, from the editor, shown once. --}}
                    @if ($offer->discount_label)
                        <p @class(['mt-4 inline-flex items-center rounded-full px-3.5 py-1.5 text-sm font-medium', 'bg-white text-primary-700' => $isActive, 'bg-primary-600 text-white' => $isScheduled, 'bg-neutral-200 text-neutral-700' => $isExpired])>{{ $offer->discount_label }}</p>
                    @endif

                    {{-- A number only when the admin entered one; "بدلًا من"
                         only when the single covered service has a real,
                         higher public price (see OfferPrice). --}}
                    @if ($offerPrice)
                        <p class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1" data-offer-price>
                            <span @class(['text-sm', 'text-white/85' => $isActive, 'text-neutral-500' => ! $isActive])>سعر العرض</span>
                            <span @class(['font-display text-3xl md:text-4xl font-medium tabular-nums', 'text-white' => $isActive, 'text-ink-950' => ! $isActive])>{{ $offerPrice->label() }}</span>
                            @if ($offerPrice->beforeLabel())
                                <span @class(['text-sm', 'text-white/85' => $isActive, 'text-neutral-500' => ! $isActive])>بدلًا من <s class="tabular-nums">{{ $offerPrice->beforeLabel() }}</s></span>
                            @endif
                        </p>
                    @endif

                    {{-- Validity, stated as dates - never a countdown. --}}
                    @if ($offer->ends_at)
                        <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-1.5 text-sm">
                            <div class="flex items-baseline gap-2">
                                <dt @class(['text-white/85' => $isActive, 'text-neutral-500' => ! $isActive])>{{ $isExpired ? 'انتهى في' : 'ساري حتى' }}</dt>
                                <dd @class(['font-medium', 'text-white' => $isActive, 'text-ink-950' => ! $isActive])><time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time></dd>
                            </div>
                        </dl>
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-3" data-hero-cta>
                        @if ($isActive)
                            <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="!bg-white !text-primary-700 hover:!bg-primary-50 shadow-lg shadow-ink-950/20">اطلب هذا العرض</x-public.button>
                            @if ($whatsappUrl)
                                <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                            @endif
                        @elseif ($isScheduled)
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 min-h-11 rounded-xl bg-white ring-1 ring-ink-950/10 px-5 font-medium text-ink-950 hover:text-primary-700 transition-colors">
                                    <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> استفسر عن العرض قبل بدايته
                                </a>
                            @endif
                        @else
                            <x-public.button :href="$offersIndexUrl" variant="secondary" icon-trailing="arrow-start">تصفح العروض الحالية</x-public.button>
                        @endif
                    </div>
                </div>

                @if ($image)
                    <div class="relative lg:order-last reveal">
                        <div @class([
                            'group relative overflow-hidden rounded-3xl',
                            'ring-1 ring-white/20 shadow-2xl shadow-ink-950/30' => $isActive,
                            'ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/15 bg-white' => $isScheduled,
                            'ring-1 ring-ink-950/10 bg-white' => $isExpired,
                        ])>
                            <img
                                src="{{ $image->url() }}" srcset="{{ $image->srcset() }}"
                                alt="{{ $image->alt_text ?? $page->title }}"
                                fetchpriority="high"
                                width="{{ $image->width ?: 1200 }}"
                                height="{{ $image->height ?: 900 }}"
                                @class(['tile-media w-full aspect-[3/2] lg:aspect-[4/3] object-cover', 'grayscale-[35%] opacity-90' => $isExpired])
                            >
                        </div>
                    </div>
                @endif
            </div>
        </x-public.container>

        @if ($offerServices->isNotEmpty() || $offerAreas->isNotEmpty())
            <x-public.wave shape="curve" position="bottom" class="text-white" />
        @endif
    </section>

    {{-- ===== 2. Scope - what the offer covers and where. Each a real
             relation, each a crawlable link: "what does it include", not
             "suggested products". ===== --}}
    @if ($offerServices->isNotEmpty() || $offerAreas->isNotEmpty())
        <section class="bg-white">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div @class(['grid gap-6 md:gap-8 reveal', 'md:grid-cols-2' => $offerServices->isNotEmpty() && $offerAreas->isNotEmpty()])>
                    @if ($offerServices->isNotEmpty())
                        <div class="surface-tint relative overflow-hidden rounded-3xl p-6 md:p-8 ring-1 ring-primary-200/60">
                            <div class="glow-primary absolute -top-16 -end-16 w-48 h-48 opacity-60" aria-hidden="true"></div>
                            <h2 class="relative flex items-center gap-2.5 font-display text-lg font-medium text-ink-950">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-600 text-white" aria-hidden="true"><x-public.icon name="check" class="w-4 h-4" /></span>
                                يشمل العرض
                            </h2>
                            <ol class="relative mt-5 space-y-2">
                                @foreach ($offerServices as $service)
                                    <li>
                                        <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                            class="group flex items-center justify-between gap-4 rounded-2xl bg-white ring-1 ring-ink-950/5 px-4 py-3 min-h-12 text-ink-950 transition-[box-shadow,ring-color] hover:ring-primary-300">
                                            <span class="flex items-baseline gap-3 min-w-0">
                                                <span class="font-display text-sm text-primary-600 tabular-nums shrink-0">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                                <span class="font-medium group-hover:text-primary-700 transition-colors">{{ $service->name }}</span>
                                            </span>
                                            <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    @if ($offerAreas->isNotEmpty())
                        <div class="rounded-3xl bg-white p-6 md:p-8 ring-1 ring-ink-950/10">
                            <h2 class="flex items-center gap-2.5 font-display text-lg font-medium text-ink-950">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-50 text-primary-700" aria-hidden="true"><x-public.icon name="map-pin" class="w-4 h-4" /></span>
                                متاح في
                            </h2>
                            <ul class="mt-5 flex flex-wrap gap-2.5">
                                @foreach ($offerAreas as $area)
                                    <li>
                                        <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                            class="inline-flex items-center gap-2 min-h-11 rounded-full bg-neutral-50 ring-1 ring-ink-950/10 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                            {{ $area->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 3. Details and terms - editor content in the reading system ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" :related="$related" related-item-type="service" width="narrow" />

    {{-- ===== 4. FAQ ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

    {{-- ===== 5. Decision - shaped by status, never misleading ===== --}}
    @if ($isActive)
        <section class="surface-atmos relative isolate overflow-hidden text-white">
            @if ($hasFaqSection)
                <x-public.wave shape="soft" position="top" class="text-white" />
            @endif
            <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
            <x-public.container width="wide" @class(['pb-16 md:pb-24', 'pt-28 md:pt-36' => $hasFaqSection, 'pt-16 md:pt-24' => ! $hasFaqSection])>
                <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                    <div>
                        <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">جاهز للاستفادة من العرض؟</h2>
                        @if ($offer->ends_at)
                            <p class="mt-4 text-lg text-white/85 leading-relaxed max-w-xl">ساري حتى <time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time>. أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر. لا يتم الدفع عبر الموقع.</p>
                        @else
                            <p class="mt-4 text-lg text-white/85 leading-relaxed max-w-xl">أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر. لا يتم الدفع عبر الموقع.</p>
                        @endif
                    </div>
                    <div class="flex flex-col items-start gap-3 lg:items-stretch">
                        <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40 lg:justify-center">اطلب هذا العرض</x-public.button>
                        @if ($whatsappUrl)
                            <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp" class="lg:justify-center">تواصل عبر واتساب</x-public.button>
                        @endif
                    </div>
                </div>
            </x-public.container>
        </section>
    @elseif ($isScheduled)
        <section class="surface-tint">
            <x-public.container width="narrow" class="py-14 md:py-20">
                <div class="rounded-3xl bg-white ring-1 ring-primary-200/60 p-7 md:p-10 reveal">
                    <h2 class="font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950 text-balance">يبدأ هذا العرض في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time></h2>
                    <p class="mt-3 text-neutral-600 leading-relaxed">يمكنك الاستفسار عنه الآن، ويصبح متاحًا للطلب مع بدايته.</p>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="mt-6 inline-flex items-center gap-2 min-h-11 font-medium text-neutral-700 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                            <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> استفسر عبر واتساب
                        </a>
                    @endif
                </div>
            </x-public.container>
        </section>
    @else
        <section class="bg-neutral-100">
            <x-public.container width="narrow" class="py-14 md:py-20">
                <div class="rounded-3xl bg-white ring-1 ring-ink-950/10 p-7 md:p-10 reveal">
                    <h2 class="font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950">انتهى هذا العرض</h2>
                    <p class="mt-3 text-neutral-600 leading-relaxed">قد تجد عرضًا مناسبًا بين العروض المتاحة حاليًا.</p>
                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                        <x-public.button :href="$offersIndexUrl" variant="cta" icon-trailing="arrow-start">العروض الحالية</x-public.button>
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                                <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> أو راسلنا على واتساب
                            </a>
                        @endif
                    </div>
                </div>
            </x-public.container>
        </section>
    @endif
</x-layouts.public>
