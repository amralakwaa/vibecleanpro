@php
    use App\Enums\OfferAvailability;

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن عرض: '.$offer->title);
    $phoneUrl = $businessProfile?->phoneUrl();
    $availability = $offer->availability();
    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero :heading="$page->title" :image="$offer->featuredMedia" :breadcrumbs="$seo->breadcrumbs">
        <div class="mt-5 flex flex-wrap items-center gap-3">
            @if ($offer->discount_label)
                <x-public.badge tone="accent" class="!bg-ink-950 !text-white text-sm px-4 py-1.5">{{ $offer->discount_label }}</x-public.badge>
            @endif

            @if ($availability === OfferAvailability::Expired)
                <x-public.badge tone="neutral">انتهى العرض</x-public.badge>
            @elseif ($availability === OfferAvailability::Scheduled)
                <x-public.badge tone="neutral">
                    يبدأ في {{ $offer->starts_at->translatedFormat('j F Y') }}
                </x-public.badge>
            @elseif ($offer->ends_at)
                <span class="text-sm text-neutral-600">ساري حتى {{ $offer->ends_at->translatedFormat('j F Y') }}</span>
            @endif
        </div>
    </x-public.hero>

    @if ($offerServices->isNotEmpty() || $offerAreas->isNotEmpty())
        <x-public.section tone="surface" class="!py-8">
            <div class="flex flex-wrap gap-8">
                @if ($offerServices->isNotEmpty())
                    <div>
                        <p class="text-sm font-semibold text-neutral-500 mb-2">الخدمات المشمولة</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($offerServices as $service)
                                <a href="{{ $urlResolver->urlForPage($service->page) }}" class="inline-flex">
                                    <x-public.badge tone="primary">{{ $service->name }}</x-public.badge>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($offerAreas->isNotEmpty())
                    <div>
                        <p class="text-sm font-semibold text-neutral-500 mb-2">المناطق المشمولة</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($offerAreas as $area)
                                <a href="{{ $urlResolver->urlForPage($area->page) }}" class="inline-flex">
                                    <x-public.badge tone="neutral">{{ $area->name }}</x-public.badge>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-public.section>
    @endif

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$related" related-item-type="service" />

    <x-public.section>
        @if ($availability === OfferAvailability::Expired)
            <x-public.cta title="انتهى هذا العرض" description="تواصل معنا لمعرفة أحدث العروض المتاحة حاليًا."
                :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
        @else
            <x-public.cta title="استفد من العرض الآن" description="تواصل معنا قبل انتهاء العرض."
                :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
        @endif
    </x-public.section>
</x-layouts.public>
