{{--
    Homepage - Evidence-Led Editorial.

    Composed as six visual moments rather than a stack of interchangeable
    sections. The previous version ran eight consecutive
    `grid sm:grid-cols-2 lg:grid-cols-3` card grids under eight centered
    headers, which is what made it read as a template; here the change
    between moments comes from the LAYOUT TYPE changing (full-bleed photo
    -> split -> editorial rows -> proof -> ruled lists -> decision), not
    from swapping the background color every time.

    Every piece of content the old page carried is still here and still
    crawlable - services, areas, projects, offers, FAQs and testimonials
    all keep real links and real DOM text. Only the presentation is
    merged. Optional content hides itself entirely when empty; no
    "لم تتم إضافة..." placeholder ever reaches a visitor.
--}}
@php
    use App\Seo\UrlResolver;

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن خدماتكم');
    $urlResolver = app(UrlResolver::class);
    $quoteUrl = route('public.quote');
    $businessUrl = route('public.contact', ['for' => 'business']);

    // Featured subset gets the editorial treatment; the rest stay as real
    // links in a typographic list so nothing drops out of the crawl.
    $featuredServices = $services->take(3);
    $remainingServices = $services->skip(3);

    $leadProject = $beforeAfterProjects->first();
    $supportingProjects = $beforeAfterProjects->skip(1);
    $leadTestimonial = $testimonials->first();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :header-overlay="(bool) $heroImage">

    {{-- ===== Moment 1 - Hero ===== --}}
    <x-public.home-hero
        :image="$heroImage"
        eyebrow="الرياض"
        heading="نترك أثرًا يمكنك رؤيته"
        description="تنظيف احترافي للمنازل والمنشآت، بفرق مدربة ومعايير عمل واضحة من أول زيارة حتى التسليم."
        :cta-url="$quoteUrl"
        cta-label="اطلب عرض سعر"
        :secondary-url="route('public.projects.index')"
        secondary-label="شاهد أعمالنا"
    />

    {{-- ===== Moment 2 - B2C / B2B split =====
         Two different doors, not two identical cards: the residential
         half stays on the light canvas, the business half sits on the
         navy identity surface. The difference registers before a single
         word is read. --}}
    <section class="grid md:grid-cols-2" aria-label="اختر ما يناسبك">
        <div class="bg-white px-6 py-14 md:px-12 md:py-24 lg:px-16 flex flex-col justify-center border-b md:border-b-0 md:border-s border-neutral-200">
            <p class="text-sm font-medium tracking-wide text-neutral-500">للأفراد والمنازل</p>
            <h2 class="mt-4 font-display text-3xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">
                عناية بمساحتك التي تعود إليها كل يوم
            </h2>
            <p class="mt-4 text-neutral-600 leading-relaxed max-w-md">
                فلل، منازل وشقق - خدمة تُحجز بسرعة، وفريق يصل في الموعد، ونتيجة تراها بنفسك.
            </p>
            <div class="mt-7">
                <a href="{{ route('public.services.index') }}" class="inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 hover:underline">
                    تصفح الخدمات المنزلية
                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                </a>
            </div>
        </div>

        <div class="bg-ink-950 text-white px-6 py-14 md:px-12 md:py-24 lg:px-16 flex flex-col justify-center">
            <p class="text-sm font-medium tracking-wide text-ink-300">للشركات والمنشآت</p>
            <h2 class="mt-4 font-display text-3xl md:text-4xl font-medium tracking-tight text-white text-balance">
                عقود تشغيل ونظافة مُدارة بمعايير مؤسسية
            </h2>
            <p class="mt-4 text-ink-200 leading-relaxed max-w-md">
                مكاتب، منشآت وإدارة مرافق - نطاق عمل مكتوب، فريق ثابت، وتقارير جودة دورية.
            </p>
            <ul class="mt-6 space-y-2.5 text-sm text-ink-200">
                <li class="flex items-center gap-2.5 border-b border-white/10 pb-2.5">
                    <x-public.icon name="check" class="w-4 h-4 text-primary-400 shrink-0" /> نطاق عمل وجدول زمني متفق عليه
                </li>
                <li class="flex items-center gap-2.5 border-b border-white/10 pb-2.5">
                    <x-public.icon name="check" class="w-4 h-4 text-primary-400 shrink-0" /> فريق مخصص ومشرف مسؤول
                </li>
                <li class="flex items-center gap-2.5">
                    <x-public.icon name="check" class="w-4 h-4 text-primary-400 shrink-0" /> مراجعة جودة موثقة
                </li>
            </ul>
            <div class="mt-8">
                <x-public.button :href="$businessUrl" variant="cta" icon="building">اطلب عرض تعاقد</x-public.button>
            </div>
        </div>
    </section>

    {{-- ===== Moment 3 - Services as editorial rows ===== --}}
    @if ($services->isNotEmpty())
        <x-public.section density="feature" width="wide">
            <x-public.section-marker number="٠١" label="خدماتنا" class="mb-12" />

            <h2 class="sr-only">خدماتنا</h2>

            <div class="space-y-14 md:space-y-20">
                @foreach ($featuredServices as $index => $service)
                    <x-public.service-row
                        :service="$service"
                        :url="$urlResolver->urlForPage($service->page)"
                        :flip="$index % 2 === 1"
                        @class(['border-t border-neutral-200 pt-14 md:pt-20' => ! $loop->first])
                    />
                @endforeach
            </div>

            @if ($remainingServices->isNotEmpty())
                <div class="mt-14 md:mt-20 border-t border-neutral-200 pt-8">
                    <p class="text-sm font-medium tracking-wide text-neutral-500 mb-4">خدمات أخرى</p>
                    <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-10">
                        @foreach ($remainingServices as $service)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                    class="flex items-center justify-between gap-3 py-3.5 text-ink-950 hover:text-primary-700 transition-colors">
                                    {{ $service->name }}
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 rtl:rotate-180 shrink-0" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-10">
                <a href="{{ route('public.services.index') }}" class="inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 hover:underline">
                    جميع الخدمات
                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                </a>
            </div>
        </x-public.section>
    @endif

    {{-- ===== Moment 4 - Evidence =====
         The strongest moment on the page. Real before/after only; when
         no project carries both stages the whole moment disappears
         rather than showing manufactured proof. --}}
    @if ($beforeAfterProjects->isNotEmpty())
        <section class="bg-white border-y border-neutral-200">
            <x-public.container width="wide" class="py-20 md:py-32">
                <x-public.section-marker number="٠٢" label="نتائج حقيقية" class="mb-12" />

                {{-- Shared moment-heading scale: a long Arabic sentence at
                     text-3xl ran to five lines on a 375px screen, so the
                     mobile step drops to text-2xl and only the phone
                     breakpoint changes - the desktop size is untouched. --}}
                <h2 class="font-display text-2xl leading-snug sm:text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance max-w-2xl">
                    الفرق الذي يمكن قياسه بالنظر
                </h2>

                <x-public.evidence-band
                    :project="$leadProject"
                    :url="$urlResolver->urlForPage($leadProject->page)"
                    :before="$leadProject->media->firstWhere('pivot.stage', 'before')"
                    :after="$leadProject->media->firstWhere('pivot.stage', 'after')"
                    :service-name="$leadProject->services->first()?->name"
                    :area-name="$leadProject->area?->name"
                    class="mt-12"
                />

                @if ($supportingProjects->isNotEmpty())
                    <div class="mt-14 grid md:grid-cols-2 gap-10 md:gap-12">
                        @foreach ($supportingProjects as $project)
                            <x-public.evidence-band
                                :project="$project"
                                :url="$urlResolver->urlForPage($project->page)"
                                :before="$project->media->firstWhere('pivot.stage', 'before')"
                                :after="$project->media->firstWhere('pivot.stage', 'after')"
                                :service-name="$project->services->first()?->name"
                                :area-name="$project->area?->name"
                            />
                        @endforeach
                    </div>
                @endif

                <div class="mt-12">
                    <a href="{{ route('public.projects.index') }}" class="inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 hover:underline">
                        جميع الأعمال
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== Moment 5 - Process + Trust + Coverage =====
         Three former sections merged into one ruled composition. The
         content is unchanged; what is gone is two extra background
         swaps and two extra centered headers. --}}
    <x-public.section density="feature" width="wide">
        <x-public.section-marker number="٠٣" label="كيف نعمل" class="mb-12" />

        <h2 class="font-display text-2xl leading-snug sm:text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance max-w-2xl">
            طريقة عمل واضحة، من أول اتصال حتى المراجعة
        </h2>

        {{-- Process: the numerals ARE the graphic. No icon circles, no
             step cards, no connecting line. --}}
        <ol class="mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-x-10 gap-y-10">
            @foreach ([
                ['٠١', 'تقييم الاحتياج', 'نفهم طبيعة المساحة ومتطلبات الخدمة.'],
                ['٠٢', 'تحديد خطة الخدمة', 'نحدد نطاق العمل والجدول الزمني المناسب.'],
                ['٠٣', 'التنفيذ الاحترافي', 'فريقنا ينفذ العمل وفق معايير واضحة.'],
                ['٠٤', 'مراجعة الجودة', 'نراجع النتيجة معك قبل الاعتماد النهائي.'],
            ] as [$num, $title, $description])
                <li class="border-t border-ink-950/15 pt-5">
                    <span class="font-display text-4xl md:text-5xl font-light text-primary-600 tabular-nums">{{ $num }}</span>
                    <p class="mt-4 font-medium text-ink-950">{{ $title }}</p>
                    <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
                </li>
            @endforeach
        </ol>

        {{-- Trust: a ruled list, not a grid of white cards, and
             deliberately unnumbered - these are parallel facts, not a
             sequence, so numbering them would be decoration. --}}
        <div class="mt-20 border-t border-neutral-200 pt-12">
            <h3 class="font-display text-2xl font-medium text-ink-950">لماذا يختارنا العملاء</h3>
            <ul class="mt-6 grid md:grid-cols-2 gap-x-12">
                @foreach ([
                    ['فريق مدرّب ومتخصص', 'أفراد فريقنا مؤهلون ومدربون على معايير عمل واضحة.'],
                    ['معدات ومواد احترافية', 'أدوات وتقنيات تنظيف مناسبة لكل نوع مساحة.'],
                    ['إجراءات عمل واضحة', 'خطوات محددة لكل خدمة من البداية حتى التسليم.'],
                    ['متابعة الجودة', 'نراجع نتيجة العمل قبل اعتماد أي خدمة.'],
                    ['خدمة منظمة وموثوقة', 'مواعيد واضحة والتزام بما تم الاتفاق عليه.'],
                ] as [$title, $description])
                    <li class="border-b border-neutral-200 py-4">
                        <p class="font-medium text-ink-950">{{ $title }}</p>
                        <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Coverage: a typographic index. Every area link stays a real
             crawlable link, but a subset is shown with a route to the
             full index rather than dumping every neighbourhood on the
             homepage. --}}
        @if ($areas->isNotEmpty())
            <div class="mt-16 border-t border-neutral-200 pt-12">
                <div class="flex flex-wrap items-baseline justify-between gap-4">
                    <h3 class="font-display text-2xl font-medium text-ink-950">مناطق التغطية في الرياض</h3>
                    <a href="{{ route('public.areas.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                        جميع المناطق
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>

                <ul class="mt-6 grid grid-cols-2 lg:grid-cols-3 gap-x-10">
                    @foreach ($areas as $area)
                        <li class="border-b border-neutral-200">
                            <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                class="flex items-baseline justify-between gap-3 py-3.5 text-ink-950 hover:text-primary-700 transition-colors">
                                <span>{{ $area->name }}</span>
                                @if ($area->services_count)
                                    <span class="text-xs text-neutral-400 tabular-nums shrink-0">{{ $area->services_count }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-public.section>

    {{-- ===== Moment 6 - Social proof, objections, decision ===== --}}
    @if ($leadTestimonial || $offers->isNotEmpty() || $faqs->isNotEmpty())
        <section class="bg-white border-t border-neutral-200">
            <x-public.container width="wide" class="py-20 md:py-32">
                <x-public.section-marker number="٠٤" label="قبل أن تقرر" class="mb-12" />

                @if ($leadTestimonial)
                    <figure class="max-w-3xl">
                        <blockquote class="font-display text-2xl md:text-4xl font-light leading-[1.45] text-ink-950 text-balance">
                            {{ $leadTestimonial->content }}
                        </blockquote>
                        <figcaption class="mt-6 text-sm text-neutral-500">
                            {{ collect([$leadTestimonial->author_name, $leadTestimonial->area?->name])->filter()->implode(' · ') }}
                        </figcaption>
                    </figure>
                @endif

                @if ($offers->isNotEmpty())
                    <div @class(['border-t border-neutral-200 pt-10', 'mt-16' => (bool) $leadTestimonial])>
                        <h2 class="font-display text-2xl font-medium text-ink-950">عروض حالية</h2>
                        <ul class="mt-5">
                            @foreach ($offers as $offer)
                                <li class="border-b border-neutral-200">
                                    <a href="{{ $urlResolver->urlForPage($offer->page) }}"
                                        class="flex flex-wrap items-baseline gap-x-4 gap-y-1 py-4 text-ink-950 hover:text-primary-700 transition-colors">
                                        <span class="font-medium">{{ $offer->title }}</span>
                                        @if ($offer->discount_label)
                                            <span class="text-sm text-primary-700">{{ $offer->discount_label }}</span>
                                        @endif
                                        @if ($offer->ends_at)
                                            <span class="text-xs text-neutral-400">حتى {{ $offer->ends_at->translatedFormat('j F Y') }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($faqs->isNotEmpty())
                    <div @class(['border-t border-neutral-200 pt-10', 'mt-16' => $leadTestimonial || $offers->isNotEmpty()])>
                        <h2 class="font-display text-2xl font-medium text-ink-950">أسئلة يطرحها عملاؤنا</h2>
                        <div class="mt-4 max-w-2xl divide-y divide-neutral-200">
                            @foreach ($faqs as $faq)
                                <x-public.faq-item :question="$faq->question" :answer="$faq->answer" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- Final decision. One blue action; WhatsApp stays the secondary
         channel in its own green, never a second blue button. --}}
    <section id="contact" class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-20 md:py-28">
            <div class="max-w-2xl">
                <h2 class="font-display text-3xl md:text-5xl font-medium tracking-tight text-white text-balance">
                    جاهزون للعناية بمساحتك
                </h2>
                <p class="mt-4 text-ink-200 leading-relaxed">
                    أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر مناسب.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>

                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">
                            تواصل عبر واتساب
                        </x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
