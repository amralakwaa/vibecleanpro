{{--
    Services Index - answers "which service is right for me?".

    Not a card grid and not a menu: a short in-page index up top so the
    whole offer can be scanned in one glance on a phone, then editorial
    rows (small image beside a numbered entry) grouped under the real
    ServiceCategory when the listed services actually carry one. Grouping
    is presentation only - categories have no route, so the heading is a
    heading and never a link. Ordering, pagination and the published
    filter are the controller's and are untouched.

    Rows deliberately differ from the homepage's service rows (large
    mirrored spreads) and from the Area page's ruled list: this page has
    to compare seven-ish services, so each row stays compact enough that
    two or three are visible together.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أحتاج مساعدة في اختيار خدمة التنظيف المناسبة');
    $phoneUrl = $businessProfile?->phoneUrl();
    $quoteUrl = route('public.quote');

    $listed = $services->getCollection();
    $hasCategories = $listed->contains(fn ($service) => $service->category !== null);

    // Categories in their own sort_order; services keep the controller's
    // order inside each. Uncategorised services close the list under a
    // neutral heading rather than being silently dropped.
    $sections = $hasCategories
        ? $listed->groupBy(fn ($service) => $service->category?->id ?? 0)
            ->map(fn ($group, $key) => ['name' => $key === 0 ? 'خدمات أخرى' : $group->first()->category->name, 'sort' => $key === 0 ? PHP_INT_MAX : $group->first()->category->sort_order, 'services' => $group])
            ->sortBy('sort')
            ->values()
        : collect([['name' => null, 'sort' => 0, 'services' => $listed]]);

    $showIndex = $listed->count() >= 4;
    $rowNumber = 0;
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Header + compact index ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-16 items-end">
                <div>
                    <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">خدماتنا</h1>
                    <p class="mt-4 text-lg text-neutral-600 max-w-xl leading-relaxed">اختر ما يناسب مساحتك واحتياجك. كل خدمة لها صفحة تشرح ما تشمله وكيف ننفذها.</p>
                </div>

                @if ($showIndex)
                    <nav aria-label="فهرس الخدمات">
                        <p class="text-sm font-medium tracking-wide text-neutral-500">في هذه الصفحة</p>
                        <ol class="mt-3 grid sm:grid-cols-2 gap-x-6">
                            @foreach ($listed as $service)
                                <li class="border-b border-neutral-200">
                                    <a href="#service-{{ $service->id }}" class="flex items-baseline gap-3 py-2.5 text-sm text-ink-950 hover:text-primary-700 transition-colors">
                                        <span class="font-display text-xs text-primary-600 tabular-nums shrink-0">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="min-w-0">{{ $service->name }}</span>
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
        {{-- ===== 2. Editorial rows, grouped by real category ===== --}}
        @foreach ($sections as $section)
            <section @class(['border-t border-neutral-200' => ! $loop->first])>
                <x-public.container width="wide" class="py-10 md:py-14">
                    @if ($section['name'])
                        <x-public.section-marker :label="$section['name']" heading class="mb-2" />
                    @endif

                    <ol>
                        @foreach ($section['services'] as $service)
                            @php $rowNumber++; @endphp
                            <li id="service-{{ $service->id }}" class="scroll-mt-24 border-b border-neutral-200 last:border-b-0">
                                <a href="{{ $urlResolver->urlForPage($service->page) }}" @class([
                                    'group grid gap-5 sm:gap-8 items-start sm:items-center py-7 md:py-9',
                                    'grid-cols-[minmax(0,1fr)_96px] sm:grid-cols-[minmax(0,1fr)_200px] lg:grid-cols-[minmax(0,1fr)_320px]' => (bool) $service->featuredMedia,
                                ])>
                                    <div class="flex gap-4 md:gap-6 min-w-0">
                                        <span class="font-display text-sm md:text-base text-primary-600 tabular-nums shrink-0 pt-1.5 md:pt-2" aria-hidden="true">{{ str_pad((string) $rowNumber, 2, '0', STR_PAD_LEFT) }}</span>
                                        <div class="min-w-0">
                                            @if ($service->is_featured)
                                                <p class="text-xs font-medium tracking-wide text-primary-700 mb-1.5">خدمة مميزة</p>
                                            @endif
                                            <h3 class="font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors">
                                                {{ $service->name }}
                                            </h3>
                                            @if ($service->short_description)
                                                <p class="mt-2.5 text-neutral-600 leading-relaxed max-w-xl">{{ $service->short_description }}</p>
                                            @endif
                                            @if ($price = $service->publicPrice())
                                                <p class="mt-2.5 text-sm text-neutral-500"><span class="font-medium text-ink-950 tabular-nums">{{ $price->label() }}</span></p>
                                            @endif
                                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary-700 underline-offset-4 group-hover:underline">
                                                تفاصيل الخدمة
                                                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                                            </span>
                                        </div>
                                    </div>

                                    @if ($service->featuredMedia)
                                        <img
                                            src="{{ $service->featuredMedia->url() }}"
                                            alt="{{ $service->featuredMedia->alt_text ?? $service->name }}"
                                            @if ($rowNumber > 1) loading="lazy" @endif
                                            width="640"
                                            height="480"
                                            class="w-full aspect-square sm:aspect-[4/3] object-cover"
                                        >
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ol>

                    @if ($loop->last)
                        <x-public.pagination :paginator="$services" />
                    @endif
                </x-public.container>
            </section>
        @endforeach
    @else
        {{-- Customer-facing, never "nothing published". --}}
        <x-public.container width="narrow" class="py-16 md:py-24">
            <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950">خدماتنا في طريقها إلى هذه الصفحة</h2>
            <p class="mt-2 text-neutral-600 leading-relaxed">أخبرنا بما تحتاجه الآن وسنرد عليك بما يمكننا تنفيذه.</p>
        </x-public.container>
    @endif

    {{-- ===== 3. Decision - for the visitor still unsure which one ===== --}}
    <section class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">لست متأكدًا أي خدمة تناسبك؟</h2>
                <p class="mt-4 text-ink-200 leading-relaxed">صف لنا المكان وحالته وسنقترح عليك الخدمة المناسبة مع عرض سعر.</p>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">تواصل عبر واتساب</x-public.button>
                    @endif
                    @if ($phoneUrl)
                        <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-ink-200 hover:text-white transition-colors">
                            <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
