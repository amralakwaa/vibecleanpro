@php
    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero :heading="$page->title" :subheading="$article->excerpt" :image="$article->featuredMedia" :breadcrumbs="$seo->breadcrumbs">
        <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-primary-100">
            @if ($article->category)
                <x-public.badge tone="accent">{{ $article->category->name }}</x-public.badge>
            @endif
            @if ($page->published_at)
                <span class="inline-flex items-center gap-1.5"><x-public.icon name="clock" class="w-4 h-4" /> {{ $page->published_at->translatedFormat('j F Y') }}</span>
            @endif
            @if ($article->author)
                <span>{{ $article->author->name }}</span>
            @endif
        </div>
    </x-public.hero>

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$related" related-item-type="service" />

    <x-public.section>
        <x-public.cta title="هل تحتاج مساعدة في هذا الأمر؟" description="فريقنا جاهز لمساعدتك." :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
    </x-public.section>
</x-layouts.public>
