{{--
    Service Detail - Evidence-Led Editorial.

    This is a LANDING PAGE, not a smaller homepage. The homepage exists to
    establish the brand and route the visitor; this page exists to close
    one decision, so it is sequenced as the buying questions get asked:

        what is it -> what does it include -> how is it done ->
        what is the real result -> where can I get it -> who says so ->
        what am I still unsure about -> how do I order

    Differences from the homepage that are deliberate, not accidental:
      - compressed hero with a breadcrumb, photo BESIDE the copy (never
        behind it), instead of a viewport-height full-bleed image
      - no B2C/B2B split - the audience is already self-selected by the
        time someone is on a specific service
      - the editor's own content blocks (scope, process, packages, FAQ)
        carry the middle of the page, so the CMS stays the source of truth

    Every Phase 4 rule is preserved: offers are Active-only (filtered in
    SQL by the controller), before+after projects render as proof and
    after-only projects as a plain card, testimonials and areas come from
    real relations, the related-services fallback is suppressed when the
    editor placed a manual related_content block, and no section ever
    shows a customer-facing empty state - it simply disappears.

    No "starting price" is ever shown: Service has no price field of its
    own; only a real "packages" content block ever shows a real price.
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
    $leadTestimonial = $testimonials->first();
    $otherTestimonials = $testimonials->skip(1)->take(2);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Service hero (compressed, breadcrumbed) ===== --}}
    <x-public.detail-hero
        :breadcrumbs="$seo->breadcrumbs"
        :eyebrow="$service->category?->name"
        :heading="$page->title"
        :description="$service->short_description"
        :image="$service->featuredMedia"
        :cta-url="$quoteUrl"
        cta-label="اطلب عرض سعر"
        :whatsapp-url="$whatsappUrl"
    />

    {{-- Coverage/proof reassurance, as a ruled meta bar rather than the
         centered pill row this page used to open with. Each statement
         appears only when the underlying relation actually has rows. --}}
    @if ($areas->isNotEmpty() || $projects->isNotEmpty())
        <x-public.section density="tight" tone="surface" width="wide" class="!pt-0">
            <ul class="flex flex-wrap items-center gap-x-8 gap-y-2 border-t border-neutral-200 pt-5 text-sm">
                @if ($areas->isNotEmpty())
                    <li class="flex items-center gap-2 text-neutral-600">
                        <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />
                        متاحة ضمن مناطق تغطيتنا في الرياض
                    </li>
                @endif
                @if ($projects->isNotEmpty())
                    <li class="flex items-center gap-2 text-neutral-600">
                        <x-public.icon name="briefcase" class="w-4 h-4 text-primary-600" />
                        مدعومة بمشاريع منفذة موثقة
                    </li>
                @endif
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 2-4. Editor content: scope, process, packages, FAQ =====
         Rendered by the shared block renderer so the CMS stays the single
         source of truth for this page's substance. --}}
    {{-- Everything except the FAQ, in the editor's own order. The FAQ is
         rendered by a second pass further down (step 9), because on a
         landing page the objection-handling belongs immediately before
         the decision - see the two-pass contract in blocks.blade.php. --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" :related="$related" related-item-type="service" />

    {{-- ===== 5. Real evidence for THIS service ===== --}}
    @if ($projects->isNotEmpty())
        <section class="bg-white border-y border-neutral-200">
            <x-public.container width="wide" class="py-16 md:py-24">
                <x-public.section-marker label="نتائج هذه الخدمة" class="mb-10" />

                <h2 class="font-display text-2xl leading-snug md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance max-w-2xl">
                    أعمال منفذة فعليًا، لا صور توضيحية
                </h2>

                @if ($projectShowcases->isNotEmpty())
                    <div @class([
                        'mt-10 grid gap-10 md:gap-12',
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
                                @if ($project->area?->name)
                                    <p class="mt-0.5 text-sm text-neutral-500">{{ $project->area->name }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 6. Active offers on this service (editorial list) ===== --}}
    @if ($offers->isNotEmpty())
        <x-public.section width="wide">
            <x-public.section-marker label="عروض على هذه الخدمة" heading class="mb-8" />
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

    {{-- ===== 7. Where it is available (typographic index) ===== --}}
    @if ($areas->isNotEmpty())
        <x-public.section tone="surface" width="wide">
            <x-public.section-marker label="مناطق التغطية" class="mb-8" />

            <h2 class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950">
                نقدّم هذه الخدمة في
            </h2>

            <ul class="mt-6 grid grid-cols-2 lg:grid-cols-3 gap-x-10">
                @foreach ($areas as $area)
                    <li class="border-b border-neutral-200">
                        <a href="{{ $urlResolver->urlForPage($area->page) }}"
                            class="flex items-baseline justify-between gap-3 py-3.5 text-ink-950 hover:text-primary-700 transition-colors">
                            <span>{{ $area->name }}</span>
                            <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 rtl:rotate-180 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 8. What customers of this service said ===== --}}
    @if ($leadTestimonial)
        <x-public.section width="wide">
            <x-public.section-marker label="آراء العملاء" heading class="mb-10" />

            <figure class="max-w-3xl">
                <blockquote class="font-display text-xl md:text-3xl font-light leading-[1.5] text-ink-950 text-balance">
                    {{ $leadTestimonial->content }}
                </blockquote>
                <figcaption class="mt-5 text-sm text-neutral-500">
                    {{ collect([$leadTestimonial->author_name, $leadTestimonial->area?->name])->filter()->implode(' · ') }}
                </figcaption>
            </figure>

            @if ($otherTestimonials->isNotEmpty())
                <ul class="mt-12 grid md:grid-cols-2 gap-x-12 border-t border-neutral-200">
                    @foreach ($otherTestimonials as $testimonial)
                        <li class="border-b border-neutral-200 py-5">
                            <p class="text-neutral-700 leading-relaxed">{{ $testimonial->content }}</p>
                            <p class="mt-2 text-sm text-neutral-500">
                                {{ collect([$testimonial->author_name, $testimonial->area?->name])->filter()->implode(' · ') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-public.section>
    @endif

    {{-- ===== 9. Related services - suppressed when the editor already
             placed a manual related_content block (Phase 4 rule). ===== --}}
    @if (! $hasManualRelatedBlock && $related->isNotEmpty())
        {{-- Default tone on purpose: the FAQ pass that follows renders on
             a white surface, and two white bands back to back would read
             as one undivided block. --}}
        <x-public.section width="wide">
            <x-public.section-marker label="خدمات ذات صلة" heading class="mb-8" />
            <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-10 max-w-5xl">
                @foreach ($related as $item)
                    <li class="border-b border-neutral-200">
                        <a href="{{ $urlResolver->urlForPage($item->page) }}"
                            class="flex items-center justify-between gap-3 py-3.5 text-ink-950 hover:text-primary-700 transition-colors">
                            {{ $item->name }}
                            <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 rtl:rotate-180 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 9. Remaining objections, right before the decision =====
         Second pass: the FAQ block only. blocks.blade.php still owns the
         heading and the markup, and still applies the Phase 4 rule that
         the FAQ appears only when an active faq block exists AND real
         questions are attached. --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" />

    {{-- ===== 10. Decision ===== --}}
    <section class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">
                    {{ 'هل تحتاج '.$service->name.'؟' }}
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
