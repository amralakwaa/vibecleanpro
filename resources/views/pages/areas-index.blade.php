@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في معرفة تغطيتكم لمنطقتي');
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="wide" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">مناطق التغطية</h1>
        <p class="mt-3 text-neutral-600 max-w-2xl">المناطق التي نقدّم فيها خدماتنا حاليًا في الرياض.</p>
    </x-public.section>

    <x-public.section width="wide" class="!pt-0">
        @if ($totalAreas > 0)
            <div class="space-y-12">
                @foreach ($groups as $entry)
                    @continue($entry['areas']->isEmpty())
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 mb-4">{{ $entry['group']->name }}</h2>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($entry['areas'] as $area)
                                <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" :services-count="$area->services_count" />
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if ($ungrouped->isNotEmpty())
                    <div>
                        @if ($groups->isNotEmpty())
                            <h2 class="text-lg font-semibold text-neutral-900 mb-4">مناطق أخرى</h2>
                        @endif
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($ungrouped as $area)
                                <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" :services-count="$area->services_count" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <x-public.empty-state icon="map-pin" title="لا توجد مناطق منشورة حاليًا"
                description="تواصل معنا لمعرفة إن كنا نغطي منطقتك." />
        @endif
    </x-public.section>

    @if ($whatsappUrl || $phoneUrl)
        <x-public.section>
            <x-public.cta title="منطقتك غير مذكورة؟" description="تواصل معنا للتأكد من تغطيتنا لمنطقتك."
                :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
        </x-public.section>
    @endif
</x-layouts.public>
