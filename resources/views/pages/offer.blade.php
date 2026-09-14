{{--
    Offer Detail - commercially clear, never a shop.

    The page answers, in order: what is the offer, is it available right
    now, what does it include, where, what are the actual terms, how do I
    take it. Everything shown is a real column or relation - there is no
    price model on Offer, so no price, no struck-through "was", no
    percentage badge repeated down the page; the editor's discount_label
    is the one value statement, shown once. No countdown, no scarcity, no
    "today only": validity is stated plainly as dates.

    Status (Offer::availability(), unchanged) drives the whole surface:
      Active    - blue conversion CTA + WhatsApp
      Scheduled - the real start date; no order CTA, only an inquiry link,
                  because presenting an order button for an offer that has
                  not started would be misleading
      Expired   - stays reachable (existing semantics), says so plainly,
                  and points to current offers instead of a CTA that
                  pretends this one is live

    Content blocks stay owned by blocks.blade.php via the only/except
    contract and render through the `.prose` reading system.
--}}
@php
    use App\Enums\OfferAvailability;

    $availability = $offer->availability();
    $isActive = $availability === OfferAvailability::Active;
    $isScheduled = $availability === OfferAvailability::Scheduled;
    $isExpired = $availability === OfferAvailability::Expired;

    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', array_filter(['service' => $offerServices->first()?->id]));
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن عرض: '.$offer->title);
    $offersIndexUrl = route('public.offers.index');
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Commercial hero - status first, one action ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <div @class(['grid gap-8 lg:gap-14 items-center', 'lg:grid-cols-[1.05fr_1fr]' => (bool) $offer->featuredMedia])>
                <div>
                    <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                    {{-- Status eyebrow: three honest states, three colours. --}}
                    <p @class([
                        'flex items-center gap-2.5 text-sm font-medium tracking-wide',
                        'text-success-700' => $isActive,
                        'text-primary-700' => $isScheduled,
                        'text-neutral-500' => $isExpired,
                    ])>
                        <span @class(['w-1.5 h-1.5 rounded-full', 'bg-success-500' => $isActive, 'bg-primary-600' => $isScheduled, 'bg-neutral-400' => $isExpired]) aria-hidden="true"></span>
                        @if ($isActive)
                            عرض متاح الآن
                        @elseif ($isScheduled)
                            يبدأ في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time>
                        @else
                            انتهى هذا العرض
                        @endif
                    </p>

                    <h1 class="mt-4 font-display text-[1.75rem] leading-tight md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                        {{ $page->title }}
                    </h1>

                    {{-- The one value statement, from the editor, shown once. --}}
                    @if ($offer->discount_label)
                        <p class="mt-4 font-display text-xl md:text-2xl font-light text-ink-950">{{ $offer->discount_label }}</p>
                    @endif

                    {{-- Validity, stated as dates - never a countdown. --}}
                    @if ($offer->ends_at || $offer->starts_at)
                        <dl class="mt-5 flex flex-wrap gap-x-8 gap-y-1.5 text-sm">
                            @if ($isExpired && $offer->ends_at)
                                <div class="flex items-baseline gap-2">
                                    <dt class="text-neutral-500">انتهى في</dt>
                                    <dd class="font-medium text-ink-950"><time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time></dd>
                                </div>
                            @elseif (! $isExpired && $offer->ends_at)
                                <div class="flex items-baseline gap-2">
                                    <dt class="text-neutral-500">ساري حتى</dt>
                                    <dd class="font-medium text-ink-950"><time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time></dd>
                                </div>
                            @endif
                        </dl>
                    @endif

                    <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3">
                        @if ($isActive)
                            <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب هذا العرض</x-public.button>
                            @if ($whatsappUrl)
                                <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                            @endif
                        @elseif ($isScheduled)
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 font-medium text-neutral-700 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                                    <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> استفسر عن العرض قبل بدايته
                                </a>
                            @endif
                        @else
                            <a href="{{ $offersIndexUrl }}" class="inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 hover:underline">
                                تصفح العروض الحالية
                                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                            </a>
                        @endif
                    </div>
                </div>

                @if ($offer->featuredMedia)
                    <div class="lg:order-last">
                        <img
                            src="{{ $offer->featuredMedia->url() }}"
                            alt="{{ $offer->featuredMedia->alt_text ?? $page->title }}"
                            fetchpriority="high"
                            width="900"
                            height="675"
                            class="w-full aspect-[3/2] lg:aspect-[4/3] object-cover"
                        >
                    </div>
                @endif
            </div>
        </x-public.container>
    </section>

    {{-- ===== 2. Scope - what the offer covers and where. Ruled lists
             in two columns, each a real relation, each a crawlable link.
             This is "what does it include", not "suggested products". ===== --}}
    @if ($offerServices->isNotEmpty() || $offerAreas->isNotEmpty())
        <x-public.section width="wide" density="tight">
            <div class="grid md:grid-cols-2 gap-x-12 gap-y-10">
                @if ($offerServices->isNotEmpty())
                    <div>
                        <h2 class="text-sm font-medium tracking-wide text-neutral-500">يشمل العرض</h2>
                        <ol class="mt-3">
                            @foreach ($offerServices as $service)
                                <li class="border-b border-neutral-200">
                                    <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                        class="flex items-baseline gap-4 py-3.5 text-ink-950 hover:text-primary-700 transition-colors">
                                        <span class="font-display text-sm text-primary-600 tabular-nums shrink-0">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="font-medium">{{ $service->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if ($offerAreas->isNotEmpty())
                    <div>
                        <h2 class="text-sm font-medium tracking-wide text-neutral-500">متاح في</h2>
                        <ul class="mt-3">
                            @foreach ($offerAreas as $area)
                                <li class="border-b border-neutral-200">
                                    <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                        class="flex items-center justify-between gap-3 py-3.5 text-ink-950 hover:text-primary-700 transition-colors">
                                        {{ $area->name }}
                                        <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 rtl:rotate-180 shrink-0" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </x-public.section>
    @endif

    {{-- ===== 3. Details and terms - editor content in the reading system ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" :related="$related" related-item-type="service" width="narrow" />

    {{-- ===== 4. FAQ ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

    {{-- ===== 5. Decision - shaped by status, never misleading ===== --}}
    @if ($isActive)
        <section class="bg-ink-950 text-white">
            <x-public.container width="wide" class="py-16 md:py-24">
                <div class="max-w-2xl">
                    <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">جاهز للاستفادة من العرض؟</h2>
                    @if ($offer->ends_at)
                        <p class="mt-4 text-ink-200 leading-relaxed">ساري حتى <time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->translatedFormat('j F Y') }}</time>. أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر.</p>
                    @else
                        <p class="mt-4 text-ink-200 leading-relaxed">أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر.</p>
                    @endif
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب هذا العرض</x-public.button>
                        @if ($whatsappUrl)
                            <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">تواصل عبر واتساب</x-public.button>
                        @endif
                    </div>
                </div>
            </x-public.container>
        </section>
    @elseif ($isScheduled)
        <x-public.container width="narrow" class="pb-16 md:pb-24">
            <div class="border-t border-b border-neutral-200 py-8">
                <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950">يبدأ هذا العرض في <time datetime="{{ $offer->starts_at->toDateString() }}">{{ $offer->starts_at->translatedFormat('j F Y') }}</time></h2>
                <p class="mt-2 text-neutral-600 leading-relaxed">يمكنك الاستفسار عنه الآن، ويصبح متاحًا للطلب مع بدايته.</p>
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                        class="mt-5 inline-flex items-center gap-2 font-medium text-neutral-700 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                        <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> استفسر عبر واتساب
                    </a>
                @endif
            </div>
        </x-public.container>
    @else
        <x-public.container width="narrow" class="pb-16 md:pb-24">
            <div class="border-t border-b border-neutral-200 py-8">
                <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950">انتهى هذا العرض</h2>
                <p class="mt-2 text-neutral-600 leading-relaxed">قد تجد عرضًا مناسبًا بين العروض المتاحة حاليًا.</p>
                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <x-public.button :href="$offersIndexUrl" variant="secondary" icon-trailing="arrow-start">العروض الحالية</x-public.button>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                            <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> أو راسلنا على واتساب
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    @endif
</x-layouts.public>
