{{--
    Offers Index - an editorial offers board, not a product grid.

    The controller already decides what belongs here (Active and
    Scheduled only, Active first; Expired never listed) - this view only
    partitions that list by status so each state reads differently:
    the first Active offer leads as a spread, the remaining Active ones
    run as ruled rows, and Scheduled offers sit lower and quieter under
    their real start dates. No tiles, no repeated percentage badges, no
    countdown - validity is a date, stated once per offer.
--}}
@php
    use App\Enums\OfferAvailability;

    $urlResolver = app(\App\Seo\UrlResolver::class);

    $active = $offers->filter(fn ($offer) => $offer->availability() === OfferAvailability::Active)->values();
    $scheduled = $offers->filter(fn ($offer) => $offer->availability() === OfferAvailability::Scheduled)->values();

    $lead = $active->first();
    $moreActive = $active->skip(1);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">العروض</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">عروض حقيقية على خدماتنا، بشروط واضحة وتواريخ معلنة.</p>
        </x-public.container>
    </section>

    @if ($offers->isNotEmpty())
        {{-- ===== Lead offer ===== --}}
        @if ($lead)
            <x-public.container width="wide" class="py-12 md:py-16">
                <x-public.section-marker label="متاح الآن" class="mb-8" />
                <a href="{{ $urlResolver->urlForPage($lead->page) }}" @class(['group grid gap-8 lg:gap-12 items-center', 'lg:grid-cols-[1.3fr_1fr]' => (bool) $lead->featuredMedia])>
                    @if ($lead->featuredMedia)
                        <img src="{{ $lead->featuredMedia->url() }}" alt="{{ $lead->featuredMedia->alt_text ?? $lead->title }}"
                            width="1200" height="800" fetchpriority="high"
                            class="w-full aspect-[3/2] object-cover">
                    @endif
                    <div>
                        <h2 class="font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors">
                            {{ $lead->title }}
                        </h2>
                        @if ($lead->discount_label)
                            <p class="mt-3 font-display text-lg md:text-xl font-light text-ink-950">{{ $lead->discount_label }}</p>
                        @endif
                        @if ($lead->offer_price !== null)
                            <p class="mt-3 text-sm text-neutral-500">سعر العرض <span class="font-display text-lg font-medium text-ink-950 tabular-nums">{{ \App\Support\Pricing\PublicPrice::format((float) $lead->offer_price) }}</span></p>
                        @endif
                        @if ($lead->ends_at)
                            <p class="mt-4 text-sm text-neutral-500">ساري حتى <time datetime="{{ $lead->ends_at->toDateString() }}">{{ $lead->ends_at->translatedFormat('j F Y') }}</time></p>
                        @endif
                        <span class="mt-6 inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 group-hover:underline">
                            تفاصيل العرض
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </span>
                    </div>
                </a>
            </x-public.container>
        @endif

        {{-- ===== Remaining active offers as ruled rows ===== --}}
        @if ($moreActive->isNotEmpty())
            <section class="border-t border-neutral-200">
                <x-public.container width="wide" class="py-12 md:py-16">
                    <x-public.section-marker label="عروض أخرى متاحة" heading class="mb-4" />
                    <ul>
                        @foreach ($moreActive as $offer)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex flex-wrap items-baseline gap-x-6 gap-y-1 py-5">
                                    <span class="font-display text-lg md:text-xl font-medium text-ink-950 group-hover:text-primary-700 transition-colors">{{ $offer->title }}</span>
                                    @if ($offer->discount_label)
                                        <span class="text-sm text-primary-700">{{ $offer->discount_label }}</span>
                                    @endif
                                    @if ($offer->offer_price !== null)
                                        <span class="text-sm font-medium text-ink-950 tabular-nums">{{ \App\Support\Pricing\PublicPrice::format((float) $offer->offer_price) }}</span>
                                    @endif
                                    @if ($offer->ends_at)
                                        <span class="text-sm text-neutral-500">حتى <time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-public.container>
            </section>
        @endif

        {{-- ===== Scheduled offers - quieter, dated by their real start ===== --}}
        @if ($scheduled->isNotEmpty())
            <section class="bg-white border-t border-neutral-200">
                <x-public.container width="wide" class="py-12 md:py-16">
                    <x-public.section-marker label="قريبًا" heading class="mb-4" />
                    <ul>
                        @foreach ($scheduled as $offer)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex flex-wrap items-baseline gap-x-6 gap-y-1 py-5">
                                    <span class="font-display text-lg font-medium text-neutral-700 group-hover:text-primary-700 transition-colors">{{ $offer->title }}</span>
                                    <span class="text-sm text-neutral-500">يبدأ في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-public.container>
            </section>
        @endif
    @else
        <x-public.container width="wide" class="py-16">
            <p class="text-neutral-600">لا توجد عروض متاحة حاليًا.</p>
        </x-public.container>
    @endif
</x-layouts.public>
