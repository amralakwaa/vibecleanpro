{{--
    Area Detail - Local Proof, in the Homepage V2 / Service V2 system.

    A Service page answers "what is this service and why would I order
    it". This page answers a different question entirely: "do you
    actually work in MY neighbourhood, and what is the proof?" So local
    proof is the spine here, not a supporting section - the order runs
    hero -> work done HERE -> the editor's local prose -> what is
    available HERE -> one offer -> a local voice -> nearby -> reading ->
    FAQ -> decision.

    The hero is typographic by design: a light tinted field with a
    concentric "here" mark (CSS only), the area group and city as real
    context, one action. No photograph is ever put here to stand in for
    the neighbourhood; the only pictures on the page are real projects
    attached to THIS area and the services' own library illustrations.

    DOORWAY PROTECTION (the critical rule on this template):
      - the H1 is the Page's own CMS title, verbatim
      - no templated local prose: there is no "نقدم أفضل خدمات التنظيف في
        {name}" anywhere and no heading is built around the area name -
        with one deliberate exception, the closing question in the decision
        band, which uses the name once as natural customer copy. The group
        and city labels are relation values printed as facts. The only
        unique local prose comes from editor-authored Content Blocks
      - every section disappears when its relation is empty; a published
        local page never shows an empty state to a visitor

    The area name still appears in the prefilled WhatsApp message, which
    is a chat draft rather than page content, and never renders on screen.
--}}
@php
    use App\Support\Pricing\PublicPrice;

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في طلب خدمة في '.$area->name);
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', ['area' => $area->id]);

    $projectShowcases = $projects->filter(
        fn ($project) => $project->media->firstWhere('pivot.stage', 'before') && $project->media->firstWhere('pivot.stage', 'after')
    );
    $projectCards = $projects->reject(fn ($project) => $projectShowcases->contains($project));
    $hasProjectPhotos = $projectShowcases->isNotEmpty() || $projectCards->contains(fn ($project) => $project->media->isNotEmpty());
    $leadTestimonial = $testimonials->first();
    $leadOffer = $offers->first();

    // Real context only: the group this area belongs to and the city the
    // business states for itself. Nothing is written when neither exists.
    $context = collect([$area->group?->name, $businessProfile?->city])->filter()->unique()->values();

    // Services grouped by their category (uncategorised last), so a longer
    // list scans by kind instead of as one flat column.
    $serviceGroups = $services->groupBy(fn ($service) => $service->category?->name ?? '')->sortKeys()->sortBy(fn ($group, $key) => $key === '' ? 1 : 0);

    $hasFaqSection = $faqs->isNotEmpty() && $page->contentBlocks->contains(fn ($block) => $block->type === 'faq' && $block->is_active);
    $surfaceBeforeDecision = match (true) {
        $hasFaqSection => 'text-white',
        $articles->isNotEmpty() => 'text-background',
        $nearbyAreas->isNotEmpty() => 'text-primary-50',
        (bool) $leadTestimonial => 'text-white',
        default => null,
    };
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Local hero - typographic, tinted, one action ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>
        {{-- The "here" mark: concentric rings around a pin, drawn in CSS.
             Decorative only - it says "a place", never "this place". --}}
        <div class="pointer-events-none absolute -z-10 end-[-6rem] top-1/2 -translate-y-1/2 hidden md:block" aria-hidden="true">
            <div class="relative w-[26rem] h-[26rem] lg:w-[32rem] lg:h-[32rem]">
                <div class="absolute inset-0 rounded-full border border-primary-300/50"></div>
                <div class="absolute inset-[16%] rounded-full border border-primary-400/50"></div>
                <div class="absolute inset-[32%] rounded-full border border-primary-500/40"></div>
                <div class="absolute inset-[48%] rounded-full bg-primary-600/10 border border-primary-500/60"></div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-primary-600 shadow-[0_0_0_8px_rgba(37,99,235,0.15)]"></div>
            </div>
        </div>

        <x-public.container width="wide" class="relative pt-8 pb-20 md:pt-12 md:pb-28">
            <div class="max-w-2xl">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                <p class="inline-flex items-center gap-2 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700 backdrop-blur-sm">
                    <x-public.icon name="map-pin" class="w-4 h-4" />
                    منطقة تغطية
                    @if ($context->isNotEmpty())
                        <span class="text-primary-400" aria-hidden="true">·</span>
                        <span class="text-ink-950/80">{{ $context->implode(' · ') }}</span>
                    @endif
                </p>

                <h1 class="mt-5 font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] lg:text-6xl font-medium tracking-tight text-ink-950 text-balance">
                    {{ $page->title }}
                </h1>

                {{-- Real local cues only - each line is a relation that has rows. --}}
                @if ($services->isNotEmpty() || $projects->isNotEmpty() || $nearbyAreas->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-neutral-600">
                        @if ($services->isNotEmpty())
                            <li class="flex items-center gap-2"><x-public.icon name="check-circle" class="w-4 h-4 text-primary-600 shrink-0" /> هذه المنطقة ضمن نطاق خدمتنا</li>
                        @endif
                        @if ($projects->isNotEmpty())
                            <li class="flex items-center gap-2"><x-public.icon name="briefcase" class="w-4 h-4 text-primary-600 shrink-0" /> لدينا أعمال منفذة هنا</li>
                        @endif
                        @if ($nearbyAreas->isNotEmpty())
                            <li class="flex items-center gap-2"><x-public.icon name="map-pin" class="w-4 h-4 text-primary-600 shrink-0" /> ونخدم المناطق المجاورة لها</li>
                        @endif
                    </ul>
                @endif

                <div class="mt-8 flex flex-wrap items-center gap-3" data-hero-cta>
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-600/25">اطلب عرض سعر</x-public.button>

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>

        <x-public.wave shape="curve" position="bottom" :class="$projects->isNotEmpty() ? 'text-white' : 'text-background'" />
    </section>

    {{-- ===== 2. Real work done in THIS area - the page's centre =====
         Photographs when the projects have them; an honest compact
         reference list when they do not. Never a stock picture. --}}
    @if ($projects->isNotEmpty())
        <section class="bg-white" aria-labelledby="area-evidence">
            <x-public.container width="wide" @class(['py-16 md:py-24' => $hasProjectPhotos, 'py-12 md:py-16' => ! $hasProjectPhotos])>
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div class="max-w-2xl">
                        <p class="text-sm font-medium tracking-wide text-primary-700">أعمالنا في هذه المنطقة</p>
                        <h2 id="area-evidence" @class(['mt-2 font-display font-medium tracking-tight text-ink-950 text-balance', 'text-3xl md:text-5xl md:leading-[1.1]' => $hasProjectPhotos, 'text-2xl md:text-3xl' => ! $hasProjectPhotos])>
                            الدليل أننا نعمل هنا فعلًا
                        </h2>
                    </div>
                    <a href="{{ route('public.projects.index', ['area' => $area->id]) }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                        كل الأعمال هنا
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                @if ($projectShowcases->isNotEmpty())
                    <div @class(['mt-10 grid gap-10 md:gap-12 reveal', 'md:grid-cols-2' => $projectShowcases->count() > 1])>
                        @foreach ($projectShowcases as $project)
                            <x-public.evidence-band
                                :project="$project"
                                :url="$urlResolver->urlForPage($project->page)"
                                :before="$project->media->firstWhere('pivot.stage', 'before')"
                                :after="$project->media->firstWhere('pivot.stage', 'after')"
                            />
                        @endforeach
                    </div>
                @endif

                @if ($projectCards->isNotEmpty())
                    <ul @class(['reveal', 'grid sm:grid-cols-2 lg:grid-cols-3 gap-5' => $hasProjectPhotos, 'flex flex-wrap gap-2.5' => ! $hasProjectPhotos, 'mt-12' => $projectShowcases->isNotEmpty(), 'mt-10' => $projectShowcases->isEmpty() && $hasProjectPhotos, 'mt-6' => ! $hasProjectPhotos])>
                        @foreach ($projectCards as $project)
                            @php($cover = $project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first())
                            <li>
                                @if ($cover)
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group relative isolate flex flex-col justify-end overflow-hidden rounded-2xl text-white aspect-[4/3] shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300">
                                        <img src="{{ $cover->url() }}" srcset="{{ $cover->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" alt="{{ $cover->alt_text ?? $project->title }}" loading="lazy"
                                            width="{{ $cover->width ?: 800 }}" height="{{ $cover->height ?: 600 }}"
                                            class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                                        <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                                        <div class="p-5">
                                            <p class="font-display text-lg font-medium tracking-tight text-white">{{ $project->title }}</p>
                                            @if ($project->completed_at)
                                                <p class="mt-1 text-sm text-white/80">{{ $project->completed_at->translatedFormat('F Y') }}</p>
                                            @endif
                                        </div>
                                    </a>
                                @elseif ($hasProjectPhotos)
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group flex items-center justify-between gap-4 rounded-2xl bg-neutral-50 ring-1 ring-ink-950/5 px-5 py-4 min-h-14 h-full text-ink-950 hover:ring-primary-200 transition-[box-shadow,ring-color]">
                                        <span>
                                            <span class="block font-medium group-hover:text-primary-700 transition-colors">{{ $project->title }}</span>
                                            @if ($project->completed_at)
                                                <span class="block mt-0.5 text-sm text-neutral-500">{{ $project->completed_at->translatedFormat('F Y') }}</span>
                                            @endif
                                        </span>
                                        <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                    </a>
                                @else
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group inline-flex items-center gap-2 min-h-11 rounded-full bg-neutral-50 ring-1 ring-ink-950/10 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                        <x-public.icon name="briefcase" class="w-4 h-4 text-primary-600" />
                                        {{ $project->title }}
                                        @if ($project->completed_at)
                                            <span class="text-neutral-500 font-normal">· {{ $project->completed_at->translatedFormat('F Y') }}</span>
                                        @endif
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 3. Local editorial content (CMS only, FAQ deferred) ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" :related="$services" related-item-type="service" />

    {{-- ===== 4. Services genuinely available here - image-led rows,
             grouped by category, the service's own price when public ===== --}}
    @if ($services->isNotEmpty())
        <section id="services" class="surface-tint relative overflow-hidden" aria-labelledby="area-services">
            <x-public.container width="wide" class="py-16 md:py-24">
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div class="max-w-2xl">
                        <p class="text-sm font-medium tracking-wide text-primary-700">الخدمات المتاحة هنا</p>
                        <h2 id="area-services" class="mt-2 font-display text-3xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">
                            ما يمكنك طلبه في هذه المنطقة
                        </h2>
                    </div>
                    <a href="{{ route('public.services.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                        جميع الخدمات
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                <div class="mt-8 md:mt-10 space-y-8 reveal">
                    @foreach ($serviceGroups as $categoryName => $group)
                        <div>
                            @if ($categoryName !== '' && $serviceGroups->count() > 1)
                                <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-3">{{ $categoryName }}</h3>
                            @endif
                            <ul class="grid gap-3 md:grid-cols-2">
                                @foreach ($group as $service)
                                    <li>
                                        <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                            class="group flex items-stretch overflow-hidden rounded-2xl bg-white ring-1 ring-ink-950/5 shadow-sm min-h-20 transition-[box-shadow,ring-color] hover:shadow-md hover:ring-primary-200">
                                            @if ($service->featuredMedia)
                                                <span class="relative w-24 sm:w-28 shrink-0 overflow-hidden">
                                                    <img src="{{ $service->featuredMedia->url() }}" srcset="{{ $service->featuredMedia->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" alt="{{ $service->featuredMedia->alt_text ?? $service->name }}" loading="lazy"
                                                        width="{{ $service->featuredMedia->width ?: 800 }}" height="{{ $service->featuredMedia->height ?: 600 }}"
                                                        class="tile-media absolute inset-0 w-full h-full object-cover">
                                                </span>
                                            @else
                                                <span class="surface-offer w-24 sm:w-28 shrink-0" aria-hidden="true"></span>
                                            @endif
                                            <span class="flex min-w-0 grow items-center justify-between gap-4 px-4 py-3">
                                                <span class="min-w-0">
                                                    <span class="block font-display text-lg font-medium text-ink-950 group-hover:text-primary-700 transition-colors">{{ $service->name }}</span>
                                                    @if ($price = $service->publicPrice())
                                                        <span class="block mt-0.5 text-sm text-neutral-600 tabular-nums">{{ $price->label() }}</span>
                                                    @endif
                                                </span>
                                                <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 5. One active offer tied to this area - compact ===== --}}
    @if ($leadOffer)
        <section class="surface-offer relative isolate overflow-hidden text-white" aria-labelledby="area-offer">
            <div class="glow-primary absolute -top-24 -end-24 w-[24rem] h-[24rem] -z-10 opacity-80" aria-hidden="true"></div>
            <x-public.container width="wide" class="py-12 md:py-16">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center reveal">
                    <div>
                        <h2 id="area-offer" class="text-sm font-medium tracking-wide text-primary-100">عروض في هذه المنطقة</h2>
                        <p class="mt-2 font-display text-2xl md:text-3xl font-medium tracking-tight text-white text-balance">{{ $leadOffer->title }}</p>
                        <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white/85">
                            @if ($leadOffer->discount_label)
                                <span class="inline-flex items-center rounded-full bg-white text-primary-700 px-3 py-1 font-medium">{{ $leadOffer->discount_label }}</span>
                            @endif
                            @if ($leadOffer->offer_price !== null)
                                <span class="font-display text-xl font-medium text-white tabular-nums">{{ PublicPrice::format((float) $leadOffer->offer_price) }}</span>
                            @endif
                            @if ($leadOffer->ends_at)
                                <span>حتى {{ $leadOffer->ends_at->translatedFormat('j F Y') }}</span>
                            @endif
                        </p>
                    </div>
                    <x-public.button :href="$urlResolver->urlForPage($leadOffer->page)" variant="cta" size="lg" icon-trailing="arrow-start" class="!bg-white !text-primary-700 hover:!bg-primary-50 self-start">تفاصيل العرض</x-public.button>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 6. A voice from this area ===== --}}
    @if ($leadTestimonial)
        <section class="bg-white" aria-labelledby="area-testimonial">
            <x-public.container width="wide" class="py-14 md:py-20">
                <figure class="surface-tint relative overflow-hidden rounded-3xl p-8 md:p-10 max-w-3xl reveal">
                    <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                    <h2 id="area-testimonial" class="relative text-sm font-medium tracking-wide text-primary-700">من عملائنا هنا</h2>
                    <blockquote class="relative mt-4 font-display text-xl md:text-3xl font-light leading-[1.5] text-ink-950 text-balance">
                        {{ $leadTestimonial->content }}
                    </blockquote>
                    <figcaption class="relative mt-5 text-sm text-neutral-600">{{ $leadTestimonial->author_name }}</figcaption>
                </figure>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 7. Nearby areas - local navigation inside the same group,
             never a link dump ===== --}}
    @if ($nearbyAreas->isNotEmpty())
        <section class="surface-tint relative overflow-hidden" aria-labelledby="area-nearby">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16 items-center reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-700">مناطق مجاورة</p>
                        <h2 id="area-nearby" class="mt-2 font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">
                            نخدم أيضًا المناطق المجاورة
                        </h2>
                        {{-- The group is printed as a fact label, never worked into a sentence. --}}
                        @if ($area->group?->name)
                            <p class="mt-3 inline-flex items-center gap-2 text-sm text-neutral-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>
                                {{ $area->group->name }}
                            </p>
                        @endif
                    </div>
                    <ul class="flex flex-wrap gap-2.5">
                        @foreach ($nearbyAreas as $nearby)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($nearby->page) }}"
                                    class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white ring-1 ring-primary-200/70 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                    <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />
                                    {{ $nearby->name }}
                                </a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{ route('public.areas.index') }}" class="inline-flex items-center gap-2 min-h-11 px-2 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                                جميع المناطق
                                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                            </a>
                        </li>
                    </ul>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 8. Related reading - quiet rows, no second card system ===== --}}
    @if ($articles->isNotEmpty())
        <section aria-labelledby="area-articles">
            <x-public.container width="wide" class="py-14 md:py-20">
                <h2 id="area-articles" class="text-sm font-medium tracking-wide text-neutral-500">مقالات مرتبطة</h2>
                <ul class="mt-4 max-w-3xl divide-y divide-neutral-200 border-t border-neutral-200 reveal">
                    @foreach ($articles as $article)
                        <li>
                            <a href="{{ $urlResolver->urlForPage($article->page) }}"
                                class="group flex items-center justify-between gap-4 py-4 min-h-14 text-ink-950">
                                <span class="min-w-0">
                                    <span class="block font-medium group-hover:text-primary-700 transition-colors">{{ $article->title }}</span>
                                    @if ($article->category?->name)
                                        <span class="block mt-0.5 text-sm text-neutral-500">{{ $article->category->name }}</span>
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

    {{-- ===== 9. FAQ - second blocks pass, immediately before the
             decision. blocks.blade.php still owns all block data. ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" />

    {{-- ===== 10. Decision - the closing gesture. The area's own name is
             used ONCE here, in natural customer copy ("do you need a
             cleaning service in X?"); every other heading on the page stays
             generic, so this is context for the visitor, not a keyword. ===== --}}
    <section class="surface-atmos relative isolate overflow-hidden text-white">
        @if ($surfaceBeforeDecision)
            <x-public.wave shape="soft" position="top" :class="$surfaceBeforeDecision" />
        @endif
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" @class(['pb-16 md:pb-24', 'pt-28 md:pt-36' => (bool) $surfaceBeforeDecision, 'pt-16 md:pt-24' => ! $surfaceBeforeDecision])>
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                <div>
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">
                        {{ 'تحتاج خدمة تنظيف في '.$area->name.'؟' }}
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
