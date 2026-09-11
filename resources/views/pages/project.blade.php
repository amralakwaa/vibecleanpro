@php
    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
    $afterImage = $project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first();
    $beforeImages = $project->media->where('pivot.stage', 'before');
    $afterImages = $project->media->where('pivot.stage', 'after');
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero :heading="$page->title" :image="$afterImage" :breadcrumbs="$seo->breadcrumbs">
        <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-primary-100">
            @if ($project->area)
                <span class="inline-flex items-center gap-1.5"><x-public.icon name="map-pin" class="w-4 h-4" /> {{ $project->area->name }}</span>
            @endif
            @if ($project->completed_at)
                <span class="inline-flex items-center gap-1.5"><x-public.icon name="clock" class="w-4 h-4" /> {{ $project->completed_at->translatedFormat('F Y') }}</span>
            @endif
        </div>
    </x-public.hero>

    @if ($beforeImages->isNotEmpty() && $afterImages->isNotEmpty())
        <x-public.section>
            <x-public.section-header title="قبل وبعد" align="center" class="mb-8" />
            <x-public.before-after :before="$beforeImages->first()" :after="$afterImages->first()" />
        </x-public.section>
    @endif

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" />

    @if ($project->summary)
        <x-public.section tone="surface">
            <p class="text-neutral-700 leading-relaxed max-w-2xl mx-auto text-center">{{ $project->summary }}</p>
        </x-public.section>
    @endif

    @if ($project->services->isNotEmpty())
        <x-public.section>
            <x-public.section-header title="الخدمات المستخدمة في هذا المشروع" align="center" class="mb-8" />
            <div class="flex flex-wrap justify-center gap-3">
                @foreach ($project->services as $service)
                    <x-public.button :href="app(\App\Seo\UrlResolver::class)->urlForPage($service->page)" variant="secondary" size="sm">
                        {{ $service->name }}
                    </x-public.button>
                @endforeach
            </div>
        </x-public.section>
    @endif

    <x-public.section>
        <x-public.cta title="أعجبتك النتيجة؟" description="احجز خدمة مشابهة الآن." :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
    </x-public.section>
</x-layouts.public>
