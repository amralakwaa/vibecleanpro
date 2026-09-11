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
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="narrow" class="text-center">
        <p class="text-sm font-semibold text-primary-600">خطأ 404</p>
        <h1 class="mt-2 text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">
            هذه الصفحة غير موجودة
        </h1>
        <p class="mt-3 text-neutral-600 max-w-md mx-auto">
            قد يكون الرابط قديمًا أو تمت إزالة هذه الصفحة. جرّب أحد الروابط التالية:
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <x-public.button href="{{ url('/') }}" variant="primary">الرئيسية</x-public.button>
            <x-public.button href="{{ route('public.services.index') }}" variant="secondary">خدماتنا</x-public.button>
            <x-public.button href="{{ route('public.contact') }}" variant="secondary">تواصل معنا</x-public.button>
        </div>
    </x-public.section>
</x-layouts.public>
