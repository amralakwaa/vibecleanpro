{{--
    Service Page Blueprint (Phase 5 report, item 20):
    Hero -> editor content (explanation/benefits/process/FAQ via
    <x-public.blocks>) -> areas served -> projects proof -> CTA.
    The last three structural sections are not something an editor
    composes via the block builder - they always reflect the Service's
    real relational data.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero
        :heading="$page->title"
        :subheading="$service->short_description"
        :image="$service->featuredMedia"
        :cta-label="$whatsappUrl ? 'اطلب الخدمة عبر واتساب' : null"
        :cta-url="$whatsappUrl"
        :breadcrumbs="$seo->breadcrumbs"
    />

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
            description="تواصل معنا الآن واحصل على عرض سعر مناسب."
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
