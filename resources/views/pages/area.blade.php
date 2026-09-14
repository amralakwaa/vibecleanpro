{{--
    Area Page Blueprint (Phase 5 redesign): a real Local Landing Page, not a
    doorway - the light Deep Petrol Ink hero (no invented image; Area has no
    featured_media_id of its own, see the Area model) carries the page's own
    CMS title verbatim as H1, then editor-authored Local Content Blocks are
    the only source of unique local copy - this template never generates a
    "نقدم خدمات تنظيف في {name}" style sentence itself. Everything below
    that (services/projects/offers/testimonials/nearby areas/articles) is
    the Area's real relational data, and every one of those sections hides
    itself completely when empty rather than showing an empty state - a
    published Local Landing Page should never look half-finished to a
    visitor who found it from a local search.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في طلب خدمة في '.$area->name);
    $phoneUrl = $businessProfile?->phoneUrl();
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', ['area' => $area->id]);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero eyebrow="منطقة تغطية" :heading="$page->title" :breadcrumbs="$seo->breadcrumbs">
        <div class="mt-7 flex flex-col sm:flex-row items-center gap-3">
            <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>
            @if ($whatsappUrl)
                <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">
                    واتساب
                </x-public.button>
            @endif
        </div>
    </x-public.hero>

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$services" related-item-type="service" />

    @if ($services->isNotEmpty())
        <x-public.section id="services" tone="surface">
            <x-public.section-header eyebrow="متاح في هذه المنطقة" title="الخدمات المتاحة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    <x-public.service-card :service="$service" :url="$urlResolver->urlForPage($service->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($projects->isNotEmpty())
        @php
            $projectShowcases = $projects->filter(
                fn ($project) => $project->media->firstWhere('pivot.stage', 'before') && $project->media->firstWhere('pivot.stage', 'after')
            );
            $projectCards = $projects->reject(fn ($project) => $projectShowcases->contains($project));
        @endphp
        <x-public.section>
            <x-public.section-header eyebrow="من أعمالنا" title="نتائج حقيقية في هذه المنطقة" align="center" class="mb-10" />

            @if ($projectShowcases->isNotEmpty())
                <div @class(['grid sm:grid-cols-2 lg:grid-cols-3 gap-6', 'mb-6' => $projectCards->isNotEmpty()])>
                    @foreach ($projectShowcases as $project)
                        <x-public.project-showcase :project="$project" :url="$urlResolver->urlForPage($project->page)"
                            :before="$project->media->firstWhere('pivot.stage', 'before')"
                            :after="$project->media->firstWhere('pivot.stage', 'after')" />
                    @endforeach
                </div>
            @endif

            @if ($projectCards->isNotEmpty())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($projectCards as $project)
                        <x-public.project-card :project="$project" :url="$urlResolver->urlForPage($project->page)"
                            :image="$project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first()" />
                    @endforeach
                </div>
            @endif
        </x-public.section>
    @endif

    @if ($offers->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header eyebrow="عرض خاص" title="عروض حالية في هذه المنطقة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($offers as $offer)
                    <x-public.offer-card :offer="$offer" :url="$urlResolver->urlForPage($offer->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($testimonials->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="آراء العملاء" title="ماذا يقول عملاؤنا في هذه المنطقة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($testimonials as $testimonial)
                    <x-public.testimonial-card :testimonial="$testimonial" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($nearbyAreas->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header title="مناطق قريبة" align="center" class="mb-8" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($nearbyAreas as $nearby)
                    <x-public.area-card :area="$nearby" :url="$urlResolver->urlForPage($nearby->page)" :services-count="$nearby->services_count" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($articles->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="مقالات مفيدة" title="مقالات مرتبطة بهذه المنطقة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($articles as $article)
                    <x-public.article-card :article="$article" :url="$urlResolver->urlForPage($article->page)" :category-name="$article->category?->name" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    <x-public.section>
        <x-public.cta
            :title="'خدمات تنظيف في '.$area->name"
            description="تواصل معنا الآن لحجز موعد في منطقتك."
            :quote-url="$quoteUrl"
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
