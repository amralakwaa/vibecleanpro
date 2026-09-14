{{--
    Area Detail - Evidence-Led LOCAL Landing Page.

    A Service page answers "what is this service and why would I order
    it". This page answers a different question entirely: "do you
    actually work in MY neighbourhood, and what is the proof?" So local
    proof is the spine here, not a supporting section - the order runs
    coverage answer -> local editorial -> work done HERE -> what is
    available HERE, and the page stays typographic rather than leaning on
    photography the way a Service page can.

    DOORWAY PROTECTION (the critical rule on this template):
      - the H1 is the Page's own CMS title, verbatim
      - this file NEVER composes a local sentence. There is no
        "نقدم أفضل خدمات التنظيف في {name}" anywhere, and no area name is
        substituted into any heading or body copy. The only unique local
        prose comes from editor-authored Content Blocks, rendered by
        blocks.blade.php, which stays the single interpreter of block data
      - no invented image: Area has no featured_media_id of its own, so
        the hero is typographic, and any photograph shown further down is
        a real project attached to THIS area
      - every section disappears when its relation is empty; a published
        local page never shows an empty state to a visitor

    The area name still appears in the prefilled WhatsApp message, which
    is a chat draft rather than page content, and never renders on screen.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في طلب خدمة في '.$area->name);
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', ['area' => $area->id]);

    $projectShowcases = $projects->filter(
        fn ($project) => $project->media->firstWhere('pivot.stage', 'before') && $project->media->firstWhere('pivot.stage', 'after')
    );
    $projectCards = $projects->reject(fn ($project) => $projectShowcases->contains($project));
    $leadTestimonial = $testimonials->first();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Editorial local hero - typographic by design ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-12 md:py-20">
            <div class="max-w-3xl">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-primary-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>
                    منطقة تغطية
                </p>

                <h1 class="mt-4 font-display text-[1.75rem] leading-tight md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                    {{ $page->title }}
                </h1>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>

    {{-- ===== 2. Immediate coverage answer =====
         A ruled metadata row, not counters and not cards. Each line is a
         statement of fact about a relation that actually has rows - no
         totals are advertised, because "5 خدمات" tells a visitor nothing
         they came here to find out. --}}
    @if ($services->isNotEmpty() || $projects->isNotEmpty() || $nearbyAreas->isNotEmpty())
        <section class="bg-white">
            <x-public.container width="wide">
                <ul class="flex flex-wrap items-center gap-x-8 gap-y-2.5 border-t border-neutral-200 py-5 text-sm">
                    @if ($services->isNotEmpty())
                        <li class="flex items-center gap-2 text-neutral-600">
                            <x-public.icon name="check-circle" class="w-4 h-4 text-primary-600 shrink-0" />
                            هذه المنطقة ضمن نطاق خدمتنا
                        </li>
                    @endif
                    @if ($projects->isNotEmpty())
                        <li class="flex items-center gap-2 text-neutral-600">
                            <x-public.icon name="briefcase" class="w-4 h-4 text-primary-600 shrink-0" />
                            لدينا أعمال منفذة هنا
                        </li>
                    @endif
                    @if ($nearbyAreas->isNotEmpty())
                        <li class="flex items-center gap-2 text-neutral-600">
                            <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600 shrink-0" />
                            ونخدم المناطق المجاورة لها
                        </li>
                    @endif
                </ul>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 3. Local editorial content (CMS only, FAQ deferred) ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" :related="$services" related-item-type="service" />

    {{-- ===== 4. Real work done in THIS area - the page's centre ===== --}}
    @if ($projects->isNotEmpty())
        <section class="bg-white border-y border-neutral-200">
            <x-public.container width="wide" class="py-16 md:py-24">
                <x-public.section-marker label="أعمالنا في هذه المنطقة" class="mb-10" />

                <h2 class="font-display text-2xl leading-snug md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance max-w-2xl">
                    الدليل أننا نعمل هنا فعلًا
                </h2>

                @if ($projectShowcases->isNotEmpty())
                    <div @class(['mt-10 grid gap-10 md:gap-12', 'md:grid-cols-2' => $projectShowcases->count() > 1])>
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
                    <ul @class(['grid sm:grid-cols-2 lg:grid-cols-3 gap-8', 'mt-12' => $projectShowcases->isNotEmpty(), 'mt-10' => $projectShowcases->isEmpty()])>
                        @foreach ($projectCards as $project)
                            @php($cover = $project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first())
                            <li>
                                @if ($cover)
                                    <img src="{{ $cover->url() }}" alt="{{ $cover->alt_text ?? $project->title }}" loading="lazy"
                                        class="w-full aspect-[4/3] object-cover">
                                @endif
                                <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                    class="mt-3 block font-medium text-ink-950 hover:text-primary-700 transition-colors">
                                    {{ $project->title }}
                                </a>
                                @if ($project->completed_at)
                                    <p class="mt-0.5 text-sm text-neutral-500">{{ $project->completed_at->translatedFormat('F Y') }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 5. Services genuinely available here =====
         A typographic index, lighter than the Service page's editorial
         rows: on this page the service is a destination to pick, not a
         thing to be sold in place. --}}
    @if ($services->isNotEmpty())
        <x-public.section id="services" width="wide">
            <x-public.section-marker label="الخدمات المتاحة هنا" heading class="mb-8" />

            <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-10 max-w-5xl">
                @foreach ($services as $service)
                    <li class="border-b border-neutral-200">
                        <a href="{{ $urlResolver->urlForPage($service->page) }}"
                            class="flex items-center justify-between gap-3 py-4 text-ink-950 hover:text-primary-700 transition-colors">
                            <span>{{ $service->name }}</span>
                            <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 rtl:rotate-180 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 6. Active offers tied to this area ===== --}}
    @if ($offers->isNotEmpty())
        <x-public.section tone="surface" width="wide">
            <x-public.section-marker label="عروض في هذه المنطقة" heading class="mb-8" />
            <ul class="max-w-3xl">
                @foreach ($offers as $offer)
                    <li class="border-b border-neutral-200">
                        <a href="{{ $urlResolver->urlForPage($offer->page) }}"
                            class="flex flex-wrap items-baseline gap-x-4 gap-y-1 py-4 text-ink-950 hover:text-primary-700 transition-colors">
                            <span class="font-medium">{{ $offer->title }}</span>
                            @if ($offer->discount_label)
                                <span class="text-sm text-primary-700">{{ $offer->discount_label }}</span>
                            @endif
                            @if ($offer->ends_at)
                                <span class="text-xs text-neutral-400">حتى {{ $offer->ends_at->translatedFormat('j F Y') }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 7. A voice from this area ===== --}}
    @if ($leadTestimonial)
        <x-public.section width="wide">
            <x-public.section-marker label="من عملائنا هنا" heading class="mb-10" />

            <figure class="max-w-3xl">
                <blockquote class="font-display text-xl md:text-3xl font-light leading-[1.5] text-ink-950 text-balance">
                    {{ $leadTestimonial->content }}
                </blockquote>
                <figcaption class="mt-5 text-sm text-neutral-500">{{ $leadTestimonial->author_name }}</figcaption>
            </figure>
        </x-public.section>
    @endif

    {{-- ===== 8. Nearby areas - local navigation, not a link dump ===== --}}
    @if ($nearbyAreas->isNotEmpty())
        <x-public.section tone="surface" width="wide" density="tight">
            <x-public.section-marker label="مناطق مجاورة" heading class="mb-6" />
            <ul class="flex flex-wrap gap-x-6 gap-y-3">
                @foreach ($nearbyAreas as $nearby)
                    <li>
                        <a href="{{ $urlResolver->urlForPage($nearby->page) }}"
                            class="inline-flex items-center gap-2 text-ink-950 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                            {{ $nearby->name }}
                            <x-public.icon name="arrow-start" class="w-3.5 h-3.5 text-neutral-300 rtl:rotate-180" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 9. Related reading - text links, no second card system ===== --}}
    @if ($articles->isNotEmpty())
        <x-public.section width="wide">
            <x-public.section-marker label="مقالات مرتبطة" heading class="mb-6" />
            <ul class="max-w-3xl">
                @foreach ($articles as $article)
                    <li class="border-b border-neutral-200">
                        <a href="{{ $urlResolver->urlForPage($article->page) }}"
                            class="flex flex-wrap items-baseline gap-x-4 gap-y-1 py-4 text-ink-950 hover:text-primary-700 transition-colors">
                            <span class="font-medium">{{ $article->title }}</span>
                            @if ($article->category?->name)
                                <span class="text-sm text-neutral-500">{{ $article->category->name }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 10. FAQ - second blocks pass, immediately before the
             decision. blocks.blade.php still owns all block data. ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" />

    {{-- ===== 11. Decision - a navy band against the light hero ===== --}}
    <section class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">
                    اطلب الخدمة في منطقتك
                </h2>
                <p class="mt-4 text-ink-200 leading-relaxed">أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر مناسب.</p>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">
                            تواصل عبر واتساب
                        </x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
