{{--
    Article Detail - Reading / Expertise, in the V2 system.

    Every other template exists to move a visitor toward a decision
    (Homepage = brand, Service = persuasion, Area = local proof,
    Project = evidence). This one exists to be READ, so the composition
    is a reading column: everything - title, image, body, related links,
    even the closing prompt - lives inside one comfortable measure, and
    nothing breaks out to full width. That single choice is what makes it
    look unlike the selling pages.

    V2 touches the frame, not the text: a light tinted opening with the
    category pill, the real date and a reading time computed from the
    body, the photograph as a framed panel that overlaps the field's
    foot, a tinted aside for the article's real service/area links, one
    photographed project as an illustration, "keep reading" rows, and a
    soft tinted prompt at the end - never the navy band the selling pages
    close with. Body typography wins: the `.prose` reading system is
    untouched.

    No table of contents by design: the body arrives as editor content
    blocks whose headings live inside rich_text HTML, and parsing that
    here would make this template a second interpreter of block data.

    Every related list is real relational data (article_service,
    article_area, the topical project inference the controller already
    performs, and same-category articles) and disappears when empty.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quoteUrl = route('public.quote', array_filter(['service' => $related->first()?->id]));
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، قرأت مقال: '.$page->title.' وأرغب في الاستفسار');

    $leadProject = $relatedProjects->first();
    $leadProjectCover = $leadProject?->media->firstWhere('pivot.stage', 'after') ?? $leadProject?->media->first();

    // The estimate counts the rich_text body only (excerpt, FAQ answers and
    // structured blocks are not included), so it is stated as approximate.
    $readingLabel = match (true) {
        $readingMinutes === null => null,
        $readingMinutes === 1 => 'حوالي دقيقة قراءة',
        $readingMinutes === 2 => 'حوالي دقيقتين قراءة',
        $readingMinutes <= 10 => 'حوالي '.$readingMinutes.' دقائق قراءة',
        default => 'حوالي '.$readingMinutes.' دقيقة قراءة',
    };
    $meta = collect([$article->author?->name, $page->published_at?->translatedFormat('j F Y'), $readingLabel])->filter()->values();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <article>
        {{-- ===== 1. Editorial opening - tinted field, reading column ===== --}}
        <header class="surface-tint relative isolate overflow-hidden">
            <div class="glow-primary absolute -top-24 -end-24 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
            <x-public.container width="narrow" @class(['relative pt-8 md:pt-12', 'pb-8 md:pb-10' => (bool) $article->featuredMedia, 'pb-14 md:pb-20' => ! $article->featuredMedia])>
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                @if ($article->category)
                    <p class="inline-flex items-center rounded-full bg-white/80 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700 backdrop-blur-sm">{{ $article->category->name }}</p>
                @endif

                <h1 @class([
                    'font-display text-[1.9rem] leading-[1.22] md:text-[2.75rem] md:leading-[1.12] font-medium tracking-tight text-ink-950 text-balance',
                    'mt-5' => $article->category,
                ])>
                    {{ $page->title }}
                </h1>

                @if ($article->excerpt)
                    <p class="mt-5 text-lg md:text-xl text-neutral-600 leading-relaxed">{{ $article->excerpt }}</p>
                @endif

                @if ($meta->isNotEmpty())
                    <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-600">
                        @foreach ($meta as $item)
                            @if (! $loop->first)<span aria-hidden="true" class="text-neutral-400">·</span>@endif
                            @if ($item === $page->published_at?->translatedFormat('j F Y'))
                                <time datetime="{{ $page->published_at->toDateString() }}">{{ $item }}</time>
                            @else
                                <span>{{ $item }}</span>
                            @endif
                        @endforeach
                    </p>
                @endif
            </x-public.container>

            {{-- The photograph as a framed panel straddling the field's foot. --}}
            @if ($article->featuredMedia)
                <div class="relative">
                    <div class="absolute inset-x-0 bottom-0 h-1/2 bg-background" aria-hidden="true"></div>
                    <x-public.container width="narrow" class="relative">
                        <figure class="reveal">
                            <div class="overflow-hidden rounded-3xl ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/15 bg-white">
                                <img
                                    src="{{ $article->featuredMedia->url() }}" srcset="{{ $article->featuredMedia->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                                    alt="{{ $article->featuredMedia->alt_text ?? $page->title }}"
                                    fetchpriority="high"
                                    width="{{ $article->featuredMedia->width ?: 1200 }}"
                                    height="{{ $article->featuredMedia->height ?: 675 }}"
                                    class="w-full aspect-[16/9] object-cover"
                                >
                            </div>
                            @if ($article->featuredMedia->caption)
                                <figcaption class="mt-3 text-sm text-neutral-500">{{ $article->featuredMedia->caption }}</figcaption>
                            @endif
                        </figure>
                    </x-public.container>
                </div>
            @else
                <x-public.wave shape="curve" position="bottom" class="text-background" />
            @endif
        </header>

        {{-- ===== 2. The body - reading column, editor blocks, FAQ deferred ===== --}}
        <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" width="narrow" />

        {{-- ===== 3. Contextual navigation - a tinted aside in the reading
                 column: services and areas this article is genuinely
                 linked to, each a crawlable link. ===== --}}
        @if ($related->isNotEmpty() || $relatedAreas->isNotEmpty())
            <x-public.container width="narrow" class="pb-12 md:pb-16">
                <aside class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-6 md:p-8 reveal" aria-label="روابط ذات صلة بالمقال">
                    <div class="glow-primary absolute -top-16 -end-16 w-48 h-48 opacity-60" aria-hidden="true"></div>
                    <div class="relative grid sm:grid-cols-2 gap-x-10 gap-y-6">
                        @if ($related->isNotEmpty())
                            <div>
                                <h2 class="text-sm font-medium tracking-wide text-primary-700">خدمات ذات صلة</h2>
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($related as $service)
                                        <li>
                                            <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                                class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white ring-1 ring-primary-200/70 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                                {{ $service->name }}
                                                <x-public.icon name="arrow-start" class="w-3.5 h-3.5 text-primary-600 rtl:rotate-180" />
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($relatedAreas->isNotEmpty())
                            <div>
                                <h2 class="text-sm font-medium tracking-wide text-primary-700">مناطق ذات صلة</h2>
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($relatedAreas as $area)
                                        <li>
                                            <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                                class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white ring-1 ring-primary-200/70 px-4 text-sm font-medium text-ink-950 transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                                <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />
                                                {{ $area->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </aside>
            </x-public.container>
        @endif

        {{-- ===== 4. Applied evidence - one real project the controller
                 inferred from this article's own service/area links.
                 One, not a grid: it illustrates the article. ===== --}}
        @if ($leadProject)
            <section class="bg-white" aria-labelledby="article-project">
                <x-public.container width="narrow" class="py-12 md:py-16">
                    <h2 id="article-project" class="text-sm font-medium tracking-wide text-primary-700">مثال من أعمالنا</h2>
                    @if ($leadProjectCover)
                        <a href="{{ $urlResolver->urlForPage($leadProject->page) }}"
                            class="group relative isolate mt-4 flex flex-col justify-end overflow-hidden rounded-3xl text-white aspect-[16/9] shadow-sm hover:shadow-xl hover:shadow-primary-900/15 transition-shadow duration-300 reveal">
                            <img src="{{ $leadProjectCover->url() }}" srcset="{{ $leadProjectCover->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" alt="{{ $leadProjectCover->alt_text ?? $leadProject->title }}" loading="lazy"
                                width="{{ $leadProjectCover->width ?: 1200 }}" height="{{ $leadProjectCover->height ?: 675 }}"
                                class="tile-media absolute inset-0 -z-20 w-full h-full object-cover">
                            <div class="tile-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
                            <div class="p-6 md:p-8">
                                <p class="font-display text-xl md:text-2xl font-medium tracking-tight text-white text-balance">{{ $leadProject->title }}</p>
                                @if ($leadProject->area?->name || $leadProject->completed_at)
                                    <p class="mt-1 text-sm text-white/80">{{ collect([$leadProject->area?->name, $leadProject->completed_at?->translatedFormat('F Y')])->filter()->implode(' · ') }}</p>
                                @endif
                            </div>
                        </a>
                    @else
                        <a href="{{ $urlResolver->urlForPage($leadProject->page) }}"
                            class="group mt-4 flex items-center justify-between gap-4 rounded-2xl bg-neutral-50 ring-1 ring-ink-950/5 px-5 py-4 min-h-14 text-ink-950 hover:ring-primary-200 transition-[box-shadow,ring-color] reveal">
                            <span>
                                <span class="block font-display text-lg font-medium group-hover:text-primary-700 transition-colors">{{ $leadProject->title }}</span>
                                @if ($leadProject->area?->name || $leadProject->completed_at)
                                    <span class="block mt-0.5 text-sm text-neutral-500">{{ collect([$leadProject->area?->name, $leadProject->completed_at?->translatedFormat('F Y')])->filter()->implode(' · ') }}</span>
                                @endif
                            </span>
                            <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                        </a>
                    @endif
                </x-public.container>
            </section>
        @endif

        {{-- ===== 5. Keep reading - same category, as editorial rows ===== --}}
        @if ($relatedArticles->isNotEmpty())
            <x-public.container width="narrow" class="py-12 md:py-16">
                <h2 class="text-sm font-medium tracking-wide text-neutral-500">اقرأ أيضًا</h2>
                <ul class="mt-4 divide-y divide-neutral-200 border-t border-neutral-200 reveal">
                    @foreach ($relatedArticles as $relatedArticle)
                        <li>
                            <a href="{{ $urlResolver->urlForPage($relatedArticle->page) }}" class="group flex items-center justify-between gap-4 py-4 min-h-14">
                                <span class="min-w-0">
                                    <span class="block font-display text-lg font-medium text-ink-950 group-hover:text-primary-700 transition-colors text-balance">
                                        {{ $relatedArticle->title }}
                                    </span>
                                    @if ($relatedArticle->category?->name || $relatedArticle->page?->published_at)
                                        <span class="mt-1 block text-sm text-neutral-500">
                                            {{ collect([$relatedArticle->category?->name, $relatedArticle->page?->published_at?->translatedFormat('j F Y')])->filter()->implode(' · ') }}
                                        </span>
                                    @endif
                                </span>
                                <x-public.icon name="arrow-start" class="w-4 h-4 text-primary-600 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-public.container>
        @endif

        {{-- ===== 6. FAQ - second blocks pass, reading width ===== --}}
        <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

        {{-- ===== 7. A soft prompt, after the reading is done. Deliberately
                 NOT the navy band the selling pages close with - a tinted
                 note in the same column, with one blue action. ===== --}}
        <x-public.container width="narrow" class="pb-16 md:pb-24">
            <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-7 md:p-10 reveal">
                <div class="glow-primary absolute -bottom-16 -start-16 w-56 h-56 opacity-60" aria-hidden="true"></div>
                <h2 class="relative font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950 text-balance">
                    تفضّل أن يقوم بذلك فريق متخصص؟
                </h2>
                <p class="relative mt-2 text-neutral-600 leading-relaxed">أرسل تفاصيل مساحتك وسنعود إليك بعرض سعر.</p>
                <div class="relative mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <x-public.button :href="$quoteUrl" variant="cta" icon="check-circle">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                            <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> أو راسلنا على واتساب
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    </article>
</x-layouts.public>
