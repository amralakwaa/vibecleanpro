@php
    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
    $isExpired = $offer->ends_at && $offer->ends_at->isPast();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero :heading="$page->title" :image="$offer->featuredMedia" :breadcrumbs="$seo->breadcrumbs">
        <div class="mt-5 flex flex-wrap items-center gap-3">
            @if ($offer->discount_label)
                <x-public.badge tone="accent" class="!bg-accent-500 !text-white text-sm px-4 py-1.5">{{ $offer->discount_label }}</x-public.badge>
            @endif
            @if ($isExpired)
                <x-public.badge tone="neutral" class="!bg-white/10 !text-white">العرض غير متاح حاليًا</x-public.badge>
            @elseif ($offer->ends_at)
                <span class="text-sm text-primary-100">ساري حتى {{ $offer->ends_at->translatedFormat('j F Y') }}</span>
            @endif
        </div>
    </x-public.hero>

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$related" related-item-type="service" />

    <x-public.section>
        <x-public.cta
            :title="$isExpired ? 'اطلب أحدث عروضنا' : 'استفد من العرض الآن'"
            description="تواصل معنا قبل انتهاء العرض."
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
