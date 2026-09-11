@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في معرفة المزيد عن أعمالكم');
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="wide" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">أعمالنا</h1>
        <p class="mt-3 text-neutral-600 max-w-2xl">نماذج من المشاريع التي نفّذناها.</p>
    </x-public.section>

    @if ($filterServices->isNotEmpty() || $filterAreas->isNotEmpty())
        <x-public.section width="wide" class="!py-0">
            <form method="GET" class="flex flex-wrap items-end gap-4 mb-8">
                @if ($filterServices->isNotEmpty())
                    <div class="w-full sm:w-56">
                        <x-public.field.select name="service" label="الخدمة" placeholder="كل الخدمات"
                            :options="$filterServices->pluck('name', 'id')" :selected="$activeService?->id" />
                    </div>
                @endif
                @if ($filterAreas->isNotEmpty())
                    <div class="w-full sm:w-56">
                        <x-public.field.select name="area" label="المنطقة" placeholder="كل المناطق"
                            :options="$filterAreas->pluck('name', 'id')" :selected="$activeArea?->id" />
                    </div>
                @endif
                <x-public.button type="submit" variant="secondary">تصفية</x-public.button>
                @if ($activeService || $activeArea)
                    <x-public.button href="{{ route('public.projects.index') }}" variant="text">إزالة التصفية</x-public.button>
                @endif
            </form>
        </x-public.section>
    @endif

    <x-public.section width="wide" class="!pt-0">
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

            <x-public.pagination :paginator="$projects" />
        @else
            <x-public.empty-state icon="briefcase" title="لا توجد مشاريع مطابقة"
                description="جرّب إزالة التصفية أو تواصل معنا مباشرة." />
        @endif
    </x-public.section>

    @if ($whatsappUrl || $phoneUrl)
        <x-public.section>
            <x-public.cta title="أعجبك ما رأيت؟" description="تواصل معنا لبدء مشروعك القادم."
                :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
        </x-public.section>
    @endif
</x-layouts.public>
