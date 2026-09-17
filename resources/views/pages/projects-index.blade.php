{{--
    Projects Index - Evidence Discovery, in the V2 system.

    An evidence gallery: photographs first, words second. The featured
    project (when it leads page one) opens as a spread; the rest run as a
    column gallery where every image keeps its own real proportions -
    tall, square, wide - so the page reads as a wall of actual jobs, not
    a grid of identically cropped thumbnails. Each photographed entry is
    a tile in the homepage's tile language (the picture is the tile, the
    facts sit on its scrim); an entry without a photograph renders as a
    typographic panel on the tinted field - never a grey placeholder, and
    never a stock picture standing in for work.

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

    {{-- ===== 1. Index head + filters (kept) on the tinted field ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-24 -end-24 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-8 pb-12 md:pt-12 md:pb-16">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">أعمالنا</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">صور من مواقع نفذنا فيها العمل فعليًا، بتاريخها ومكانها.</p>

            @if ($filterServices->isNotEmpty() || $filterAreas->isNotEmpty())
                <form method="GET" class="mt-8 rounded-2xl bg-white/80 ring-1 ring-primary-200/60 backdrop-blur-sm p-4 grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-end sm:gap-x-4">
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
                    <x-public.button type="submit" variant="cta" class="col-span-2 sm:col-auto min-h-12">تصفية</x-public.button>
                    @if ($isFiltered)
                        <x-public.button href="{{ route('public.projects.index') }}" variant="text" class="col-span-2 sm:col-auto min-h-12">إزالة التصفية</x-public.button>
                    @endif
                </form>
            @endif
        </x-public.container>
    </section>

    @if ($projects->isNotEmpty())
        {{-- ===== 2. Featured spread - the result photograph as the hero
                 of the gallery, the facts beside it ===== --}}
        @if ($lead)
            <section class="bg-white" aria-labelledby="projects-lead">
                <x-public.container width="wide" class="py-12 md:py-16">
                    <p class="text-sm font-medium tracking-wide text-primary-700 reveal">مشروع مميز</p>
                    <a href="{{ $urlResolver->urlForPage($lead->page) }}" @class(['group mt-4 grid gap-8 lg:gap-12 items-center reveal', 'lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]' => (bool) $afterImage($lead)])>
                        @if ($image = $afterImage($lead))
                            <div class="relative">
                                <div class="surface-atmos absolute inset-0 -translate-x-3 translate-y-3 md:-translate-x-5 md:translate-y-5 rounded-3xl -z-10" aria-hidden="true"></div>
                                <div class="relative overflow-hidden rounded-3xl ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/15 bg-white">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? $lead->title }}"
                                        width="{{ $image->width ?: 1200 }}" height="{{ $image->height ?: 800 }}" fetchpriority="high"
                                        class="tile-media w-full aspect-[4/3] md:aspect-[3/2] object-cover">
                                    @if ($hasBeforeAndAfter($lead))
                                        <span class="absolute top-4 start-4 inline-flex items-center gap-1.5 rounded-full bg-white/90 text-ink-950 px-3 py-1 text-xs font-medium backdrop-blur-sm"><span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>يتضمن صور قبل وبعد</span>
                                    @endif
                                </div>
                            </div>
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
                            <h2 id="projects-lead" class="mt-3 font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors">
                                {{ $lead->title }}
                            </h2>
                            @if ($lead->summary)
                                <p class="mt-4 text-neutral-600 leading-relaxed">{{ $lead->summary }}</p>
                            @endif
                            @if ($lead->services->isNotEmpty())
                                <p class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($lead->services as $service)
                                        <span class="inline-flex items-center rounded-full bg-primary-50 text-primary-700 px-3 py-1 text-xs font-medium">{{ $service->name }}</span>
                                    @endforeach
                                </p>
                            @endif
                            @if (! $afterImage($lead) && $hasBeforeAndAfter($lead))
                                <p class="mt-3 text-sm text-ink-950 inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>يتضمن صور قبل وبعد</p>
                            @endif
                            <span class="mt-6 inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 group-hover:underline">
                                اقرأ دراسة الحالة
                                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                            </span>
                        </div>
                    </a>
                </x-public.container>
            </section>
        @endif

        {{-- ===== 3. Evidence gallery - real proportions, tiles with the
                 facts on the picture; typographic panels for the rest ===== --}}
        @if ($gallery->isNotEmpty())
            <section @class(['bg-white', 'border-t border-neutral-200' => (bool) $lead]) aria-labelledby="projects-gallery">
                <x-public.container width="wide" class="py-12 md:py-16">
                    <h2 id="projects-gallery" class="text-sm font-medium tracking-wide text-neutral-500 reveal">{{ $lead ? 'المزيد من أعمالنا' : ($isFiltered ? 'أعمال مطابقة لاختيارك' : 'أعمال منفذة') }}</h2>

                    <ul class="mt-6 columns-1 sm:columns-2 lg:columns-3 gap-x-5">
                        @foreach ($gallery as $project)
                            @php $image = $afterImage($project); @endphp
                            <li class="break-inside-avoid mb-5 reveal">
                                @if ($image)
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group relative isolate flex flex-col justify-end overflow-hidden rounded-2xl text-white shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300"
                                        style="aspect-ratio: {{ $ratio($image) }}">
                                        <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? $project->title }}" loading="lazy"
                                            width="{{ $image->width ?: 800 }}" height="{{ $image->height ?: 600 }}"
                                            class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                                        <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                                        @if ($hasBeforeAndAfter($project))
                                            <span class="absolute top-3 start-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 text-ink-950 px-3 py-1 text-xs font-medium backdrop-blur-sm"><span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>قبل وبعد</span>
                                        @endif
                                        <div class="p-5">
                                            <p class="text-xs font-medium tracking-wide text-primary-200 flex flex-wrap gap-x-2">
                                                @if ($project->area)
                                                    <span>{{ $project->area->name }}</span>
                                                @endif
                                                @if ($project->completed_at)
                                                    <span class="text-white/75"><time datetime="{{ $project->completed_at->toDateString() }}">{{ $project->completed_at->translatedFormat('F Y') }}</time></span>
                                                @endif
                                            </p>
                                            <h3 class="mt-1.5 font-display text-lg md:text-xl font-medium tracking-tight text-white text-balance">{{ $project->title }}</h3>
                                            @if ($project->services->isNotEmpty())
                                                <p class="mt-1 text-sm text-white/80">{{ $project->services->first()->name }}</p>
                                            @endif
                                        </div>
                                    </a>
                                @else
                                    <a href="{{ $urlResolver->urlForPage($project->page) }}"
                                        class="group surface-tint relative overflow-hidden flex flex-col rounded-2xl ring-1 ring-primary-200/60 p-5 md:p-6 min-h-[11rem] transition-[box-shadow,ring-color] hover:ring-primary-400 hover:shadow-md">
                                        <div class="glow-primary absolute -top-12 -end-12 w-40 h-40 opacity-60" aria-hidden="true"></div>
                                        <p class="relative text-xs font-medium tracking-wide text-primary-700 flex flex-wrap gap-x-2">
                                            @if ($project->area)
                                                <span>{{ $project->area->name }}</span>
                                            @endif
                                            @if ($project->completed_at)
                                                <span class="text-neutral-500"><time datetime="{{ $project->completed_at->toDateString() }}">{{ $project->completed_at->translatedFormat('F Y') }}</time></span>
                                            @endif
                                        </p>
                                        <h3 class="relative mt-2 font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors">{{ $project->title }}</h3>
                                        @if ($project->summary)
                                            <p class="relative mt-2 text-sm text-neutral-600 leading-relaxed line-clamp-3">{{ $project->summary }}</p>
                                        @endif
                                        <p class="relative mt-auto pt-4 flex items-center justify-between gap-3 text-sm">
                                            <span class="text-neutral-600">{{ $project->services->first()?->name }}@if ($hasBeforeAndAfter($project)) · قبل وبعد @endif</span>
                                            <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                        </p>
                                    </a>
                                @endif
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
        {{-- Customer-facing states: one for a filter that matched nothing,
             one for a gallery that is not filled yet - respectful, and
             never claiming work that does not exist. --}}
        <section class="bg-white">
            <x-public.container width="narrow" class="py-16 md:py-24">
                <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-8 md:p-10 reveal">
                    <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                    @if ($isFiltered)
                        <h2 class="relative font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950">لا توجد أعمال مطابقة لهذا الاختيار</h2>
                        <p class="relative mt-3 text-neutral-600 leading-relaxed">جرّب خدمة أو منطقة أخرى، أو اطلع على كل الأعمال.</p>
                        <a href="{{ route('public.projects.index') }}" class="relative mt-6 inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                            عرض كل الأعمال
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    @else
                        <h2 class="relative font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950">نجهّز معرض أعمالنا</h2>
                        <p class="relative mt-3 text-neutral-600 leading-relaxed">راسلنا وسنشاركك أمثلة من أعمال مشابهة لما تحتاجه.</p>
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="relative mt-6 inline-flex items-center gap-2 min-h-11 font-medium text-neutral-700 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                                <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> راسلنا على واتساب
                            </a>
                        @endif
                    @endif
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 4. Decision ===== --}}
    <section class="surface-atmos relative isolate overflow-hidden text-white">
        <x-public.wave shape="soft" position="top" class="text-white" />
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-28 pb-16 md:pt-36 md:pb-24">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                <div>
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">تريد نتيجة مشابهة في مكانك؟</h2>
                    <p class="mt-4 text-lg text-white/85 leading-relaxed max-w-xl">أرسل لنا تفاصيل المكان وسنعود إليك بعرض سعر. لا يتم الدفع عبر الموقع.</p>
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
