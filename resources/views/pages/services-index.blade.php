{{--
    Services Index - Service Discovery, in the V2 system.

    Answers "which service is right for me?" as a discovery page, not a
    card grid and not a menu: a tinted head with the compact in-page
    index (so the whole offer can be scanned in one glance on a phone),
    the featured service as one large photo tile in the homepage's tile
    language, then image-led rows grouped under the real ServiceCategory
    when the listed services carry one - each row a rounded photograph
    beside the name, the short description, the public price and an
    arrow. Grouping is presentation only - categories have no route, so
    the heading is a heading and never a link. Ordering, pagination and
    the published filter are the controller's and are untouched.

    Prices come from PublicPrice only (nothing renders for quote-only or
    hidden prices); "خدمة مميزة" is the editor's is_featured toggle and
    is stated once, never dressed up as popularity.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أحتاج مساعدة في اختيار خدمة التنظيف المناسبة');
    $phoneUrl = $businessProfile?->phoneUrl();
    $quoteUrl = route('public.quote');

    $listed = $services->getCollection();
    $hasCategories = $listed->contains(fn ($service) => $service->category !== null);

    // The featured service leads as a large tile only on page one and only
    // when the editor marked it; everything else runs as rows.
    $lead = $services->onFirstPage() && $listed->first()?->is_featured ? $listed->first() : null;
    $rows = $lead ? $listed->skip(1) : $listed;

    // Categories in their own sort_order; services keep the controller's
    // order inside each. Uncategorised services close the list under a
    // neutral heading rather than being silently dropped.
    $sections = $hasCategories
        ? $rows->groupBy(fn ($service) => $service->category?->id ?? 0)
            ->map(fn ($group, $key) => ['name' => $key === 0 ? 'خدمات أخرى' : $group->first()->category->name, 'sort' => $key === 0 ? PHP_INT_MAX : $group->first()->category->sort_order, 'services' => $group])
            ->sortBy('sort')
            ->values()
        : collect([['name' => null, 'sort' => 0, 'services' => $rows]]);

    $showIndex = $listed->count() >= 4;
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Head + compact index on the tinted field ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-24 -end-24 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-8 pb-12 md:pt-12 md:pb-16">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-16 items-end">
                <div>
                    <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">خدماتنا</h1>
                    <p class="mt-4 text-lg text-neutral-600 max-w-xl leading-relaxed">اختر ما يناسب مساحتك واحتياجك. كل خدمة لها صفحة تشرح ما تشمله وكيف ننفذها.</p>
                </div>

                @if ($showIndex)
                    <nav aria-label="فهرس الخدمات">
                        <p class="text-sm font-medium tracking-wide text-neutral-500">في هذه الصفحة</p>
                        <ol class="mt-3 flex flex-wrap gap-2">
                            @foreach ($listed as $service)
                                <li>
                                    <a href="#service-{{ $service->id }}" class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-4 text-sm text-ink-950 backdrop-blur-sm transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                        <span class="font-display text-xs text-primary-600 tabular-nums">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span>{{ $service->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif
            </div>
        </x-public.container>
    </section>

    @if ($listed->isNotEmpty())
        {{-- ===== 2. The featured service as one large photo tile ===== --}}
        @if ($lead)
            <section class="bg-white" aria-labelledby="services-lead">
                <x-public.container width="wide" class="py-10 md:py-14">
                    <p class="text-xs font-medium tracking-wide text-primary-700 reveal">خدمة مميزة</p>
                    <a id="service-{{ $lead->id }}" href="{{ $urlResolver->urlForPage($lead->page) }}"
                        class="group relative isolate mt-3 flex flex-col justify-end overflow-hidden rounded-3xl text-white min-h-[22rem] md:min-h-[26rem] scroll-mt-24 shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300 reveal">
                        @if ($lead->featuredMedia)
                            <img src="{{ $lead->featuredMedia->url() }}" alt="{{ $lead->featuredMedia->alt_text ?? $lead->name }}"
                                width="{{ $lead->featuredMedia->width ?: 1600 }}" height="{{ $lead->featuredMedia->height ?: 900 }}" fetchpriority="high"
                                class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                        @else
                            <div class="surface-atmos absolute inset-0 -z-20" aria-hidden="true"></div>
                        @endif
                        <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                        <div class="p-6 md:p-10 max-w-2xl">
                            @if ($lead->category)
                                <p class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium tracking-wide text-white backdrop-blur-sm">{{ $lead->category->name }}</p>
                            @endif
                            <h2 id="services-lead" class="mt-3 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">{{ $lead->name }}</h2>
                            @if ($lead->short_description)
                                <p class="mt-3 text-white/85 leading-relaxed max-w-xl line-clamp-2">{{ $lead->short_description }}</p>
                            @endif
                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                @if ($price = $lead->publicPrice())
                                    <span class="inline-flex items-center rounded-full bg-white/15 px-3.5 py-1.5 text-sm font-medium text-white backdrop-blur-sm tabular-nums">{{ $price->label() }}</span>
                                @endif
                                <span class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-white">
                                    تفاصيل الخدمة
                                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                                </span>
                            </div>
                        </div>
                    </a>
                </x-public.container>
            </section>
        @endif

        {{-- ===== 3. Image-led rows, grouped by real category ===== --}}
        @foreach ($sections as $section)
            @if ($section['services']->isNotEmpty())
                <section @class(['bg-white', 'border-t border-neutral-200' => ! $loop->first || $lead]) aria-labelledby="{{ $section['name'] ? 'services-cat-'.$loop->index : 'services-all' }}">
                    <x-public.container width="wide" class="py-10 md:py-14">
                        @if ($section['name'])
                            <h2 id="services-cat-{{ $loop->index }}" class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950 reveal">{{ $section['name'] }}</h2>
                        @else
                            <h2 id="services-all" class="sr-only">الخدمات</h2>
                        @endif

                        <ol @class(['grid gap-4 md:gap-5 md:grid-cols-2 reveal', 'mt-6' => (bool) $section['name']])>
                            @foreach ($section['services'] as $service)
                                <li id="service-{{ $service->id }}" class="scroll-mt-24">
                                    <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                        class="group flex items-stretch h-full overflow-hidden rounded-2xl bg-white ring-1 ring-ink-950/5 shadow-sm transition-[box-shadow,ring-color] hover:shadow-md hover:ring-primary-200">
                                        @if ($service->featuredMedia)
                                            <span class="relative w-32 sm:w-44 lg:w-52 shrink-0 overflow-hidden">
                                                <img src="{{ $service->featuredMedia->url() }}" alt="{{ $service->featuredMedia->alt_text ?? $service->name }}" loading="lazy"
                                                    width="{{ $service->featuredMedia->width ?: 800 }}" height="{{ $service->featuredMedia->height ?: 600 }}"
                                                    class="tile-media absolute inset-0 w-full h-full object-cover">
                                            </span>
                                        @else
                                            <span class="surface-offer w-32 sm:w-44 lg:w-52 shrink-0 flex items-end p-3" aria-hidden="true">
                                                <span class="font-display text-lg font-medium leading-tight text-white/30">{{ $service->name }}</span>
                                            </span>
                                        @endif
                                        <span class="flex min-w-0 grow flex-col justify-center gap-1.5 px-5 py-4 md:px-6 md:py-5">
                                            @if ($service->is_featured)
                                                <span class="text-xs font-medium tracking-wide text-primary-700">خدمة مميزة</span>
                                            @endif
                                            <span class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors">{{ $service->name }}</span>
                                            @if ($service->short_description)
                                                <span class="text-sm text-neutral-600 leading-relaxed line-clamp-2">{{ $service->short_description }}</span>
                                            @endif
                                            {{-- PublicPrice or nothing in the price slot; the
                                                 row's own link text carries a quote-only service. --}}
                                            <span class="mt-1.5 flex items-center justify-between gap-3">
                                                @if ($price = $service->publicPrice())
                                                    <span class="text-sm font-medium text-ink-950 tabular-nums">{{ $price->label() }}</span>
                                                @else
                                                    <span class="text-sm font-medium text-primary-700">تفاصيل الخدمة</span>
                                                @endif
                                                <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>

                        @if ($loop->last)
                            <x-public.pagination :paginator="$services" />
                        @endif
                    </x-public.container>
                </section>
            @endif
        @endforeach

        @if ($rows->isEmpty() && $services->hasPages())
            <x-public.container width="wide" class="pb-12">
                <x-public.pagination :paginator="$services" />
            </x-public.container>
        @endif
    @else
        {{-- Customer-facing, never "nothing published". --}}
        <section class="bg-white">
            <x-public.container width="narrow" class="py-16 md:py-24">
                <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-8 md:p-10 reveal">
                    <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                    <h2 class="relative font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950">خدماتنا في طريقها إلى هذه الصفحة</h2>
                    <p class="relative mt-3 text-neutral-600 leading-relaxed">أخبرنا بما تحتاجه الآن وسنرد عليك بما يمكننا تنفيذه.</p>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 4. Decision - for the visitor still unsure which one ===== --}}
    <section class="surface-atmos relative isolate overflow-hidden text-white">
        <x-public.wave shape="soft" position="top" class="text-white" />
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-28 pb-16 md:pt-36 md:pb-24">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                <div>
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">لست متأكدًا أي خدمة تناسبك؟</h2>
                    <p class="mt-4 text-lg text-white/85 leading-relaxed max-w-xl">صف لنا المكان وحالته وسنقترح عليك الخدمة المناسبة مع عرض سعر. لا يتم الدفع عبر الموقع.</p>
                </div>
                <div class="flex flex-col items-start gap-3 lg:items-stretch">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40 lg:justify-center">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp" class="lg:justify-center">تواصل عبر واتساب</x-public.button>
                    @endif
                    @if ($phoneUrl)
                        <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-white/80 hover:text-white transition-colors lg:justify-center">
                            <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
