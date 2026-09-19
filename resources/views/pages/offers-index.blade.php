{{--
    Offers Index - the commercial board, in the Homepage V2 language.

    The controller already decides what belongs here (Active and
    Scheduled only, Active first; Expired never listed) - this view only
    partitions that list by status so each state reads differently:

      - the first Active offer leads as the loud "event" surface the
        homepage introduced: blue gradient field, one panel, the offer's
        own number (and "بدلًا من" only when OfferPrice can prove it),
        the validity date, one action;
      - the remaining Active offers run as commercial rows on the same
        field, each with its covered service's photograph when one exists;
      - Scheduled offers sit lower and quieter on a tinted field, dated by
        their real start - no order action, nothing pretends to be live.

    No countdown, no cart, no marketplace grid, no fake scarcity: validity
    is a date, stated once per offer, and every picture is the covered
    service's own media (never an invented sale visual).
--}}
@php
    use App\Enums\OfferAvailability;
    use App\Support\Pricing\OfferPrice;
    use App\Support\Pricing\PublicPrice;

    $urlResolver = app(\App\Seo\UrlResolver::class);

    $active = $offers->filter(fn ($offer) => $offer->availability() === OfferAvailability::Active)->values();
    $scheduled = $offers->filter(fn ($offer) => $offer->availability() === OfferAvailability::Scheduled)->values();

    $lead = $active->first();
    $moreActive = $active->skip(1);

    $imageFor = fn ($offer) => $offer->featuredMedia ?? $offer->services->first()?->featuredMedia;
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== Index head - light, short, breadcrumbed ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-24 -end-24 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-8 pb-12 md:pt-12 md:pb-16">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">العروض</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">عروض حقيقية على خدماتنا، بشروط واضحة وتواريخ معلنة.</p>
        </x-public.container>
    </section>

    @if ($offers->isNotEmpty())
        {{-- ===== Active offers - the loud moment ===== --}}
        @if ($lead)
            @php
                $leadPrice = OfferPrice::forOffer($lead, $lead->services);
                $leadService = $lead->services->first();
                $leadImage = $imageFor($lead);
            @endphp
            <section class="surface-offer relative isolate overflow-hidden text-white" aria-labelledby="offers-active">
                <div class="glow-primary absolute top-1/3 -end-32 w-[30rem] h-[30rem] -z-10 opacity-80" aria-hidden="true"></div>

                <x-public.container width="wide" @class(['pt-10 md:pt-14', 'pb-24 md:pb-32' => $scheduled->isNotEmpty(), 'pb-16 md:pb-24' => $scheduled->isEmpty()])>
                    <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                        <div>
                            <p class="text-sm font-medium tracking-wide text-primary-100">العروض المتاحة</p>
                            <h2 id="offers-active" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">متاح الآن</h2>
                        </div>
                    </div>

                    <article class="mt-8 md:mt-10 rounded-3xl bg-white/[0.08] border border-white/15 backdrop-blur-sm overflow-hidden reveal">
                        <div @class(['grid items-stretch', 'lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]' => (bool) $leadImage])>
                            <div class="p-7 md:p-10 lg:p-12 flex flex-col justify-center">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($lead->discount_label)
                                        <span class="inline-flex items-center rounded-full bg-white text-primary-700 px-3.5 py-1.5 text-sm font-medium">{{ $lead->discount_label }}</span>
                                    @endif
                                    @if ($leadService)
                                        <span class="inline-flex items-center rounded-full border border-white/25 px-3.5 py-1.5 text-sm text-white/90">{{ $leadService->name }}</span>
                                    @endif
                                </div>
                                <h3 class="mt-5 font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-white text-balance">
                                    <a href="{{ $urlResolver->urlForPage($lead->page) }}" class="hover:underline underline-offset-4">{{ $lead->title }}</a>
                                </h3>
                                @if ($leadPrice)
                                    <p class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                        <span class="text-sm text-white/85">سعر العرض</span>
                                        <span class="font-display text-3xl md:text-4xl font-medium text-white tabular-nums">{{ $leadPrice->label() }}</span>
                                        @if ($leadPrice->beforeLabel())
                                            <span class="text-sm text-white/85">بدلًا من <s class="tabular-nums">{{ $leadPrice->beforeLabel() }}</s></span>
                                        @endif
                                    </p>
                                @endif
                                @if ($lead->ends_at)
                                    <p class="mt-3 text-sm text-white/85">ساري حتى <time datetime="{{ $lead->ends_at->toDateString() }}">{{ $lead->ends_at->translatedFormat('j F Y') }}</time></p>
                                @endif
                                <div class="mt-8 flex flex-wrap items-center gap-4">
                                    <x-public.button :href="$urlResolver->urlForPage($lead->page)" variant="cta" size="lg" icon-trailing="arrow-start" class="!bg-white !text-primary-700 hover:!bg-primary-50">تفاصيل العرض</x-public.button>
                                </div>
                            </div>
                            @if ($leadImage)
                                <div class="relative min-h-[16rem] lg:min-h-0 [mask-image:linear-gradient(to_bottom,transparent_0%,black_18%)] lg:[mask-image:linear-gradient(to_left,transparent_0%,black_24%)]">
                                    <img src="{{ $leadImage->url() }}" srcset="{{ $leadImage->srcset() }}" alt="{{ $leadImage->alt_text ?? $lead->title }}" fetchpriority="high"
                                        width="{{ $leadImage->width ?: 1200 }}" height="{{ $leadImage->height ?: 800 }}"
                                        class="absolute inset-0 w-full h-full object-cover">
                                </div>
                            @endif
                        </div>
                    </article>

                    {{-- ===== Remaining active offers - commercial rows ===== --}}
                    @if ($moreActive->isNotEmpty())
                        <h2 class="mt-10 text-sm font-medium tracking-wide text-primary-100 reveal">عروض أخرى متاحة</h2>
                        <ul class="mt-4 grid gap-4 md:grid-cols-2 reveal">
                            @foreach ($moreActive as $offer)
                                @php
                                    $price = OfferPrice::forOffer($offer, $offer->services);
                                    $image = $imageFor($offer);
                                @endphp
                                <li>
                                    <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex items-stretch gap-0 rounded-2xl border border-white/15 bg-white/[0.06] overflow-hidden min-h-24 transition-colors hover:bg-white/[0.12]">
                                        @if ($image)
                                            <span class="relative w-28 sm:w-36 shrink-0 overflow-hidden">
                                                <img src="{{ $image->url() }}" srcset="{{ $image->srcset() }}" alt="{{ $image->alt_text ?? $offer->title }}" loading="lazy" width="{{ $image->width ?: 800 }}" height="{{ $image->height ?: 600 }}" class="tile-media absolute inset-0 w-full h-full object-cover">
                                            </span>
                                        @endif
                                        <span class="flex min-w-0 grow items-center justify-between gap-4 px-5 py-4">
                                            <span class="min-w-0">
                                                <span class="block font-display text-lg font-medium text-white">{{ $offer->title }}</span>
                                                <span class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-0.5 text-sm text-white/85">
                                                    @if ($offer->discount_label)
                                                        <span>{{ $offer->discount_label }}</span>
                                                    @endif
                                                    @if ($price)
                                                        <span class="font-medium text-white tabular-nums">{{ $price->label() }}</span>
                                                    @endif
                                                    @if ($offer->ends_at)
                                                        <span>حتى <time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time></span>
                                                    @endif
                                                </span>
                                            </span>
                                            <x-public.icon name="arrow-start" class="w-4 h-4 text-white rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-public.container>

                @if ($scheduled->isNotEmpty())
                    <x-public.wave shape="curve" position="bottom" class="text-primary-50" />
                @endif
            </section>
        @endif

        {{-- ===== Scheduled offers - quieter, dated by their real start ===== --}}
        @if ($scheduled->isNotEmpty())
            <section class="surface-tint relative overflow-hidden" aria-labelledby="offers-scheduled">
                <x-public.container width="wide" class="py-14 md:py-20">
                    <div class="max-w-2xl reveal">
                        <p class="text-sm font-medium tracking-wide text-primary-700">قريبًا</p>
                        <h2 id="offers-scheduled" class="mt-2 font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">عروض قادمة بتواريخ معلنة</h2>
                    </div>
                    <ul class="mt-8 grid gap-4 md:grid-cols-2 reveal">
                        @foreach ($scheduled as $offer)
                            <li>
                                <a href="{{ $urlResolver->urlForPage($offer->page) }}" class="group flex items-center justify-between gap-4 rounded-2xl border border-dashed border-primary-300 bg-white/70 px-5 py-4 min-h-14 transition-colors hover:bg-white">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors">{{ $offer->title }}</span>
                                        <span class="block mt-0.5 text-sm text-neutral-600">يبدأ في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time></span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
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
