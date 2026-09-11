{{--
    Area Page Blueprint (Phase 5 report, item 21) - deliberately NOT a
    reskin of the Service template: no hero photo (areas rarely have one
    worth showing), local relevance first, then services available,
    projects proof, nearby areas, local FAQ, CTA.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <div class="bg-primary-900 text-white">
        <x-public.container width="wide" class="py-12 md:py-16">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="[&_a]:text-primary-200 [&_a:hover]:text-white [&_span]:text-white mb-5" />
            <span class="inline-flex items-center gap-2 text-primary-200 text-sm font-medium mb-3">
                <x-public.icon name="map-pin" class="w-4 h-4" /> منطقة تغطية
            </span>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">{{ $page->title }}</h1>
        </x-public.container>
    </div>

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
    @else
        <x-public.section tone="surface">
            <x-public.empty-state icon="sparkles" title="لا توجد خدمات مرتبطة بهذه المنطقة بعد" />
        </x-public.section>
    @endif

    @if ($projects->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="من أعمالنا" title="مشاريع منفذة في هذه المنطقة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($projects as $project)
                    <x-public.project-card :project="$project" :url="$urlResolver->urlForPage($project->page)"
                        :image="$project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first()" />
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

    <x-public.section>
        <x-public.cta
            :title="'خدمات تنظيف في '.$area->name"
            description="تواصل معنا الآن لحجز موعد في منطقتك."
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
