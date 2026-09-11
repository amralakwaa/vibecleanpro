@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار عن خدماتكم');
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="wide" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">خدماتنا</h1>
        <p class="mt-3 text-neutral-600 max-w-2xl">تصفح خدمات التنظيف المتاحة واختر ما يناسب احتياجك.</p>
    </x-public.section>

    <x-public.section width="wide" class="!pt-0">
        @if ($services->isNotEmpty())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    <x-public.service-card :service="$service" :url="$urlResolver->urlForPage($service->page)" />
                @endforeach
            </div>

            <x-public.pagination :paginator="$services" />
        @else
            <x-public.empty-state icon="sparkles" title="لا توجد خدمات منشورة حاليًا"
                description="تواصل معنا مباشرة لمعرفة الخدمات المتاحة." />
        @endif
    </x-public.section>

    @if ($whatsappUrl || $phoneUrl)
        <x-public.section>
            <x-public.cta title="لم تجد ما تبحث عنه؟" description="تواصل معنا وسنساعدك في اختيار الخدمة المناسبة."
                :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
        </x-public.section>
    @endif
</x-layouts.public>
