@php
    use App\Seo\UrlResolver;

    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
    $urlResolver = app(UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero
        :heading="($businessProfile->name ?? config('app.name')).' لخدمات التنظيف الاحترافية بالرياض'"
        subheading="فريق مدرّب وأدوات احترافية لتنظيف منزلك أو منشأتك التجارية، بمواعيد موثوقة ونتيجة تدوم."
        :cta-label="$whatsappUrl ? 'احجز عبر واتساب' : null"
        :cta-url="$whatsappUrl"
    />

    {{-- Trust strip: general, true statements only - no invented figures
         or certifications (see Phase 5 report, item 23). --}}
    <x-public.section tone="surface" class="!py-10">
        <div class="grid sm:grid-cols-3 gap-6">
            <x-public.trust-card icon="shield-check" title="فريق مدرّب وموثوق" />
            <x-public.trust-card icon="sparkles" title="أدوات ومواد تنظيف احترافية" />
            <x-public.trust-card icon="clock" title="مواعيد مرنة وموثوقة" />
        </div>
    </x-public.section>

    <x-public.section id="services">
        <x-public.section-header eyebrow="خدماتنا" title="خدمات التنظيف التي نقدمها" align="center" class="mb-10" />

        @if ($services->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    <x-public.service-card :service="$service" :url="$urlResolver->urlForPage($service->page)" />
                @endforeach
            </div>
        @else
            <x-public.empty-state icon="sparkles" title="لم تُضَف خدمات منشورة بعد" />
        @endif
    </x-public.section>

    <x-public.section id="projects" tone="surface">
        <x-public.section-header eyebrow="أعمالنا" title="من مشاريعنا المنفذة" align="center" class="mb-10" />

        @if ($projects->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($projects as $project)
                    <x-public.project-card
                        :project="$project"
                        :url="$urlResolver->urlForPage($project->page)"
                        :image="$project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first()"
                        :area-name="$project->area?->name"
                    />
                @endforeach
            </div>
        @else
            <x-public.empty-state icon="briefcase" title="لم تُضَف مشاريع منشورة بعد" />
        @endif
    </x-public.section>

    <x-public.section id="areas">
        <x-public.section-header eyebrow="مناطق التغطية" title="نخدم هذه المناطق في الرياض" align="center" class="mb-10" />

        @if ($areas->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($areas as $area)
                    <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" :services-count="$area->services_count" />
                @endforeach
            </div>
        @else
            <x-public.empty-state icon="map-pin" title="لم تُضَف مناطق منشورة بعد" />
        @endif
    </x-public.section>

    {{-- Neutral, structural placeholder copy proving the "steps" layout -
         no invented business claims (see Phase 5 report, item 30). --}}
    <x-public.section tone="surface">
        <x-public.section-header title="كيف تحصل على الخدمة" align="center" class="mb-10" />
        <ol class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['title' => 'تواصل معنا', 'description' => 'عبر واتساب أو اتصال مباشر.'],
                ['title' => 'تحديد الموعد', 'description' => 'نتفق على الخدمة والوقت المناسب.'],
                ['title' => 'تنفيذ التنظيف', 'description' => 'فريقنا ينفّذ العمل بعناية.'],
                ['title' => 'تسليم واعتماد', 'description' => 'نراجع النتيجة معك قبل المغادرة.'],
            ] as $index => $step)
                <x-public.step-card :number="$index + 1" :title="$step['title']" :description="$step['description']" />
            @endforeach
        </ol>
    </x-public.section>

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
            title="جاهزون لخدمتك الآن"
            description="تواصل معنا عبر واتساب أو اتصل بنا مباشرة للحصول على عرض سعر."
            :whatsapp-url="$whatsappUrl"
            :phone-url="$phoneUrl"
        />
    </x-public.section>
</x-layouts.public>
