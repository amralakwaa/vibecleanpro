{{--
    Service Page Blueprint (Phase 4 redesign):
    Hero -> Quick Trust strip -> editor content (explanation/benefits/
    process/packages/FAQ via <x-public.blocks>) -> real proof (projects,
    before/after first) -> active offers -> areas served -> testimonials
    -> related services (only when the editor hasn't already placed a
    manual related_content block - see $hasManualRelatedBlock) -> CTA.
    These structural sections are never something an editor composes via
    the block builder - they always reflect the Service's real relational
    data (Areas/Projects/Offers/Testimonials), and every one of them hides
    itself completely when that data is empty rather than showing an
    empty state.

    No "starting price" is ever shown here: Service has no price field of
    its own (see item 23/6 of the Phase 6 spec) - only a content-block
    "packages" section, when an editor actually adds one, ever shows a
    real price. The hero CTA is always "Request Quote".
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن خدمة '.$service->name);
    $phoneUrl = $businessProfile?->phoneUrl();
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', ['service' => $service->id]);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero
        :heading="$page->title"
        :subheading="$service->short_description"
        :image="$service->featuredMedia"
        :breadcrumbs="$seo->breadcrumbs"
    >
        <div class="mt-7 flex flex-col sm:flex-row items-center gap-3">
            <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>
            @if ($whatsappUrl)
                <x-public.button :href="$whatsappUrl" external variant="secondary" size="lg" icon="whatsapp"
                    class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">
                    واتساب
                </x-public.button>
            @endif
        </div>
    </x-public.hero>

    @if ($service->category || $areas->isNotEmpty() || $projects->isNotEmpty())
        <x-public.section tone="surface" class="!py-8">
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if ($service->category)
                    <x-public.badge tone="primary">{{ $service->category->name }}</x-public.badge>
                @endif
                @if ($areas->isNotEmpty())
                    <x-public.badge tone="neutral">
                        <x-public.icon name="map-pin" class="w-3.5 h-3.5" />
                        نقدّم هذه الخدمة في منطقتك
                    </x-public.badge>
                @endif
                @if ($projects->isNotEmpty())
                    <x-public.badge tone="accent">
                        <x-public.icon name="briefcase" class="w-3.5 h-3.5" />
                        نتائج حقيقية من مشاريع منفذة
                    </x-public.badge>
                @endif
            </div>
        </x-public.section>
    @endif

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$related" related-item-type="service" />

    @if ($projects->isNotEmpty())
        @php
            $projectShowcases = $projects->filter(
                fn ($project) => $project->media->firstWhere('pivot.stage', 'before') && $project->media->firstWhere('pivot.stage', 'after')
            );
            $projectCards = $projects->reject(fn ($project) => $projectShowcases->contains($project));
        @endphp
        <x-public.section>
            <x-public.section-header eyebrow="من أعمالنا" title="نتائج حقيقية لهذه الخدمة" align="center" class="mb-10" />

            @if ($projectShowcases->isNotEmpty())
                <div @class(['grid sm:grid-cols-2 lg:grid-cols-3 gap-6', 'mb-6' => $projectCards->isNotEmpty()])>
                    @foreach ($projectShowcases as $project)
                        <x-public.project-showcase :project="$project" :url="$urlResolver->urlForPage($project->page)"
                            :before="$project->media->firstWhere('pivot.stage', 'before')"
                            :after="$project->media->firstWhere('pivot.stage', 'after')"
                            :area-name="$project->area?->name" />
                    @endforeach
                </div>
            @endif

            @if ($projectCards->isNotEmpty())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($projectCards as $project)
                        <x-public.project-card :project="$project" :url="$urlResolver->urlForPage($project->page)"
                            :image="$project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first()"
                            :area-name="$project->area?->name" />
                    @endforeach
                </div>
            @endif
        </x-public.section>
    @endif

    @if ($offers->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header eyebrow="عرض خاص" title="عروض حالية على هذه الخدمة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($offers as $offer)
                    <x-public.offer-card :offer="$offer" :url="$urlResolver->urlForPage($offer->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($areas->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="مناطق التغطية" title="نقدّم هذه الخدمة في المناطق التالية" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($areas as $area)
                    <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($testimonials->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header eyebrow="آراء العملاء" title="ماذا يقول عملاؤنا عن هذه الخدمة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($testimonials as $testimonial)
                    <x-public.testimonial-card :testimonial="$testimonial" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if (! $hasManualRelatedBlock && $related->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="قد يهمك أيضًا" title="خدمات ذات صلة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($related as $item)
                    <x-public.service-card :service="$item" :url="$urlResolver->urlForPage($item->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    <x-public.section>
        <x-public.cta
            :title="'هل تحتاج '.$service->name.'؟'"
            description="اطلب عرض سعر مناسب الآن."
            :quote-url="$quoteUrl"
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
