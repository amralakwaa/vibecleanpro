{{--
    Shared template for Trust, Legal, and Landing pages (including a future
    Contact page - see Phase 5 report, item 19). Their content is
    freeform/blocks-driven rather than structured like Service/Area, so one
    clean template covers all three rather than three near-identical ones.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="narrow" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">{{ $page->title }}</h1>
    </x-public.section>

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" />

    @if ($whatsappUrl || $phoneUrl)
        <x-public.section width="narrow">
            <x-public.cta title="لديك استفسار؟" :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
        </x-public.section>
    @endif
</x-layouts.public>
