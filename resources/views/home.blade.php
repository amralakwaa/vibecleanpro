{{--
    Homepage V2 - Visual Excellence Reset, phase 1.

    Same architecture, same data, same links and the same honesty rules
    as before; what changed is the visual layer. The page now runs on a
    deliberate rhythm of surfaces instead of white/grey alternation:

      1 Hero          deep atmospheric navy + real photograph
      2 Services      tinted light-blue field, image-led tiles
      3 Evidence      white, photograph-led before/after
      4 B2C / B2B     split tonal doors (tinted vs navy)
      5 Offers        the loud moment - blue gradient surface
      6 Trust         clean light: identity, testimonial, principles
      7 Coverage      tinted, compact
      8 FAQ           white, quiet
      9 Decision      deep navy with glow

    Every fact stays CMS-driven (services, prices, offers, projects,
    testimonials, areas, identity, counts) and every optional section
    vanishes when its data is empty. Radius language: tiles 2xl, panels
    3xl, controls xl. Motion: CSS-only reveal + tile hover, both silenced
    by prefers-reduced-motion.
--}}
@php
    use App\Enums\OfferAvailability;
    use App\Seo\UrlResolver;
    use App\Support\Pricing\OfferPrice;

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن خدماتكم');
    $urlResolver = app(UrlResolver::class);
    $quoteUrl = route('public.quote');
    $businessUrl = route('public.contact', ['for' => 'business']);

    $featuredService = $services->first();
    $tileServices = $services->skip(1);

    $leadProject = $beforeAfterProjects->first();
    $supportingProjects = $beforeAfterProjects->skip(1)->take(2);
    $leadTestimonial = $testimonials->first();

    $activeOffers = $offers->filter(fn ($offer) => $offer->availability() === OfferAvailability::Active)->values();
    $scheduledOffers = $offers->filter(fn ($offer) => $offer->availability() === OfferAvailability::Scheduled)->values();
    $leadOffer = $activeOffers->first();
    $moreOffers = $activeOffers->skip(1);

    $values = collect($businessProfile?->values ?? [])->filter(fn ($value) => filled($value['title'] ?? null))->take(4)->values();

    // Hero facts - only real, published counts and stored identity.
    $facts = collect([
        $businessProfile?->city ? ['label' => 'نخدم', 'value' => $businessProfile->city] : null,
        $publishedAreas >= 3 ? ['label' => 'مناطق التغطية', 'value' => $publishedAreas.' '.($publishedAreas <= 10 ? 'أحياء' : 'حيًا')] : null,
        $publishedProjects >= 3 ? ['label' => 'أعمال موثقة', 'value' => $publishedProjects.' '.($publishedProjects <= 10 ? 'مشاريع' : 'مشروعًا')] : null,
    ])->filter()->values()->all();

    // B2C door photo: a service photo that is not already the featured tile.
    $b2cImage = $tileServices->first(fn ($service) => $service->featuredMedia)?->featuredMedia ?? $featuredService?->featuredMedia;
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :header-overlay="true">

    {{-- ===== 1. Hero ===== --}}
    <x-public.home-hero
        :image="$heroImage"
        :eyebrow="$businessProfile?->tagline ?: 'شركة تنظيف في الرياض'"
        heading="نظافة تراها من أول نظرة"
        description="تنظيف احترافي للفلل والشقق والمكاتب في الرياض - فريق مدرّب، سعر واضح قبل البدء، ونتيجة موثقة بالصور."
        :cta-url="$quoteUrl"
        cta-label="اطلب عرض سعر"
        :whatsapp-url="$whatsappUrl"
        :secondary-url="route('public.projects.index')"
        secondary-label="شاهد أعمالنا"
        :services="$services"
        :facts="$facts"
    />

    {{-- ===== 2. Services - image-led tiles on a tinted field ===== --}}
    @if ($services->isNotEmpty())
        <section class="surface-tint relative" aria-labelledby="home-services">
            <x-public.container width="wide" class="pt-6 pb-16 md:pt-10 md:pb-24">
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div class="max-w-2xl">
                        <p class="text-sm font-medium tracking-wide text-primary-700">خدماتنا</p>
                        <h2 id="home-services" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                            اختر الخدمة التي يحتاجها مكانك
                        </h2>
                    </div>
                    <a href="{{ route('public.services.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                        جميع الخدمات
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                <div class="mt-8 md:mt-10 grid gap-4 md:gap-5 md:grid-cols-2 lg:grid-cols-4 lg:auto-rows-[15rem]">
                    {{-- Featured: a large photograph tile spanning two columns and two rows. --}}
                    @if ($featuredService)
                        <a href="{{ $urlResolver->urlForPage($featuredService->page) }}"
                            class="group relative isolate flex flex-col justify-end overflow-hidden rounded-2xl text-white min-h-[22rem] md:min-h-[20rem] md:col-span-2 lg:min-h-0 lg:row-span-2 shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300 reveal">
                            @if ($featuredService->featuredMedia)
                                <img src="{{ $featuredService->featuredMedia->url() }}" alt="{{ $featuredService->featuredMedia->alt_text ?? $featuredService->name }}"
                                    width="1200" height="800"
                                    class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                            @else
                                <div class="surface-atmos absolute inset-0 -z-20" aria-hidden="true"></div>
                            @endif
                            <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                            <div class="p-6 md:p-8">
                                @if ($featuredService->category)
                                    <p class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium tracking-wide text-white backdrop-blur-sm">{{ $featuredService->category->name }}</p>
                                @endif
                                <h3 class="mt-3 font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">{{ $featuredService->name }}</h3>
                                @if ($featuredService->short_description)
                                    <p class="mt-2 text-white/80 leading-relaxed max-w-md line-clamp-2">{{ $featuredService->short_description }}</p>
                                @endif
                                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                                    @if ($price = $featuredService->publicPrice())
                                        <span class="inline-flex items-center rounded-full bg-white/15 px-3.5 py-1.5 text-sm font-medium text-white backdrop-blur-sm tabular-nums">{{ $price->label() }}</span>
                                    @endif
                                    <span class="inline-flex items-center gap-2 text-sm font-medium text-white">
                                        تفاصيل الخدمة
                                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endif

                    {{-- The rest: the same photographic language as the featured
                         tile, one size down - the photo IS the tile, the copy sits
                         on its scrim. A service without a photo gets a lit blue
                         field with its name set large behind the label - a
                         designed fallback, never a grey rectangle. --}}
                    @foreach ($tileServices as $service)
                        <a href="{{ $urlResolver->urlForPage($service->page) }}"
                            class="group relative isolate flex flex-col justify-end overflow-hidden rounded-2xl text-white min-h-[15rem] md:min-h-[16rem] lg:min-h-0 shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300 reveal">
                            @if ($service->featuredMedia)
                                <img src="{{ $service->featuredMedia->url() }}" alt="{{ $service->featuredMedia->alt_text ?? $service->name }}"
                                    loading="lazy" width="640" height="427"
                                    class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                            @else
                                <div class="surface-offer absolute inset-0 -z-20 flex items-start justify-end p-4" aria-hidden="true">
                                    <span class="font-display text-4xl font-medium leading-none text-white/15 text-end">{{ $service->name }}</span>
                                </div>
                            @endif
                            <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                            <div class="p-5">
                                @if ($service->category)
                                    <p class="text-xs font-medium tracking-wide text-primary-200">{{ $service->category->name }}</p>
                                @endif
                                <h3 class="mt-1 font-display text-xl md:text-2xl font-medium tracking-tight text-white text-balance">{{ $service->name }}</h3>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    @if ($price = $service->publicPrice())
                                        <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white backdrop-blur-sm tabular-nums">{{ $price->label() }}</span>
                                    @else
                                        <span class="text-sm text-white/80">اطلب عرض سعر</span>
                                    @endif
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-white rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 3. Evidence - photograph-led proof on white ===== --}}
    @if ($beforeAfterProjects->isNotEmpty())
        <section class="bg-white" aria-labelledby="home-evidence">
            <x-public.container width="wide" class="py-16 md:py-24">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] lg:gap-14 items-end reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-700">نتائج حقيقية</p>
                        <h2 id="home-evidence" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                            قبل وبعد - من مواقع نفذنا فيها العمل فعليًا
                        </h2>
                        <p class="mt-4 text-neutral-600 leading-relaxed max-w-md">صور حقيقية بلا فلاتر، بتاريخها ومكانها. كل مشروع له صفحة تروي ما حدث.</p>
                        <a href="{{ route('public.projects.index') }}" class="mt-6 inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                            جميع الأعمال
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>

                    <x-public.evidence-band
                        :project="$leadProject"
                        :url="$urlResolver->urlForPage($leadProject->page)"
                        :before="$leadProject->media->firstWhere('pivot.stage', 'before')"
                        :after="$leadProject->media->firstWhere('pivot.stage', 'after')"
                        :service-name="$leadProject->services->first()?->name"
                        :area-name="$leadProject->area?->name"
                    />
                </div>

                @if ($supportingProjects->isNotEmpty())
                    <div class="mt-12 grid md:grid-cols-2 gap-8 md:gap-10 reveal">
                        @foreach ($supportingProjects as $project)
                            <x-public.evidence-band
                                :project="$project"
                                :url="$urlResolver->urlForPage($project->page)"
                                :before="$project->media->firstWhere('pivot.stage', 'before')"
                                :after="$project->media->firstWhere('pivot.stage', 'after')"
                                :service-name="$project->services->first()?->name"
                                :area-name="$project->area?->name"
                            />
                        @endforeach
                    </div>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 4. B2C / B2B - two doors with two personalities ===== --}}
    <section class="grid lg:grid-cols-[1.15fr_1fr]" aria-label="اختر ما يناسبك">
        <div class="surface-tint relative overflow-hidden px-6 py-14 md:px-12 md:py-20 lg:px-16 flex flex-col justify-center">
            <div class="glow-primary absolute -bottom-20 -start-20 w-72 h-72 opacity-60" aria-hidden="true"></div>
            <div class="relative grid gap-8 md:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)] items-center">
                <div class="reveal">
                    <p class="text-sm font-medium tracking-wide text-primary-700">للأفراد والمنازل</p>
                    <h2 class="mt-3 font-display text-3xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">
                        عناية بمساحتك التي تعود إليها كل يوم
                    </h2>
                    <p class="mt-4 text-neutral-600 leading-relaxed max-w-md">
                        فلل، منازل وشقق - خدمة تُحجز بسرعة، وفريق يصل في الموعد، ونتيجة تراها بنفسك.
                    </p>
                    <div class="mt-7 flex flex-wrap items-center gap-4">
                        <x-public.button :href="$quoteUrl" variant="cta" icon="check-circle">اطلب عرض سعر</x-public.button>
                        <a href="{{ route('public.services.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                            تصفح الخدمات المنزلية
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                </div>
                @if ($b2cImage)
                    {{-- The home photo is a landscape strip above the copy on
                         phones and a tall portrait beside it from tablet up. --}}
                    <img src="{{ $b2cImage->url() }}" alt="{{ $b2cImage->alt_text ?? '' }}" loading="lazy" width="800" height="1000"
                        class="-order-1 md:order-none w-full aspect-[16/10] md:aspect-[4/5] object-cover rounded-2xl shadow-lg shadow-primary-900/10 reveal">
                @endif
            </div>
        </div>

        <div class="surface-atmos relative overflow-hidden text-white px-6 py-14 md:px-12 md:py-20 lg:px-16 flex flex-col justify-center">
            {{-- A faint dot grid gives the corporate door a drafted, technical
                 texture so it reads as a different material from the B2C
                 photograph beside it - not just a darker box. --}}
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.09)_1px,transparent_1.5px)] bg-[size:26px_26px] [mask-image:linear-gradient(to_bottom,black_20%,transparent_95%)]" aria-hidden="true"></div>
            <div class="glow-primary absolute -top-24 -end-24 w-80 h-80 opacity-50" aria-hidden="true"></div>
            <div class="relative reveal">
                <p class="text-sm font-medium tracking-wide text-primary-200">للشركات والمنشآت</p>
                <h2 class="mt-3 font-display text-3xl md:text-4xl font-medium tracking-tight text-white text-balance">
                    عقود تشغيل ونظافة مُدارة بمعايير مؤسسية
                </h2>
                <p class="mt-4 text-ink-100/85 leading-relaxed max-w-md">
                    مكاتب، منشآت وإدارة مرافق - نطاق عمل مكتوب، فريق ثابت، وتقارير جودة دورية.
                </p>
                <ul class="mt-6 grid gap-2.5 text-sm text-ink-100/90">
                    <li class="flex items-center gap-2.5 rounded-xl bg-white/[0.06] border border-white/10 px-3.5 py-2.5">
                        <x-public.icon name="check" class="w-4 h-4 text-primary-300 shrink-0" /> نطاق عمل وجدول زمني متفق عليه
                    </li>
                    <li class="flex items-center gap-2.5 rounded-xl bg-white/[0.06] border border-white/10 px-3.5 py-2.5">
                        <x-public.icon name="check" class="w-4 h-4 text-primary-300 shrink-0" /> فريق مخصص ومشرف مسؤول
                    </li>
                    <li class="flex items-center gap-2.5 rounded-xl bg-white/[0.06] border border-white/10 px-3.5 py-2.5">
                        <x-public.icon name="check" class="w-4 h-4 text-primary-300 shrink-0" /> مراجعة جودة موثقة
                    </li>
                </ul>
                <div class="mt-8">
                    <x-public.button :href="$businessUrl" variant="cta" icon="building">اطلب عرض تعاقد</x-public.button>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== 5. Offers - the loud moment ===== --}}
    @if ($activeOffers->isNotEmpty() || $scheduledOffers->isNotEmpty())
        <section class="surface-offer relative isolate overflow-hidden text-white" aria-labelledby="home-offers">
            <div class="glow-primary absolute top-1/3 -end-32 w-[30rem] h-[30rem] -z-10 opacity-80" aria-hidden="true"></div>

            <x-public.container width="wide" class="pt-24 pb-20 md:pt-32 md:pb-28">
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-100">العروض</p>
                        <h2 id="home-offers" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">
                            {{ $leadOffer ? 'عرض متاح الآن' : 'عروض قادمة' }}
                        </h2>
                    </div>
                    <a href="{{ route('public.offers.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white underline-offset-4 hover:underline">
                        كل العروض
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                @if ($leadOffer)
                    @php
                        $leadOfferPrice = OfferPrice::forOffer($leadOffer, $leadOffer->services);
                        $leadOfferService = $leadOffer->services->first();
                        // An offer without its own picture borrows the covered
                        // service's - the same image the visitor already saw
                        // in the services grid, never an invented "sale" visual.
                        $leadOfferImage = $leadOffer->featuredMedia ?? $leadOfferService?->featuredMedia;
                    @endphp
                    <article class="mt-8 md:mt-10 rounded-3xl bg-white/[0.08] border border-white/15 backdrop-blur-sm overflow-hidden reveal">
                        <div @class(['grid items-stretch', 'lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]' => (bool) $leadOfferImage])>
                            <div class="p-7 md:p-10 lg:p-12 flex flex-col justify-center">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($leadOffer->discount_label)
                                        <span class="inline-flex items-center rounded-full bg-white text-primary-700 px-3.5 py-1.5 text-sm font-medium">{{ $leadOffer->discount_label }}</span>
                                    @endif
                                    @if ($leadOfferService)
                                        <span class="inline-flex items-center rounded-full border border-white/25 px-3.5 py-1.5 text-sm text-white/90">{{ $leadOfferService->name }}</span>
                                    @endif
                                </div>
                                <h3 class="mt-5 font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-white text-balance">
                                    {{ $leadOffer->title }}
                                </h3>
                                @if ($leadOfferPrice)
                                    <p class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                        <span class="text-sm text-white/85">سعر العرض</span>
                                        <span class="font-display text-3xl md:text-4xl font-medium text-white tabular-nums">{{ $leadOfferPrice->label() }}</span>
                                        @if ($leadOfferPrice->beforeLabel())
                                            <span class="text-sm text-white/85">بدلًا من <s class="tabular-nums">{{ $leadOfferPrice->beforeLabel() }}</s></span>
                                        @endif
                                    </p>
                                @endif
                                @if ($leadOffer->ends_at)
                                    <p class="mt-3 text-sm text-white/85">ساري حتى <time datetime="{{ $leadOffer->ends_at->toDateString() }}">{{ $leadOffer->ends_at->translatedFormat('j F Y') }}</time></p>
                                @endif
                                <div class="mt-8 flex flex-wrap items-center gap-4">
                                    <x-public.button :href="route('public.quote', array_filter(['service' => $leadOfferService?->id]))" variant="cta" size="lg" icon="check-circle" class="!bg-white !text-primary-700 hover:!bg-primary-50">اطلب هذا العرض</x-public.button>
                                    <a href="{{ $urlResolver->urlForPage($leadOffer->page) }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white underline-offset-4 hover:underline">
                                        تفاصيل العرض
                                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                                    </a>
                                </div>
                            </div>
                            @if ($leadOfferImage)
                                {{-- The offer photo bleeds into the panel on its inner
                                     edge (masked, like the hero) instead of butting
                                     against the copy with a hard seam. --}}
                                <div class="relative min-h-[16rem] lg:min-h-0 [mask-image:linear-gradient(to_bottom,transparent_0%,black_18%)] lg:[mask-image:linear-gradient(to_left,transparent_0%,black_24%)]">
                                    <img src="{{ $leadOfferImage->url() }}" alt="{{ $leadOfferImage->alt_text ?? $leadOffer->title }}" loading="lazy"
                                        width="1200" height="800"
                                        class="absolute inset-0 w-full h-full object-cover">
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                @if ($moreOffers->isNotEmpty() || $scheduledOffers->isNotEmpty())
                    <ul class="mt-6 grid gap-4 md:grid-cols-2 reveal">
                        @foreach ($moreOffers as $offer)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex items-center justify-between gap-4 rounded-2xl bg-white/[0.08] border border-white/15 px-5 py-4 min-h-14 hover:bg-white/15 transition-colors">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-white">{{ $offer->title }}</span>
                                        <span class="block mt-0.5 text-sm text-white/85">
                                            {{ collect([$offer->discount_label, $offer->offer_price !== null ? \App\Support\Pricing\PublicPrice::format((float) $offer->offer_price) : null, $offer->ends_at ? 'حتى '.$offer->ends_at->translatedFormat('j F Y') : null])->filter()->implode(' · ') }}
                                        </span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-white/85 rtl:rotate-180 shrink-0 transition-transform group-hover:-translate-x-1" />
                                </a>
                            </li>
                        @endforeach
                        @foreach ($scheduledOffers as $offer)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex items-center justify-between gap-4 rounded-2xl border border-dashed border-white/25 px-5 py-4 min-h-14 hover:bg-white/10 transition-colors">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-white/90">{{ $offer->title }}</span>
                                        <span class="block mt-0.5 text-sm text-white/85">قريبًا · يبدأ في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time></span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-white/60 rtl:rotate-180 shrink-0" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-public.container>
            <x-public.wave shape="curve" position="bottom" class="text-white" />
        </section>
    @endif

    {{-- ===== 6. Trust - identity, a real voice, principles ===== --}}
    <section class="bg-white" aria-labelledby="home-trust">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-20">
                <div class="reveal">
                    <p class="text-sm font-medium tracking-wide text-primary-700">لماذا يثق بنا العملاء</p>
                    <h2 id="home-trust" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                        {{ $businessProfile?->identity_statement ? 'شركة محلية تعرف الرياض' : 'طريقة عمل واضحة من أول اتصال' }}
                    </h2>
                    @if ($businessProfile?->identity_statement)
                        <p class="mt-4 text-lg text-neutral-600 leading-relaxed max-w-lg">{{ $businessProfile->identity_statement }}</p>
                    @endif

                    @if ($values->isNotEmpty())
                        <ol class="mt-8 grid sm:grid-cols-2 gap-x-8">
                            @foreach ($values as $value)
                                <li class="flex gap-4 py-4 border-t border-neutral-200">
                                    <span class="font-display text-sm text-primary-600 tabular-nums shrink-0 pt-1" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <p class="font-medium text-ink-950">{{ $value['title'] }}</p>
                                        @if (filled($value['description'] ?? null))
                                            <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $value['description'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <ol class="mt-8 grid sm:grid-cols-2 gap-x-8">
                            @foreach ([
                                ['تقييم الاحتياج', 'نفهم طبيعة المساحة ومتطلبات الخدمة.'],
                                ['خطة واضحة', 'نطاق العمل والجدول الزمني قبل البدء.'],
                                ['تنفيذ بمعايير', 'فريق مدرّب ومواد مناسبة لكل مساحة.'],
                                ['مراجعة الجودة', 'نراجع النتيجة معك قبل الاعتماد النهائي.'],
                            ] as [$title, $description])
                                <li class="flex gap-4 py-4 border-t border-neutral-200">
                                    <span class="font-display text-sm text-primary-600 tabular-nums shrink-0 pt-1" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <p class="font-medium text-ink-950">{{ $title }}</p>
                                        <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($businessProfile?->hasVisibleFounder())
                        <p class="mt-6 text-sm text-neutral-500">
                            {{ collect([$businessProfile->founder_title, $businessProfile->founder_name])->filter()->implode(': ') }}
                        </p>
                    @endif
                </div>

                @if ($leadTestimonial)
                    <figure class="surface-tint relative overflow-hidden rounded-3xl p-8 md:p-10 self-start reveal">
                        <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                        <p class="relative text-sm font-medium tracking-wide text-primary-700">رأي عميل</p>
                        <blockquote class="relative mt-4 font-display text-xl md:text-2xl font-light leading-[1.6] text-ink-950 text-balance">
                            {{ $leadTestimonial->content }}
                        </blockquote>
                        <figcaption class="relative mt-6 text-sm text-neutral-600">
                            {{ collect([$leadTestimonial->author_name, $leadTestimonial->area?->name])->filter()->implode(' · ') }}
                        </figcaption>
                    </figure>
                @endif
            </div>
        </x-public.container>
    </section>

    {{-- ===== 7. Coverage - compact, tinted ===== --}}
    @if ($areas->isNotEmpty())
        <section class="surface-tint relative overflow-hidden" aria-labelledby="home-coverage">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16 items-center reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-700">مناطق التغطية</p>
                        <h2 id="home-coverage" class="mt-2 font-display text-3xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">
                            نصل إلى أحياء {{ $businessProfile?->city ?: 'الرياض' }}
                        </h2>
                        <a href="{{ route('public.areas.index') }}" class="mt-5 inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                            جميع المناطق
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                    <ul class="flex flex-wrap gap-2.5">
                        @foreach ($areas as $area)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                    class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white px-4 text-ink-950 ring-1 ring-ink-950/10 shadow-sm hover:ring-primary-300 hover:text-primary-700 transition-colors">
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

    {{-- ===== 8. FAQ - quiet ===== --}}
    @if ($faqs->isNotEmpty())
        <section class="bg-white" aria-labelledby="home-faq">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16 reveal">
                    <h2 id="home-faq" class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950">أسئلة يطرحها عملاؤنا</h2>
                    <div class="divide-y divide-neutral-200 border-t border-neutral-200">
                        @foreach ($faqs as $faq)
                            <x-public.faq-item :question="$faq->question" :answer="$faq->answer" />
                        @endforeach
                    </div>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 9. Decision ===== --}}
    <section id="contact" class="surface-atmos relative isolate overflow-hidden text-white">
        <x-public.wave shape="soft" position="top" :class="$faqs->isNotEmpty() ? 'text-white' : 'text-primary-50'" />
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-28 pb-20 md:pt-36 md:pb-28">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                <div class="max-w-2xl">
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">
                        أخبرنا بما يحتاجه مكانك، ونعود إليك بعرض سعر
                    </h2>
                    <p class="mt-4 text-ink-100/85 leading-relaxed">لا يوجد دفع عبر الموقع - تطلب، نتواصل معك على جوالك، ثم تقرر.</p>
                </div>
                <div class="flex flex-col gap-4 lg:items-end">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="w-full sm:w-auto justify-center shadow-lg shadow-primary-900/40">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp" class="w-full sm:w-auto justify-center">تواصل عبر واتساب</x-public.button>
                    @endif
                    @if ($businessProfile?->phoneUrl() && $businessProfile?->phone)
                        <a href="{{ $businessProfile->phoneUrl() }}" dir="ltr" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-white/80 hover:text-white transition-colors">
                            <x-public.icon name="phone" class="w-4 h-4" /> {{ $businessProfile->phone }}
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
