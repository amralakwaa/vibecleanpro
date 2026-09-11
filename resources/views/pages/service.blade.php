{{--
    Service Page Blueprint (Phase 5/6 report):
    Hero -> Quick Facts + Request Quote -> editor content (explanation/
    benefits/process/packages/FAQ via <x-public.blocks>) -> areas served
    -> projects proof -> CTA. The structural sections (quick facts, areas,
    projects) are never something an editor composes via the block
    builder - they always reflect the Service's real relational data.

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
            <div class="flex flex-wrap items-center justify-center gap-8 text-center">
                @if ($service->category)
                    <div>
                        <p class="text-sm text-neutral-500">التصنيف</p>
                        <p class="font-semibold text-neutral-900">{{ $service->category->name }}</p>
                    </div>
                @endif
                @if ($areas->isNotEmpty())
                    <div>
                        <p class="text-sm text-neutral-500">مناطق متاحة</p>
                        <p class="font-semibold text-neutral-900">{{ $areas->count() }}</p>
                    </div>
                @endif
                @if ($projects->isNotEmpty())
                    <div>
                        <p class="text-sm text-neutral-500">مشاريع منفذة</p>
                        <p class="font-semibold text-neutral-900">{{ $projects->count() }}</p>
                    </div>
                @endif
            </div>
        </x-public.section>
    @endif

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$related" related-item-type="service" />

    @if ($areas->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header eyebrow="مناطق التغطية" title="نقدّم هذه الخدمة في المناطق التالية" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($areas as $area)
                    <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($projects->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="من أعمالنا" title="مشاريع منفذة لهذه الخدمة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($projects as $project)
                    <x-public.project-card :project="$project" :url="$urlResolver->urlForPage($project->page)"
                        :image="$project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first()"
                        :area-name="$project->area?->name" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    <x-public.section>
        <x-public.cta
            :title="'هل تحتاج '.$service->name.'؟'"
            description="اطلب عرض سعر مناسب الآن."
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
