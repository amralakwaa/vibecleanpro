@php
    use App\Enums\OfferAvailability;

    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="wide" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">العروض</h1>
        <p class="mt-3 text-neutral-600 max-w-2xl">العروض المتاحة حاليًا.</p>
    </x-public.section>

    <x-public.section width="wide" class="!pt-0">
        @if ($offers->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($offers as $offer)
                    <x-public.card :padded="false" class="relative overflow-hidden flex flex-col h-full">
                        <div class="aspect-[16/9] bg-neutral-100 overflow-hidden">
                            @if ($offer->featuredMedia)
                                <img src="{{ $offer->featuredMedia->url() }}" alt="{{ $offer->featuredMedia->alt_text ?? '' }}"
                                    loading="lazy" class="w-full h-full object-cover" width="480" height="270">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-primary-300">
                                    <x-public.icon name="sparkles" class="w-9 h-9" />
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            @if ($offer->availability() === OfferAvailability::Scheduled)
                                <x-public.badge tone="neutral" class="mb-2">قريبًا</x-public.badge>
                            @endif
                            @if ($offer->discount_label)
                                <x-public.badge tone="accent" class="mb-2">{{ $offer->discount_label }}</x-public.badge>
                            @endif
                            <h3 class="font-semibold text-neutral-900">
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="hover:text-primary-700 transition-colors">
                                    <span class="absolute inset-0"></span>
                                    {{ $offer->title }}
                                </a>
                            </h3>
                        </div>
                    </x-public.card>
                @endforeach
            </div>
        @else
            <x-public.empty-state icon="sparkles" title="لا توجد عروض متاحة حاليًا"
                description="تابعنا لمعرفة العروض القادمة." />
        @endif
    </x-public.section>
</x-layouts.public>
