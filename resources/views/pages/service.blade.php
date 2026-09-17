{{--
    Service Detail - Persuasion, in the Homepage V2 visual system.

    This is a LANDING PAGE, not a smaller homepage. The homepage exists to
    establish the brand and route the visitor; this page exists to close
    one decision, so it is sequenced as the buying questions get asked:

        what is it -> what does it include -> what drives the price ->
        how is it done -> what is the real result -> is there an offer ->
        where can I get it -> who says so -> what am I still unsure about
        -> how do I order

    V2 carries the homepage's surfaces, radius, image and motion language
    here without copying its composition: a light tinted hero with the
    service photograph framed beside the copy (never behind it), editor
    blocks on a deliberate rhythm (page field / tinted / white), the
    evidence moment on white, the offer moment on the loud blue surface,
    coverage as a compact tinted strip, and a deep navy decision with a
    wave - the same closing gesture as the homepage.

    Every rule is preserved: offers are Active-only (filtered in SQL by
    the controller), before+after projects render as proof and after-only
    projects as photo tiles, testimonials and areas come from real
    relations, the related-services fallback is suppressed when the
    editor placed a manual related_content block, the price is stated
    once (hero) via PublicPrice only, and no section ever shows a
    customer-facing empty state - it simply disappears.
--}}
@php
    // Phone is deliberately absent from this page's CTAs - it stays
    // reachable from the header menu, the footer and the mobile bar, so
    // no section here ever stacks quote + WhatsApp + phone together.
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن خدمة '.$service->name);
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', ['service' => $service->id]);

    $projectShowcases = $projects->filter(
        fn ($project) => $project->media->firstWhere('pivot.stage', 'before') && $project->media->firstWhere('pivot.stage', 'after')
    );
    $projectCards = $projects->reject(fn ($project) => $projectShowcases->contains($project));
    // With no photograph on any project the "evidence" heading would
    // promise pictures it cannot show, so the projects then read as a
    // compact reference list instead of a visual moment.
    $hasProjectPhotos = $projectShowcases->isNotEmpty() || $projectCards->contains(fn ($project) => $project->media->isNotEmpty());
    $leadTestimonial = $testimonials->first();
    $otherTestimonials = $testimonials->skip(1)->take(2);

    $leadOffer = $offers->first();
    $moreOffers = $offers->skip(1);

    // Trust cues in the hero: each appears only when the relation has rows.
    $cues = array_values(array_filter([
        $areas->isNotEmpty() ? ['icon' => 'map-pin', 'text' => 'متاحة ضمن مناطق تغطيتنا في الرياض'] : null,
        $projects->isNotEmpty() ? ['icon' => 'briefcase', 'text' => 'مدعومة بمشاريع منفذة موثقة'] : null,
    ]));

    // Waves take the colour of the section they hand over to, and every
    // section here is optional - so the colours are resolved from what
    // actually renders, never assumed.
    $hasFaqSection = $faqs->isNotEmpty() && $page->contentBlocks->contains(fn ($block) => $block->type === 'faq' && $block->is_active);
    $hasRelatedSection = ! $hasManualRelatedBlock && $related->isNotEmpty();
    $surfaceBeforeDecision = match (true) {
        $hasFaqSection => 'text-white',
        $hasRelatedSection => 'text-background',
        (bool) $leadTestimonial => 'text-white',
        $areas->isNotEmpty() => 'text-primary-50',
        default => null, // offers (loud) or editor content: straight edge
    };
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Service hero (light, framed photograph, price once) ===== --}}
    <x-public.detail-hero
        :breadcrumbs="$seo->breadcrumbs"
        :eyebrow="$service->category?->name"
        :heading="$page->title"
        :description="$service->short_description"
        :image="$service->featuredMedia"
        :cta-url="$quoteUrl"
        cta-label="اطلب عرض سعر"
        :whatsapp-url="$whatsappUrl"
        :price="$service->publicPrice()"
        :cues="$cues"
        :offer="$leadOffer ? ['url' => $urlResolver->urlForPage($leadOffer->page), 'title' => $leadOffer->title, 'label' => $leadOffer->discount_label] : null"
    />

    {{-- ===== 2-4. Editor content: scope, inclusions, price factors,
         process, packages - rendered by the shared block renderer so the
         CMS stays the single source of truth for this page's substance.
         The FAQ is rendered by a second pass further down, because on a
         landing page the objection-handling belongs immediately before
         the decision - see the two-pass contract in blocks.blade.php. --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" :related="$related" related-item-type="service" />

    {{-- ===== 5. Real evidence for THIS service ===== --}}
    @if ($projects->isNotEmpty() && ! $hasProjectPhotos)
        <section class="bg-white" aria-labelledby="service-projects">
            <x-public.container width="wide" class="py-12 md:py-16">
                <h2 id="service-projects" class="text-sm font-medium tracking-wide text-neutral-500">مشاريع منفذة لهذه الخدمة</h2>
                <ul class="mt-4 flex flex-wrap gap-2.5 reveal">
                    @foreach ($projects as $project)
                        <li>
                            <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                class="group inline-flex items-center gap-2 min-h-11 rounded-full bg-neutral-50 ring-1 ring-ink-950/10 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                <x-public.icon name="briefcase" class="w-4 h-4 text-primary-600" />
                                {{ $project->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-public.container>
        </section>
    @elseif ($projects->isNotEmpty())
        <section class="bg-white" aria-labelledby="service-evidence">
            <x-public.container width="wide" class="py-16 md:py-24">
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div class="max-w-2xl">
                        <p class="text-sm font-medium tracking-wide text-primary-700">نتائج هذه الخدمة</p>
                        <h2 id="service-evidence" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                            أعمال منفذة فعليًا، لا صور توضيحية
                        </h2>
                    </div>
                    <a href="{{ route('public.projects.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                        جميع الأعمال
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                @if ($projectShowcases->isNotEmpty())
                    <div @class([
                        'mt-10 grid gap-10 md:gap-12 reveal',
                        'md:grid-cols-2' => $projectShowcases->count() > 1,
                    ])>
                        @foreach ($projectShowcases as $project)
                            <x-public.evidence-band
                                :project="$project"
                                :url="$urlResolver->urlForPage($project->page)"
                                :before="$project->media->firstWhere('pivot.stage', 'before')"
                                :after="$project->media->firstWhere('pivot.stage', 'after')"
                                :area-name="$project->area?->name"
                            />
                        @endforeach
                    </div>
                @endif

                {{-- After-only projects: photo tiles in the homepage's tile
                     language (scrim, title on the picture), or a quiet
                     ruled link when the project has no photograph at all. --}}
                @if ($projectCards->isNotEmpty())
                    <ul @class(['grid sm:grid-cols-2 lg:grid-cols-3 gap-5 reveal', 'mt-12' => $projectShowcases->isNotEmpty(), 'mt-10' => $projectShowcases->isEmpty()])>
                        @foreach ($projectCards as $project)
                            @php($cover = $project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first())
                            <li>
                                @if ($cover)
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group relative isolate flex flex-col justify-end overflow-hidden rounded-2xl text-white aspect-[4/3] shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300">
                                        <img src="{{ $cover->url() }}" alt="{{ $cover->alt_text ?? $project->title }}" loading="lazy"
                                            width="{{ $cover->width ?: 800 }}" height="{{ $cover->height ?: 600 }}"
                                            class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                                        <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                                        <div class="p-5">
                                            <p class="font-display text-lg font-medium tracking-tight text-white">{{ $project->title }}</p>
                                            @if ($project->area?->name)
                                                <p class="mt-1 text-sm text-white/80">{{ $project->area->name }}</p>
                                            @endif
                                        </div>
                                    </a>
                                @else
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group flex items-center justify-between gap-4 rounded-2xl bg-neutral-50 ring-1 ring-ink-950/5 px-5 py-4 min-h-14 text-ink-950 hover:ring-primary-200 transition-[box-shadow,ring-color]">
                                        <span>
                                            <span class="block font-medium group-hover:text-primary-700 transition-colors">{{ $project->title }}</span>
                                            @if ($project->area?->name)
                                                <span class="block mt-0.5 text-sm text-neutral-500">{{ $project->area->name }}</span>
                                            @endif
                                        </span>
                                        <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 6. Active offers on this service - the loud moment ===== --}}
    @if ($leadOffer)
        <section class="surface-offer relative isolate overflow-hidden text-white" aria-labelledby="service-offers">
            <div class="glow-primary absolute top-1/3 -end-32 w-[28rem] h-[28rem] -z-10 opacity-80" aria-hidden="true"></div>

            <x-public.container width="wide" @class(['pt-16 md:pt-24', 'pb-24 md:pb-32' => $areas->isNotEmpty(), 'pb-16 md:pb-24' => $areas->isEmpty()])>
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-100">عروض على هذه الخدمة</p>
                        <h2 id="service-offers" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">
                            عرض متاح الآن
                        </h2>
                    </div>
                    <a href="{{ route('public.offers.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white underline-offset-4 hover:underline">
                        كل العروض
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                {{-- The offer's own number only (never a derived "before"
                     here - the service price is already stated once above). --}}
                <article class="mt-8 md:mt-10 rounded-3xl bg-white/[0.08] border border-white/15 backdrop-blur-sm overflow-hidden reveal">
                  <div @class(['grid items-stretch', 'lg:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)]' => (bool) $leadOffer->featuredMedia])>
                  <div class="p-7 md:p-10 flex flex-col justify-center">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($leadOffer->discount_label)
                            <span class="inline-flex items-center rounded-full bg-white text-primary-700 px-3.5 py-1.5 text-sm font-medium">{{ $leadOffer->discount_label }}</span>
                        @endif
                        @if ($leadOffer->ends_at)
                            <span class="inline-flex items-center rounded-full border border-white/25 px-3.5 py-1.5 text-sm text-white/90">ساري حتى <time datetime="{{ $leadOffer->ends_at->toDateString() }}" class="ms-1">{{ $leadOffer->ends_at->translatedFormat('j F Y') }}</time></span>
                        @endif
                    </div>
                    <h3 class="mt-5 font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-white text-balance">
                        {{ $leadOffer->title }}
                    </h3>
                    @if ($leadOffer->offer_price !== null)
                        <p class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="text-sm text-white/85">سعر العرض</span>
                            <span class="font-display text-3xl md:text-4xl font-medium text-white tabular-nums">{{ \App\Support\Pricing\PublicPrice::format((float) $leadOffer->offer_price) }}</span>
                        </p>
                    @endif
                    <div class="mt-7 flex flex-wrap items-center gap-4">
                        <x-public.button :href="$urlResolver->urlForPage($leadOffer->page)" variant="cta" size="lg" icon-trailing="arrow-start" class="!bg-white !text-primary-700 hover:!bg-primary-50">تفاصيل العرض</x-public.button>
                    </div>
                  </div>
                  @if ($leadOffer->featuredMedia)
                    <div class="relative min-h-[14rem] lg:min-h-0 [mask-image:linear-gradient(to_bottom,transparent_0%,black_18%)] lg:[mask-image:linear-gradient(to_left,transparent_0%,black_24%)]">
                        <img src="{{ $leadOffer->featuredMedia->url() }}" alt="{{ $leadOffer->featuredMedia->alt_text ?? $leadOffer->title }}" loading="lazy"
                            width="{{ $leadOffer->featuredMedia->width ?: 1200 }}" height="{{ $leadOffer->featuredMedia->height ?: 800 }}"
                            class="absolute inset-0 w-full h-full object-cover">
                    </div>
                  @endif
                  </div>
                </article>

                @if ($moreOffers->isNotEmpty())
                    <ul class="mt-6 grid gap-4 md:grid-cols-2 reveal">
                        @foreach ($moreOffers as $offer)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex items-center justify-between gap-4 rounded-2xl border border-white/15 bg-white/[0.06] px-5 py-4 min-h-14 transition-colors hover:bg-white/[0.12]">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-white">{{ $offer->title }}</span>
                                        <span class="block mt-0.5 text-sm text-white/80">
                                            {{ collect([$offer->discount_label, $offer->offer_price !== null ? \App\Support\Pricing\PublicPrice::format((float) $offer->offer_price) : null, $offer->ends_at ? 'حتى '.$offer->ends_at->translatedFormat('j F Y') : null])->filter()->implode(' · ') }}
                                        </span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-white rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-public.container>

            @if ($areas->isNotEmpty())
                <x-public.wave shape="curve" position="bottom" class="text-primary-50" />
            @endif
        </section>
    @endif

    {{-- ===== 7. Where it is available - compact tinted strip ===== --}}
    @if ($areas->isNotEmpty())
        <section class="surface-tint relative overflow-hidden" aria-labelledby="service-areas">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16 items-center reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-700">مناطق التغطية</p>
                        <h2 id="service-areas" class="mt-2 font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">
                            نقدّم هذه الخدمة في
                        </h2>
                    </div>
                    <ul class="flex flex-wrap gap-2.5">
                        @foreach ($areas as $area)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                    class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white ring-1 ring-primary-200/70 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                    <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />
                                    {{ $area->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 8. What customers of this service said ===== --}}
    @if ($leadTestimonial)
        <section class="bg-white" aria-labelledby="service-testimonials">
            <x-public.container width="wide" class="py-16 md:py-24">
                <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:gap-16 items-start">
                    <figure class="surface-tint relative overflow-hidden rounded-3xl p-8 md:p-10 reveal">
                        <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                        <h2 id="service-testimonials" class="relative text-sm font-medium tracking-wide text-primary-700">آراء العملاء</h2>
                        <blockquote class="relative mt-4 font-display text-xl md:text-3xl font-light leading-[1.5] text-ink-950 text-balance">
                            {{ $leadTestimonial->content }}
                        </blockquote>
                        <figcaption class="relative mt-5 text-sm text-neutral-600">
                            {{ collect([$leadTestimonial->author_name, $leadTestimonial->area?->name])->filter()->implode(' · ') }}
                        </figcaption>
                    </figure>

                    @if ($otherTestimonials->isNotEmpty())
                        <ul class="divide-y divide-neutral-200 reveal">
                            @foreach ($otherTestimonials as $testimonial)
                                <li class="py-5 first:pt-0">
                                    <p class="text-neutral-700 leading-relaxed">{{ $testimonial->content }}</p>
                                    <p class="mt-2 text-sm text-neutral-500">
                                        {{ collect([$testimonial->author_name, $testimonial->area?->name])->filter()->implode(' · ') }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 9. Related services - suppressed when the editor already
             placed a manual related_content block. ===== --}}
    @if (! $hasManualRelatedBlock && $related->isNotEmpty())
        <section aria-labelledby="service-related">
            <x-public.container width="wide" class="py-14 md:py-20">
                <h2 id="service-related" class="text-sm font-medium tracking-wide text-neutral-500">خدمات ذات صلة</h2>
                <ul class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 reveal">
                    @foreach ($related as $item)
                        <li>
                            <a href="{{ $urlResolver->urlForPage($item->page) }}"
                                class="group flex items-center justify-between gap-4 rounded-2xl bg-white ring-1 ring-ink-950/5 px-5 py-4 min-h-14 shadow-sm transition-[box-shadow,ring-color] hover:shadow-md hover:ring-primary-200">
                                <span class="min-w-0">
                                    <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors">{{ $item->name }}</span>
                                    @if ($price = $item->publicPrice())
                                        <span class="block mt-0.5 text-sm text-neutral-500 tabular-nums">{{ $price->label() }}</span>
                                    @endif
                                </span>
                                <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 10. Remaining objections, right before the decision =====
         Second pass: the FAQ block only. blocks.blade.php still owns the
         heading and the markup, and still applies the rule that the FAQ
         appears only when an active faq block exists AND real questions
         are attached. --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" />

    {{-- ===== 11. Decision - the homepage's closing gesture ===== --}}
    <section class="surface-atmos relative isolate overflow-hidden text-white">
        @if ($surfaceBeforeDecision)
            <x-public.wave shape="soft" position="top" :class="$surfaceBeforeDecision" />
        @endif
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" @class(['pb-16 md:pb-24', 'pt-28 md:pt-36' => (bool) $surfaceBeforeDecision, 'pt-16 md:pt-24' => ! $surfaceBeforeDecision])>
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                <div>
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">
                        {{ 'هل تحتاج '.$service->name.'؟' }}
                    </h2>
                    <p class="mt-4 text-lg text-white/85 leading-relaxed max-w-xl">أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر مناسب. لا يتم الدفع عبر الموقع.</p>
                </div>
                <div class="flex flex-col items-start gap-3 lg:items-stretch">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40 lg:justify-center">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp" class="lg:justify-center">تواصل عبر واتساب</x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
