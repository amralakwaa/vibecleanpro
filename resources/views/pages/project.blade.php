{{--
    Project Detail - a real CASE STUDY, in the V2 system.

    The other templates each have a different job (Homepage = brand,
    Service = persuasion, Area = local proof); this one's job is evidence.
    So the photographs are the content and the copy is the caption, and
    the composition opens like a magazine case study: a compact title
    block with the facts rail on a tinted field, then the result
    photograph as a large framed panel that overlaps the field's edge -
    a fifth hero shape, distinct from the homepage (text over image),
    Service (image beside text) and Area (no image).

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
    $heroIsResult = $heroImage && $afterImages->contains($heroImage);

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
    $hasRelatedPhotos = $relatedProjects->contains(fn ($related) => $related->media->isNotEmpty());

    $hasFaqSection = $faqs->isNotEmpty() && $page->contentBlocks->contains(fn ($block) => $block->type === 'faq' && $block->is_active);
    $surfaceBeforeDecision = match (true) {
        $hasFaqSection => 'text-white',
        $relatedProjects->isNotEmpty() => 'text-primary-50',
        $hasEvidence => 'text-white',
        default => null,
    };
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Case-study opening: title block + facts rail on the tinted
             field; the result photograph overlaps the field's foot ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>

        <x-public.container width="wide" @class(['relative pt-8 md:pt-12', 'pb-8 md:pb-10' => (bool) $heroImage, 'pb-16 md:pb-24' => ! $heroImage])>
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,0.7fr)] lg:gap-16 lg:items-end">
                <div class="max-w-3xl">
                    <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                    <p class="inline-flex items-center gap-2 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700 backdrop-blur-sm">
                        <x-public.icon name="briefcase" class="w-4 h-4" />
                        دراسة حالة
                    </p>

                    <h1 class="mt-5 font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950 text-balance">
                        {{ $page->title }}
                    </h1>

                    @if ($project->summary)
                        <p class="mt-4 text-lg text-neutral-600 leading-relaxed max-w-xl">{{ $project->summary }}</p>
                    @endif

                    <div class="mt-7 flex flex-wrap items-center gap-3" data-hero-cta>
                        <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-600/25">اطلب خدمة مشابهة</x-public.button>
                        @if ($whatsappUrl)
                            <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                        @endif
                    </div>
                </div>

                {{-- Project facts: every entry is a real relation or column.
                     It is also this page's natural navigation - Project ->
                     Service and Project -> Area live here as crawlable
                     links. The area only links when its page is genuinely
                     published (see $linkedArea). --}}
                @if ($hasContext)
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 rounded-2xl bg-white ring-1 ring-ink-950/5 shadow-sm p-5 md:p-6 text-sm reveal">
                        @if ($relatedServices->isNotEmpty())
                            <div class="col-span-2">
                                <dt class="text-neutral-500">{{ $relatedServices->count() > 1 ? 'الخدمات' : 'الخدمة' }}</dt>
                                <dd class="mt-1 font-medium text-ink-950">
                                    @foreach ($relatedServices as $service)
                                        <a href="{{ $urlResolver->urlForPage($service->page) }}" class="inline-flex min-h-11 items-center hover:text-primary-700 underline-offset-4 hover:underline transition-colors">{{ $service->name }}</a>@if (! $loop->last)<span class="text-neutral-400"> · </span>@endif
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if ($project->area)
                            <div>
                                <dt class="text-neutral-500">المنطقة</dt>
                                <dd class="mt-1 font-medium text-ink-950">
                                    @if ($linkedArea)
                                        <a href="{{ $urlResolver->urlForPage($linkedArea->page) }}" class="inline-flex min-h-11 items-center gap-1.5 hover:text-primary-700 underline-offset-4 hover:underline transition-colors"><x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />{{ $project->area->name }}</a>
                                    @else
                                        <span class="inline-flex min-h-11 items-center gap-1.5"><x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />{{ $project->area->name }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($project->completed_at)
                            <div>
                                <dt class="text-neutral-500">تاريخ الإنجاز</dt>
                                <dd class="mt-1 font-medium text-ink-950 inline-flex min-h-11 items-center">
                                    <time datetime="{{ $project->completed_at->toDateString() }}">{{ $project->completed_at->translatedFormat('F Y') }}</time>
                                </dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>
        </x-public.container>

        {{-- The result photograph: a framed panel that straddles the edge
             between the tinted field and the white page beneath. --}}
        @if ($heroImage)
            <div class="relative">
                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-white" aria-hidden="true"></div>
                <x-public.container width="wide" class="relative">
                    <figure class="reveal">
                        <div class="relative overflow-hidden rounded-3xl ring-1 ring-ink-950/10 shadow-2xl shadow-primary-900/20 bg-white">
                            <img
                                src="{{ $heroImage->url() }}" srcset="{{ $heroImage->srcset() }}"
                                alt="{{ $heroImage->alt_text ?? $project->title }}"
                                fetchpriority="high"
                                width="{{ $heroImage->width ?: 1600 }}"
                                height="{{ $heroImage->height ?: 900 }}"
                                class="w-full aspect-[4/5] sm:aspect-[3/2] lg:aspect-[21/9] object-cover"
                            >
                            @if ($heroIsResult)
                                <span class="absolute top-4 start-4 inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-3 py-1 text-xs font-medium tracking-wide text-white shadow-sm">
                                    <x-public.icon name="check" class="w-3 h-3" />
                                    النتيجة
                                </span>
                            @endif
                        </div>
                        @if ($heroImage->caption)
                            <figcaption class="relative mt-3 text-xs text-neutral-500">{{ $heroImage->caption }}</figcaption>
                        @endif
                    </figure>
                </x-public.container>
            </div>
        @else
            <x-public.wave shape="curve" position="bottom" class="text-background" />
        @endif
    </section>

    {{-- ===== 2. Scope of work and any other editor blocks (FAQ deferred) ===== --}}
    <div @class(['bg-white pt-8 md:pt-12' => (bool) $heroImage])>
        <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" />
    </div>

    {{-- ===== 2b. The case study: the problem, the site as we found it,
             what we did, and what changed. Every part is optional and
             renders only when the owner has written it - an empty field is
             silence, never a placeholder or an invented claim. ===== --}}
    @if ($project->hasCaseStudy())
        <section class="bg-white" aria-labelledby="project-case-study">
            <x-public.container width="narrow" class="py-12 md:py-16">
                <h2 id="project-case-study" class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950">تفاصيل التنفيذ</h2>

                <div class="mt-6 space-y-8">
                    @if (filled($project->challenge))
                        <div class="reveal">
                            <h3 class="font-display text-lg font-medium text-ink-950">المشكلة</h3>
                            <p class="mt-2 text-neutral-600 leading-relaxed">{{ $project->challenge }}</p>
                        </div>
                    @endif

                    @if (filled($project->site_condition))
                        <div class="reveal">
                            <h3 class="font-display text-lg font-medium text-ink-950">حالة الموقع قبل التنفيذ</h3>
                            <p class="mt-2 text-neutral-600 leading-relaxed">{{ $project->site_condition }}</p>
                        </div>
                    @endif

                    @if (filled($project->execution_steps))
                        <div class="reveal">
                            <h3 class="font-display text-lg font-medium text-ink-950">خطوات التنفيذ</h3>
                            <ol class="mt-3 space-y-3">
                                @foreach ($project->execution_steps as $index => $step)
                                    @if (filled($step['title'] ?? null))
                                        <li class="flex gap-3">
                                            <span class="flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-primary-50 font-display text-sm font-medium text-primary-700 tabular-nums">{{ $index + 1 }}</span>
                                            <span>
                                                <span class="block font-medium text-ink-950">{{ $step['title'] }}</span>
                                                @if (filled($step['description'] ?? null))
                                                    <span class="mt-1 block text-neutral-600 leading-relaxed">{{ $step['description'] }}</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endif
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    @if (filled($project->outcome))
                        <div class="surface-tint rounded-2xl ring-1 ring-primary-200/60 p-5 md:p-6 reveal">
                            <h3 class="font-display text-lg font-medium text-ink-950">النتيجة</h3>
                            <p class="mt-2 text-neutral-700 leading-relaxed">{{ $project->outcome }}</p>
                        </div>
                    @endif
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 3-6. The evidence chapter: one continuous section, three
             movements. Pairs are the centrepiece; leftover befores and
             afters are shown honestly under their own stage; "during"
             sits between as the process record. ===== --}}
    @if ($hasEvidence)
        <section class="bg-white" aria-labelledby="project-evidence">
            <x-public.container width="wide" class="py-16 md:py-24">
                <div class="max-w-2xl reveal">
                    <p class="text-sm font-medium tracking-wide text-primary-700">الدليل</p>
                    <h2 id="project-evidence" class="mt-2 font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">
                        ما تم قبل العمل، وأثناءه، وبعده
                    </h2>
                </div>

                {{-- Movement A: before-only leftovers (or all befores when
                     nothing exists to pair them with) --}}
                @if ($unpairedBefore->isNotEmpty())
                    <div class="mt-12 reveal">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">قبل</h3>
                        <div @class(['grid gap-4 sm:grid-cols-2', 'lg:grid-cols-3' => $unpairedBefore->count() > 2])>
                            @foreach ($unpairedBefore as $image)
                                <figure class="relative overflow-hidden rounded-2xl ring-1 ring-ink-950/5 bg-neutral-100">
                                    <img src="{{ $image->url() }}" srcset="{{ $image->srcset() }}" alt="{{ $image->alt_text ?? 'قبل التنفيذ - '.$project->title }}" loading="lazy"
                                        width="{{ $image->width ?: 800 }}" height="{{ $image->height ?: 600 }}"
                                        class="w-full aspect-[4/3] object-cover">
                                    <span class="absolute top-3 start-3 inline-flex items-center rounded-full bg-ink-950/70 px-3 py-1 text-xs font-medium tracking-wide text-white backdrop-blur-sm">قبل</span>
                                    @if ($image->caption)
                                        <figcaption class="px-4 py-2.5 text-xs text-neutral-500 bg-white">{{ $image->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Movement B: the process, as a documentary sequence.
                     Horizontal rail on phones so the sequence reads in
                     order instead of stacking. --}}
                @if ($duringImages->isNotEmpty())
                    <div class="mt-12 reveal">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">أثناء العمل</h3>
                        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:pb-0 sm:grid sm:overflow-visible sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($duringImages as $image)
                                <figure class="relative shrink-0 w-[78vw] sm:w-auto snap-start overflow-hidden rounded-2xl ring-1 ring-ink-950/5 bg-neutral-100">
                                    <img src="{{ $image->url() }}" srcset="{{ $image->srcset() }}" alt="{{ $image->alt_text ?? 'أثناء التنفيذ - '.$project->title }}" loading="lazy"
                                        width="{{ $image->width ?: 800 }}" height="{{ $image->height ?: 600 }}"
                                        class="w-full aspect-[4/3] object-cover">
                                    <span class="absolute top-3 start-3 inline-flex items-center rounded-full bg-white/90 text-ink-950 px-3 py-1 text-xs font-medium tracking-wide backdrop-blur-sm">{{ $loop->iteration }} / {{ $duringImages->count() }}</span>
                                    @if ($image->caption)
                                        <figcaption class="px-4 py-2.5 text-xs text-neutral-500 bg-white">{{ $image->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Movement C: the result. Genuine before/after pairs when
                     both stages exist, in the homepage's evidence language;
                     otherwise the after photographs on their own, never
                     given an invented "before". --}}
                @if ($pairs->isNotEmpty())
                    <div class="mt-12 reveal">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">قبل وبعد</h3>
                        <div @class(['grid gap-8 md:gap-10', 'md:grid-cols-2' => $pairs->count() > 1])>
                            @foreach ($pairs as $pair)
                                <figure>
                                    <div class="relative rounded-2xl overflow-hidden ring-1 ring-ink-950/5 shadow-sm">
                                        <div class="grid grid-cols-2 gap-0.5 bg-white">
                                            <div class="relative bg-neutral-100">
                                                <img src="{{ $pair['before']->url() }}" alt="{{ $pair['before']->alt_text ?? 'قبل التنفيذ - '.$project->title }}" loading="lazy"
                                                    width="{{ $pair['before']->width ?: 800 }}" height="{{ $pair['before']->height ?: 600 }}"
                                                    class="w-full aspect-[3/4] md:aspect-[4/3] object-cover">
                                                <span class="absolute top-3 start-3 inline-flex items-center rounded-full bg-ink-950/70 px-3 py-1 text-xs font-medium tracking-wide text-white backdrop-blur-sm">قبل</span>
                                            </div>
                                            <div class="relative bg-neutral-100">
                                                <img src="{{ $pair['after']->url() }}" alt="{{ $pair['after']->alt_text ?? 'بعد التنفيذ - '.$project->title }}" loading="lazy"
                                                    width="{{ $pair['after']->width ?: 800 }}" height="{{ $pair['after']->height ?: 600 }}"
                                                    class="w-full aspect-[3/4] md:aspect-[4/3] object-cover">
                                                <span class="absolute top-3 start-3 inline-flex items-center gap-1 rounded-full bg-primary-600 px-3 py-1 text-xs font-medium tracking-wide text-white shadow-sm">
                                                    <x-public.icon name="check" class="w-3 h-3" />
                                                    بعد
                                                </span>
                                            </div>
                                        </div>
                                        <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white text-primary-700 shadow-md ring-1 ring-ink-950/5" aria-hidden="true">
                                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                                        </span>
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
                    <div class="mt-12 reveal">
                        <h3 class="text-sm font-medium tracking-wide text-neutral-500 mb-4">{{ $pairs->isNotEmpty() ? 'المزيد من النتيجة' : 'النتيجة' }}</h3>
                        <div @class(['grid gap-4 sm:grid-cols-2', 'lg:grid-cols-3' => $unpairedAfter->count() > 2])>
                            @foreach ($unpairedAfter as $image)
                                <figure class="relative overflow-hidden rounded-2xl ring-1 ring-ink-950/5 bg-neutral-100">
                                    <img src="{{ $image->url() }}" srcset="{{ $image->srcset() }}" alt="{{ $image->alt_text ?? 'بعد التنفيذ - '.$project->title }}" loading="lazy"
                                        width="{{ $image->width ?: 800 }}" height="{{ $image->height ?: 600 }}"
                                        class="w-full aspect-[4/3] object-cover">
                                    <span class="absolute top-3 start-3 inline-flex items-center gap-1 rounded-full bg-primary-600 px-3 py-1 text-xs font-medium tracking-wide text-white shadow-sm">
                                        <x-public.icon name="check" class="w-3 h-3" />
                                        بعد
                                    </span>
                                    @if ($image->caption)
                                        <figcaption class="px-4 py-2.5 text-xs text-neutral-500 bg-white">{{ $image->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-public.container>
        </section>
    @endif

    {{-- ===== 7. More work nearby - photo tiles when real, references when not ===== --}}
    @if ($relatedProjects->isNotEmpty())
        <section class="surface-tint relative overflow-hidden" aria-labelledby="project-related">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-700">أعمال قريبة</p>
                        <h2 id="project-related" class="mt-2 font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">مشاريع أخرى في نفس المنطقة</h2>
                    </div>
                    @if ($linkedArea)
                        <a href="{{ $urlResolver->urlForPage($linkedArea->page) }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                            صفحة المنطقة
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    @endif
                </div>
                <ul @class(['mt-8 reveal', 'grid sm:grid-cols-2 lg:grid-cols-3 gap-5' => $hasRelatedPhotos, 'flex flex-wrap gap-2.5' => ! $hasRelatedPhotos])>
                    @foreach ($relatedProjects as $related)
                        @php($cover = $related->media->firstWhere('pivot.stage', 'after') ?? $related->media->first())
                        <li>
                            @if ($cover)
                                <a href="{{ $urlResolver->urlForPage($related->page) }}"
                                    class="group relative isolate flex flex-col justify-end overflow-hidden rounded-2xl text-white aspect-[4/3] shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300">
                                    <img src="{{ $cover->url() }}" srcset="{{ $cover->srcset() }}" alt="{{ $cover->alt_text ?? $related->title }}" loading="lazy"
                                        width="{{ $cover->width ?: 800 }}" height="{{ $cover->height ?: 600 }}"
                                        class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                                    <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                                    <div class="p-5">
                                        <p class="font-display text-lg font-medium tracking-tight text-white">{{ $related->title }}</p>
                                        @if ($related->completed_at)
                                            <p class="mt-1 text-sm text-white/80">{{ $related->completed_at->translatedFormat('F Y') }}</p>
                                        @endif
                                    </div>
                                </a>
                            @elseif ($hasRelatedPhotos)
                                <a href="{{ $urlResolver->urlForPage($related->page) }}"
                                    class="group flex items-center justify-between gap-4 rounded-2xl bg-white ring-1 ring-ink-950/5 px-5 py-4 min-h-14 h-full text-ink-950 hover:ring-primary-200 transition-[box-shadow,ring-color]">
                                    <span>
                                        <span class="block font-medium group-hover:text-primary-700 transition-colors">{{ $related->title }}</span>
                                        @if ($related->completed_at)
                                            <span class="block mt-0.5 text-sm text-neutral-500">{{ $related->completed_at->translatedFormat('F Y') }}</span>
                                        @endif
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                </a>
                            @else
                                <a href="{{ $urlResolver->urlForPage($related->page) }}"
                                    class="group inline-flex items-center gap-2 min-h-11 rounded-full bg-white ring-1 ring-primary-200/70 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                    <x-public.icon name="briefcase" class="w-4 h-4 text-primary-600" />
                                    {{ $related->title }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-public.container>
        </section>
    @endif

    {{-- ===== FAQ - second blocks pass, before the decision ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" />

    {{-- ===== 8. Decision ===== --}}
    <section class="surface-atmos relative isolate overflow-hidden text-white">
        @if ($surfaceBeforeDecision)
            <x-public.wave shape="soft" position="top" :class="$surfaceBeforeDecision" />
        @endif
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" @class(['pb-16 md:pb-24', 'pt-28 md:pt-36' => (bool) $surfaceBeforeDecision, 'pt-16 md:pt-24' => ! $surfaceBeforeDecision])>
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-center reveal">
                <div>
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">
                        نتيجة مشابهة لمساحتك؟
                    </h2>
                    <p class="mt-4 text-lg text-white/85 leading-relaxed max-w-xl">أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر مناسب. لا يتم الدفع عبر الموقع.</p>
                </div>
                <div class="flex flex-col items-start gap-3 lg:items-stretch">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40 lg:justify-center">اطلب خدمة مشابهة</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp" class="lg:justify-center">تواصل عبر واتساب</x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
