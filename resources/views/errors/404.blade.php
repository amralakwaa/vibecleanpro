@php
    // Error views are rendered directly by Laravel's exception handler, not
    // through a controller - so this builds its own minimal SeoHeadData
    // rather than relying on one being passed in. Always noindex: a 404 is
    // never content worth ranking, and it must never look like a normal
    // 200 page to a crawler (see PublicPageController's HTTP status policy).
    $businessProfile = \App\Models\BusinessProfile::query()->first();
    $urlResolver = app(\App\Seo\UrlResolver::class);

    $seo = new \App\Seo\ValueObjects\SeoHeadData(
        title: 'الصفحة غير موجودة | '.($businessProfile?->name ?? config('app.name')),
        metaDescription: null,
        canonicalUrl: $urlResolver->absoluteUrl(request()->getPathInfo()),
        robotsContent: 'noindex, follow',
        openGraph: [
            'title' => 'الصفحة غير موجودة',
            'description' => null,
            'image' => null,
            'url' => $urlResolver->absoluteUrl(request()->getPathInfo()),
            'type' => 'website',
        ],
        structuredData: [],
        breadcrumbs: [],
    );

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في التواصل معكم');
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :mobile-bar="false">
    <section class="surface-atmos relative isolate overflow-hidden text-white min-h-[70vh] flex items-center">

        {{-- Decorative: concentric rings ===== --}}
        <div class="pointer-events-none absolute -z-10 start-[-5rem] top-1/2 -translate-y-1/2 hidden md:block" aria-hidden="true">
            <div class="relative w-[28rem] h-[28rem] lg:w-[32rem] lg:h-[32rem]">
                <div class="absolute inset-0 rounded-full border border-primary-400/20"></div>
                <div class="absolute inset-[18%] rounded-full border border-primary-400/25"></div>
                <div class="absolute inset-[36%] rounded-full border border-primary-500/20"></div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-primary-500 shadow-[0_0_0_8px_rgba(37,99,235,0.12)]"></div>
            </div>
        </div>

        {{-- Decorative: large "404" numeral ===== --}}
        <div class="pointer-events-none absolute -z-10 inset-0 flex items-center justify-center select-none" aria-hidden="true">
            <span class="font-display font-medium text-[18rem] leading-none text-white/[0.03] tracking-tight">٤٠٤</span>
        </div>

        {{-- Glow ===== --}}
        <div class="glow-primary absolute -top-32 end-1/4 w-[30rem] h-[30rem] -z-10 opacity-50" aria-hidden="true"></div>

        <x-public.container width="narrow" class="relative py-24 md:py-32 text-center">
            <div class="reveal">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/15 px-3.5 py-1.5 text-sm font-medium text-white/90 backdrop-blur-sm">
                    <x-public.icon name="shield-check" class="w-4 h-4 text-primary-300" />
                    خطأ ٤٠٤
                </p>

                <h1 class="mt-6 font-display text-4xl md:text-6xl md:leading-[1.05] font-medium tracking-tight text-white text-balance">
                    هذه الصفحة غير موجودة
                </h1>

                <p class="mt-5 text-lg text-white/75 leading-relaxed max-w-md mx-auto">
                    قد يكون الرابط قديمًا أو تمت إزالة هذه الصفحة. جرّب أحد الروابط أدناه أو تواصل معنا مباشرة.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <x-public.button href="{{ url('/') }}" variant="cta" size="lg" icon="arrow-start" class="!bg-white !text-primary-700 hover:!bg-primary-50">
                        الصفحة الرئيسية
                    </x-public.button>
                    <x-public.button href="{{ route('public.services.index') }}" variant="secondary" size="lg" class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">
                        خدماتنا
                    </x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">
                            واتساب
                        </x-public.button>
                    @else
                        <x-public.button href="{{ route('public.contact') }}" variant="secondary" size="lg" class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">
                            تواصل معنا
                        </x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
