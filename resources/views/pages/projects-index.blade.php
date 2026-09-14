{{--
    Projects Index - answers "show me what you have actually done".

    An evidence gallery: photographs first, words second. The featured
    project (when it leads page one) opens as a spread; the rest run as a
    column gallery where every image keeps its own real proportions -
    tall, square, wide - so the page reads as a wall of actual jobs, not
    a grid of identically cropped thumbnails. Entries without a photo
    render as typographic entries rather than grey placeholders.

    Every line is real data: title, the Area it was done in, the Service
    relation, completed_at, and a small "قبل وبعد" mark when the project
    genuinely has both stages (the comparison itself lives on the case
    study, never here). The existing ?service / ?area filters, canonical
    policy and pagination are kept exactly as they were.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، رأيت أعمالكم وأرغب في طلب خدمة مشابهة');
    $phoneUrl = $businessProfile?->phoneUrl();
    $quoteUrl = route('public.quote');
    $isFiltered = $activeService || $activeArea;

    $afterImage = fn ($project) => $project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first();
    $hasBeforeAndAfter = fn ($project) => $project->media->contains('pivot.stage', 'before') && $project->media->contains('pivot.stage', 'after');

    // Real proportions, clamped so one extreme upload cannot break the
    // rhythm of the gallery.
    $ratio = function ($media): string {
        $value = $media->width && $media->height ? $media->width / $media->height : 4 / 3;

        return (string) max(0.75, min(1.6, $value));
    };

    $lead = $projects->onFirstPage() && $projects->first()?->is_featured ? $projects->first() : null;
    $gallery = $lead ? $projects->getCollection()->skip(1) : $projects->getCollection();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Header + filters (kept) ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">أعمالنا</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">صور من مواقع نفذنا فيها العمل فعليًا، بتاريخها ومكانها.</p>

            @if ($filterServices->isNotEmpty() || $filterAreas->isNotEmpty())
                <form method="GET" class="mt-8 pt-6 border-t border-neutral-200 grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-end sm:gap-x-4">
                    @if ($filterServices->isNotEmpty())
                        <div class="sm:w-56">
                            <x-public.field.select name="service" label="الخدمة"
                                :options="['' => 'كل الخدمات'] + $filterServices->pluck('name', 'id')->all()" :selected="$activeService?->id" />
                        </div>
                    @endif
                    @if ($filterAreas->isNotEmpty())
                        <div class="sm:w-56">
                            <x-public.field.select name="area" label="المنطقة"
                                :options="['' => 'كل المناطق'] + $filterAreas->pluck('name', 'id')->all()" :selected="$activeArea?->id" />
                        </div>
                    @endif
                    <x-public.button type="submit" variant="secondary" class="col-span-2 sm:col-auto">تصفية</x-public.button>
                    @if ($isFiltered)
                        <x-public.button href="{{ route('public.projects.index') }}" variant="text" class="col-span-2 sm:col-auto min-h-12">إزالة التصفية</x-public.button>
                    @endif
                </form>
            @endif
        </x-public.container>
    </section>

    @if ($projects->isNotEmpty())
        {{-- ===== 2. Featured spread ===== --}}
        @if ($lead)
            <x-public.container width="wide" class="py-12 md:py-16">
                <x-public.section-marker label="مشروع مميز" class="mb-8" />
                <a href="{{ $urlResolver->urlForPage($lead->page) }}" @class(['group grid gap-8 lg:gap-12 items-end', 'lg:grid-cols-[1.5fr_1fr]' => (bool) $afterImage($lead)])>
                    @if ($image = $afterImage($lead))
                        <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? $lead->title }}"
                            width="1200" height="800" fetchpriority="high"
                            class="w-full aspect-[4/3] md:aspect-[3/2] object-cover">
                    @endif
                    <div>
                        <p class="text-sm text-neutral-500 flex flex-wrap gap-x-3 gap-y-1">
                            @if ($lead->area)
                                <span class="text-primary-700 font-medium">{{ $lead->area->name }}</span>
                            @endif
                            @if ($lead->completed_at)
                                <time datetime="{{ $lead->completed_at->toDateString() }}">{{ $lead->completed_at->translatedFormat('F Y') }}</time>
                            @endif
                        </p>
                        <h2 class="mt-3 font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors">
                            {{ $lead->title }}
                        </h2>
                        @if ($lead->summary)
                            <p class="mt-4 text-neutral-600 leading-relaxed">{{ $lead->summary }}</p>
                        @endif
                        @if ($lead->services->isNotEmpty() || $hasBeforeAndAfter($lead))
                            <p class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-neutral-500">
                                @if ($lead->services->isNotEmpty())
                                    <span>{{ $lead->services->pluck('name')->join('، ') }}</span>
                                @endif
                                @if ($hasBeforeAndAfter($lead))
                                    <span class="inline-flex items-center gap-1.5 text-ink-950"><span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>يتضمن صور قبل وبعد</span>
                                @endif
                            </p>
                        @endif
                        <span class="mt-6 inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 group-hover:underline">
                            اقرأ دراسة الحالة
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </span>
                    </div>
                </a>
            </x-public.container>
        @endif

        {{-- ===== 3. Evidence gallery ===== --}}
        @if ($gallery->isNotEmpty())
            <section @class(['border-t border-neutral-200' => (bool) $lead])>
                <x-public.container width="wide" class="py-12 md:py-16">
                    <x-public.section-marker :label="$lead ? 'المزيد من أعمالنا' : ($isFiltered ? 'أعمال مطابقة لاختيارك' : 'أعمال منفذة')" heading class="mb-8" />

                    <ul class="columns-1 sm:columns-2 lg:columns-3 gap-x-8">
                        @foreach ($gallery as $project)
                            @php $image = $afterImage($project); @endphp
                            <li class="break-inside-avoid mb-10 md:mb-12">
                                <a href="{{ $urlResolver->urlForPage($project->page) }}" @class(['group block', 'border-t-2 border-ink-950 pt-5' => ! $image])>
                                    @if ($image)
                                        <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? $project->title }}" loading="lazy"
                                            width="{{ $image->width ?: 800 }}" height="{{ $image->height ?: 600 }}"
                                            class="w-full object-cover bg-neutral-100" style="aspect-ratio: {{ $ratio($image) }}">
                                    @endif
                                    <p @class(['text-sm text-neutral-500 flex flex-wrap gap-x-3 gap-y-1', 'mt-4' => (bool) $image])>
                                        @if ($project->area)
                                            <span class="text-primary-700 font-medium">{{ $project->area->name }}</span>
                                        @endif
                                        @if ($project->completed_at)
                                            <time datetime="{{ $project->completed_at->toDateString() }}">{{ $project->completed_at->translatedFormat('F Y') }}</time>
                                        @endif
                                    </p>
                                    <h3 @class(['font-display font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors', 'mt-1.5 text-lg' => (bool) $image, 'mt-2 text-xl md:text-2xl' => ! $image])>
                                        {{ $project->title }}
                                    </h3>
                                    @if (! $image && $project->summary)
                                        <p class="mt-2 text-neutral-600 leading-relaxed">{{ $project->summary }}</p>
                                    @endif
                                    @if ($project->services->isNotEmpty() || $hasBeforeAndAfter($project))
                                        <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-500">
                                            @if ($project->services->isNotEmpty())
                                                <span>{{ $project->services->first()->name }}</span>
                                            @endif
                                            @if ($hasBeforeAndAfter($project))
                                                <span class="inline-flex items-center gap-1.5 text-ink-950"><span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>قبل وبعد</span>
                                            @endif
                                        </p>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <x-public.pagination :paginator="$projects" />
                </x-public.container>
            </section>
        @elseif ($projects->hasPages())
            <x-public.container width="wide" class="pb-12">
                <x-public.pagination :paginator="$projects" />
            </x-public.container>
        @endif
    @else
        {{-- Customer-facing empty states: one for a filter that matched
             nothing, one for a gallery that is not filled yet. --}}
        <x-public.container width="narrow" class="py-16 md:py-24">
            @if ($isFiltered)
                <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950">لا توجد أعمال مطابقة لهذا الاختيار</h2>
                <p class="mt-2 text-neutral-600 leading-relaxed">جرّب خدمة أو منطقة أخرى، أو اطلع على كل الأعمال.</p>
                <a href="{{ route('public.projects.index') }}" class="mt-5 inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 hover:underline">
                    عرض كل الأعمال
                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                </a>
            @else
                <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950">نجهّز معرض أعمالنا</h2>
                <p class="mt-2 text-neutral-600 leading-relaxed">راسلنا وسنشاركك أمثلة من أعمال مشابهة لما تحتاجه.</p>
            @endif
        </x-public.container>
    @endif

    {{-- ===== 4. Decision ===== --}}
    <section class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">تريد نتيجة مشابهة في مكانك؟</h2>
                <p class="mt-4 text-ink-200 leading-relaxed">أرسل لنا تفاصيل المكان وسنعود إليك بعرض سعر.</p>
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
