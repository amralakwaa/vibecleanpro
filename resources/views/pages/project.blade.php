{{--
    Project Detail - a real CASE STUDY, not a project page.

    The other three templates each have a different job (Homepage = brand,
    Service = persuasion, Area = local proof); this one's job is evidence.
    So the photographs are the content and the copy is the caption, and
    the composition opens like a magazine case study: a compact title
    block, then the result photograph full width beneath it - a fourth
    hero shape, distinct from the homepage (text over image), Service
    (image beside text) and Area (no image).

    MEDIA HONESTY - the rules this file will not break:
      - only project_media rows are shown, each in the stage the editor
        gave it (before / during / after), each stage in the editor's own
        sort order, never re-labelled, never filtered or tinted
      - "قبل / بعد" is shown ONLY as genuine pairs (nth before beside nth
        after); leftovers of either stage are shown honestly under their
        own stage, never invented a partner
      - a stage with no rows produces no section - there is no empty
        state, no placeholder frame, no stock image
      - every fact in the context rail (service, area, date) comes from a
        real relation or column; nothing about duration, crew size, area,
        savings or satisfaction is ever displayed unless the editor wrote
        it into a content block

    Content blocks stay owned by blocks.blade.php via the only/except
    contract; this file never reads block data.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في خدمة مشابهة لمشروع: '.$project->title);
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', array_filter([
        'service' => $relatedServices->first()?->id,
        'area' => $linkedArea?->id,
    ]));

    $byStage = fn (string $stage) => $project->media
        ->where('pivot.stage', $stage)
        ->sortBy('pivot.sort_order')
        ->values();

    $beforeImages = $byStage('before');
    $duringImages = $byStage('during');
    $afterImages = $byStage('after');

    $heroImage = $afterImages->first() ?? $project->media->sortBy('pivot.sort_order')->first();

    // Genuine pairs only: the nth before beside the nth after. zip() of
    // two equal-length slices is empty when either stage is empty, so
    // this never fabricates a partner.
    $pairCount = min($beforeImages->count(), $afterImages->count());
    $pairs = $beforeImages->take($pairCount)
        ->zip($afterImages->take($pairCount))
        ->map(fn ($pair) => ['before' => $pair[0], 'after' => $pair[1]]);
    $unpairedBefore = $beforeImages->slice($pairCount)->values();
    $unpairedAfter = $afterImages->slice($pairCount)->values();

    $hasEvidence = $project->media->isNotEmpty();
    $hasContext = $relatedServices->isNotEmpty() || $project->area || $project->completed_at;
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Case-study opening: compact title block, then the
             result photograph full width beneath it ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="pt-10 md:pt-14 pb-8 md:pb-10">
            <div class="max-w-3xl">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-primary-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>
                    دراسة حالة
                </p>

                <h1 class="mt-4 font-display text-[1.75rem] leading-tight md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                    {{ $page->title }}
                </h1>

                {{-- Context rail: every fact here is a real relation or
                     column. It is also this page's natural navigation -
                     Project -> Service and Project -> Area live here as
                     crawlable links, so no separate "related" card grid
                     is needed further down. The area only links when its
                     page is genuinely published (see $linkedArea). --}}
                @if ($hasContext)
                    <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                        @if ($relatedServices->isNotEmpty())
                            <div class="flex items-baseline gap-2">
                                <dt class="text-neutral-500">{{ $relatedServices->count() > 1 ? 'الخدمات' : 'الخدمة' }}</dt>
                                <dd class="font-medium text-ink-950">
                                    @foreach ($relatedServices as $service)
                                        <a href="{{ $urlResolver->urlForPage($service->page) }}" class="hover:text-primary-700 underline-offset-4 hover:underline transition-colors">{{ $service->name }}</a>@if (! $loop->last)<span class="text-neutral-400"> · </span>@endif
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if ($project->area)
                            <div class="flex items-baseline gap-2">
                                <dt class="text-neutral-500">المنطقة</dt>
                                <dd class="font-medium text-ink-950">
                                    @if ($linkedArea)
                                        <a href="{{ $urlResolver->urlForPage($linkedArea->page) }}" class="hover:text-primary-700 underline-offset-4 hover:underline transition-colors">{{ $project->area->name }}</a>
                                    @else
                                        {{ $project->area->name }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($project->completed_at)
                            <div class="flex items-baseline gap-2">
                                <dt class="text-neutral-500">تاريخ الإنجاز</dt>
                                <dd class="font-medium text-ink-950">
                                    <time datetime="{{ $project->completed_at->toDateString() }}">{{ $project->completed_at->translatedFormat('F Y') }}</time>
                                </dd>
                            </div>
                        @endif
                    </dl>
                @endif

                <div class="mt-7">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب خدمة مشابهة</x-public.button>
                </div>
            </div>
        </x-public.container>

        @if ($heroImage)
            <figure>
                <img
                    src="{{ $heroImage->url() }}"
                    alt="{{ $heroImage->alt_text ?? $project->title }}"
                    fetchpriority="high"
                    width="1600"
                    height="900"
                    class="w-full aspect-[4/5] sm:aspect-[3/2] lg:aspect-[21/9] object-cover"
                >
                @if ($heroImage->caption)
                    <figcaption class="px-4 sm:px-6 lg:px-8 py-3 text-xs text-neutral-500">{{ $heroImage->caption }}</figcaption>
                @endif
            </figure>
        @endif
    </section>

    {{-- ===== 2. Context - the editor's summary, in a reading measure ===== --}}
    @if ($project->summary)
        <x-public.section width="wide">
            <x-public.section-marker label="عن المشروع" class="mb-8" />
            <p class="max-w-2xl text-lg md:text-xl text-neutral-700 leading-relaxed">{{ $project->summary }}</p>
        </x-public.section>
    @endif

    {{-- ===== 3. Scope of work and any other editor blocks (FAQ deferred) ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" />

    {{-- ===== 4-7. The evidence chapter: one continuous section, three
             movements. Pairs are the centrepiece; leftover befores and
             afters are shown honestly under their own stage; "during"
             sits between as the process record. ===== --}}
    @if ($hasEvidence)
        <section class="bg-white border-y border-neutral-200">
            <x-public.container width="wide" class="py-16 md:py-24">
                <x-public.section-marker label="الدليل" class="mb-10" />

                <h2 class="font-display text-2xl leading-snug md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance max-w-2xl">
                    ما تم قبل العمل، وأثناءه، وبعده
                </h2>

                {{-- Movement A: before-only leftovers (or all befores when
                     nothing exists to pair them with) --}}
                @if ($unpairedBefore->isNotEmpty())
                    <div class="mt-12">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">قبل</h3>
                        <div @class(['grid gap-px bg-neutral-200', 'sm:grid-cols-2' => $unpairedBefore->count() > 1, 'lg:grid-cols-3' => $unpairedBefore->count() > 2])>
                            @foreach ($unpairedBefore as $image)
                                <figure class="bg-neutral-50">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? 'قبل التنفيذ - '.$project->title }}" loading="lazy"
                                        class="w-full aspect-[4/3] object-cover">
                                    @if ($image->caption)
                                        <figcaption class="px-3 py-2 text-xs text-neutral-500">{{ $image->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Movement B: the process, as a documentary sequence.
                     Horizontal rail on phones so the sequence reads left to
                     right (well - right to left) instead of stacking. --}}
                @if ($duringImages->isNotEmpty())
                    <div class="mt-12">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">أثناء العمل</h3>
                        <div class="flex gap-px bg-neutral-200 overflow-x-auto snap-x snap-mandatory sm:grid sm:overflow-visible sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($duringImages as $image)
                                <figure class="bg-neutral-50 shrink-0 w-[78vw] sm:w-auto snap-start">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? 'أثناء التنفيذ - '.$project->title }}" loading="lazy"
                                        class="w-full aspect-[4/3] object-cover">
                                    @if ($image->caption)
                                        <figcaption class="px-3 py-2 text-xs text-neutral-500">{{ $image->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Movement C: the result. Genuine before/after pairs when
                     both stages exist; otherwise the after photographs on
                     their own, never given an invented "before". --}}
                @if ($pairs->isNotEmpty())
                    <div class="mt-12">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">قبل وبعد</h3>
                        <div @class(['grid gap-10 md:gap-12', 'md:grid-cols-2' => $pairs->count() > 1])>
                            @foreach ($pairs as $pair)
                                <figure>
                                    <div class="grid grid-cols-2 gap-px bg-neutral-200">
                                        <div class="bg-neutral-50">
                                            <p class="px-3 py-2 text-xs font-medium tracking-wide text-neutral-500">قبل</p>
                                            <img src="{{ $pair['before']->url() }}" alt="{{ $pair['before']->alt_text ?? 'قبل التنفيذ - '.$project->title }}" loading="lazy"
                                                class="w-full aspect-[3/4] md:aspect-[4/3] object-cover">
                                        </div>
                                        <div class="bg-neutral-50">
                                            <p class="px-3 py-2 text-xs font-medium tracking-wide text-primary-700">بعد</p>
                                            <img src="{{ $pair['after']->url() }}" alt="{{ $pair['after']->alt_text ?? 'بعد التنفيذ - '.$project->title }}" loading="lazy"
                                                class="w-full aspect-[3/4] md:aspect-[4/3] object-cover">
                                        </div>
                                    </div>
                                    @if ($pair['after']->caption || $pair['before']->caption)
                                        <figcaption class="mt-2 text-xs text-neutral-500">{{ $pair['after']->caption ?? $pair['before']->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($unpairedAfter->isNotEmpty())
                    <div class="mt-12">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">{{ $pairs->isNotEmpty() ? 'المزيد من النتيجة' : 'النتيجة' }}</h3>
                        <div @class(['grid gap-px bg-neutral-200', 'sm:grid-cols-2' => $unpairedAfter->count() > 1, 'lg:grid-cols-3' => $unpairedAfter->count() > 2])>
                            @foreach ($unpairedAfter as $image)
                                <figure class="bg-neutral-50">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? 'بعد التنفيذ - '.$project->title }}" loading="lazy"
                                        class="w-full aspect-[4/3] object-cover">
                                    @if ($image->caption)
                                        <figcaption class="px-3 py-2 text-xs text-neutral-500">{{ $image->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 11. More work nearby - media-first, no card chrome ===== --}}
    @if ($relatedProjects->isNotEmpty())
        <x-public.section tone="surface" width="wide">
            <x-public.section-marker label="مشاريع أخرى في نفس المنطقة" heading class="mb-8" />
            <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($relatedProjects as $related)
                    @php($cover = $related->media->firstWhere('pivot.stage', 'after') ?? $related->media->first())
                    <li>
                        @if ($cover)
                            <img src="{{ $cover->url() }}" alt="{{ $cover->alt_text ?? $related->title }}" loading="lazy"
                                class="w-full aspect-[4/3] object-cover">
                        @endif
                        <a href="{{ $urlResolver->urlForPage($related->page) }}"
                            class="mt-3 block font-medium text-ink-950 hover:text-primary-700 transition-colors">
                            {{ $related->title }}
                        </a>
                        @if ($related->completed_at)
                            <p class="mt-0.5 text-sm text-neutral-500">{{ $related->completed_at->translatedFormat('F Y') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== FAQ - second blocks pass, before the decision ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" />

    {{-- ===== 12. Decision ===== --}}
    <section class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">
                    نتيجة مشابهة لمساحتك؟
                </h2>
                <p class="mt-4 text-ink-200 leading-relaxed">أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر مناسب.</p>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب خدمة مشابهة</x-public.button>

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
