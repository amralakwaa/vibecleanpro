@php
    use App\Seo\UrlResolver;

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن خدماتكم');
    $urlResolver = app(UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero
        eyebrow="VIBE CLEAN PRO"
        heading="عناية احترافية للمنازل والمنشآت في الرياض"
        subheading="تنظيف متخصص وإدارة مرافق بمعايير واضحة وجودة يمكن الاعتماد عليها."
        :image="$heroImage"
    >
        <div class="mt-7 flex flex-col sm:flex-row items-center gap-3">
            <x-public.button :href="route('public.quote')" variant="cta" size="lg" icon="check-circle">
                اطلب خدمة منزلية
            </x-public.button>
            <x-public.button :href="route('public.contact')" variant="secondary" size="lg" icon="building">
                حلول الشركات
            </x-public.button>
        </div>
    </x-public.hero>

    {{-- Section 2: reduces B2C/B2B hesitation from the first scroll - see
         the Phase 3 report. Purely navigational, no CMS data (Service has
         no audience/segment field to query against). --}}
    <x-public.section id="audience">
        <x-public.section-header eyebrow="ابدأ من هنا" title="كيف نخدمك؟" align="center" class="mb-10" />
        <div class="grid sm:grid-cols-2 gap-6 max-w-4xl mx-auto">
            <x-public.audience-card
                icon="home"
                title="العناية بمساحتك المنزلية"
                description="تنظيف احترافي للفلل والمنازل والشقق."
                :url="route('public.services.index')"
            />
            <x-public.audience-card
                icon="building"
                title="حلول النظافة للمنشآت"
                description="خدمات تنظيف وعقود تشغيل للشركات."
                :url="route('public.contact')"
            />
        </div>
    </x-public.section>

    <x-public.section id="services" tone="surface">
        <x-public.section-header eyebrow="ما نقدمه" title="خدماتنا المتخصصة" align="center" class="mb-10" />

        @if ($services->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    <x-public.service-card :service="$service" :url="$urlResolver->urlForPage($service->page)" />
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <x-public.button :href="route('public.services.index')" variant="text" icon-trailing="arrow-start">
                    عرض جميع الخدمات
                </x-public.button>
            </div>
        @else
            <x-public.empty-state icon="sparkles" title="لم تُضَف خدمات منشورة بعد" />
        @endif
    </x-public.section>

    {{-- Section 4: real, structural reasons only - no invented figures or
         unverified review counts (see the Phase 3 report). --}}
    <x-public.section id="why-us">
        <x-public.section-header eyebrow="لماذا نحن" title="لماذا يختارنا العملاء؟" align="center" class="mb-10" />
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10 max-w-5xl mx-auto">
            <x-public.trust-card icon="users" title="فريق مدرّب ومتخصص"
                description="أفراد فريقنا مؤهلون ومدربون على معايير عمل واضحة." />
            <x-public.trust-card icon="sparkles" title="معدات ومواد احترافية"
                description="أدوات وتقنيات تنظيف مناسبة لكل نوع مساحة." />
            <x-public.trust-card icon="check-circle" title="إجراءات عمل واضحة"
                description="خطوات محددة لكل خدمة من البداية حتى التسليم." />
            <x-public.trust-card icon="shield-check" title="متابعة الجودة"
                description="نراجع نتيجة العمل قبل اعتماد أي خدمة." />
            <x-public.trust-card icon="clock" title="خدمة منظمة وموثوقة"
                description="مواعيد واضحة والتزام بما تم الاتفاق عليه." />
        </div>
    </x-public.section>

    <x-public.section id="how-we-work" tone="surface">
        <x-public.section-header title="كيف نعمل؟" align="center" class="mb-12" />
        <ol class="grid sm:grid-cols-2 lg:grid-cols-4 gap-y-10 gap-x-6">
            <x-public.step-card :number="1" title="تقييم الاحتياج" description="نفهم طبيعة المساحة ومتطلبات الخدمة." />
            <x-public.step-card :number="2" title="تحديد خطة الخدمة" description="نحدد نطاق العمل والجدول الزمني المناسب." />
            <x-public.step-card :number="3" title="التنفيذ الاحترافي" description="فريقنا ينفذ العمل وفق معايير واضحة." />
            <x-public.step-card :number="4" title="مراجعة الجودة" description="نراجع النتيجة معك قبل الاعتماد النهائي." />
        </ol>
    </x-public.section>

    {{-- Section 6: only projects with a real before AND after photo qualify
         (see HomeController::index) - meta badges (service/area) render
         only when that data actually exists on the project. --}}
    <x-public.section id="projects">
        <x-public.section-header eyebrow="أعمالنا" title="نتائج حقيقية من مشاريعنا" align="center" class="mb-10" />

        @if ($beforeAfterProjects->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($beforeAfterProjects as $project)
                    <x-public.project-showcase
                        :project="$project"
                        :url="$urlResolver->urlForPage($project->page)"
                        :before="$project->media->firstWhere('pivot.stage', 'before')"
                        :after="$project->media->firstWhere('pivot.stage', 'after')"
                        :area-name="$project->area?->name"
                        :service-name="$project->services->first()?->name"
                    />
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <x-public.button :href="route('public.projects.index')" variant="text" icon-trailing="arrow-start">
                    عرض جميع الأعمال
                </x-public.button>
            </div>
        @else
            <x-public.empty-state icon="briefcase" title="لم تُضَف مشاريع قبل/بعد منشورة بعد" />
        @endif
    </x-public.section>

    <x-public.section id="areas" tone="surface">
        <x-public.section-header eyebrow="مناطق التغطية" title="نخدم هذه المناطق في الرياض" align="center" class="mb-10" />

        @if ($areas->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($areas as $area)
                    <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" :services-count="$area->services_count" />
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <x-public.button :href="route('public.areas.index')" variant="text" icon-trailing="arrow-start">
                    عرض جميع المناطق
                </x-public.button>
            </div>
        @else
            <x-public.empty-state icon="map-pin" title="لم تُضَف مناطق منشورة بعد" />
        @endif
    </x-public.section>

    {{-- Section 8: only currently-active/scheduled CMS offers - no fake
         discounts or countdowns (see OfferAvailability). Hidden entirely
         when there is nothing genuine to show. --}}
    @if ($offers->isNotEmpty())
        <x-public.section id="offers">
            <x-public.section-header eyebrow="عروضنا" title="عروض حالية" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($offers as $offer)
                    <x-public.offer-card :offer="$offer" :url="$urlResolver->urlForPage($offer->page)" />
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <x-public.button :href="route('public.offers.index')" variant="text" icon-trailing="arrow-start">
                    عرض جميع العروض
                </x-public.button>
            </div>
        </x-public.section>
    @endif

    {{-- Section 9: sitewide, page-less FAQs (see FaqResource) - content
         itself (ideally objection-handling: "هل تخدمون الشركات؟", "كم
         تستغرق الخدمة؟"...) is managed entirely from the CMS, not authored
         here. --}}
    @if ($faqs->isNotEmpty())
        <x-public.section id="faq" tone="surface">
            <x-public.section-header eyebrow="أسئلة شائعة" title="أسئلة يطرحها عملاؤنا كثيرًا" align="center" class="mb-8" />
            <div class="max-w-2xl mx-auto divide-y divide-neutral-200">
                @foreach ($faqs as $faq)
                    <x-public.faq-item :question="$faq->question" :answer="$faq->answer" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($testimonials->isNotEmpty())
        <x-public.section>
            <x-public.section-header eyebrow="آراء العملاء" title="ماذا يقول عملاؤنا" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($testimonials as $testimonial)
                    <x-public.testimonial-card :testimonial="$testimonial" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    <x-public.section id="contact">
        <x-public.cta
            title="جاهزون للعناية بمساحتك؟"
            description="اطلب الخدمة الآن أو تواصل معنا مباشرة عبر واتساب."
            :quote-url="route('public.quote')"
            :whatsapp-url="$whatsappUrl"
        />
    </x-public.section>
</x-layouts.public>
