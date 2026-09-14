{{--
    Article Detail - a knowledge page, not a landing page.

    Every other template exists to move a visitor toward a decision
    (Homepage = brand, Service = persuasion, Area = local proof,
    Project = evidence). This one exists to be READ, so the composition
    is a reading column: everything - title, image, body, related links,
    even the closing prompt - lives inside one comfortable measure, and
    nothing breaks out to full width. That single choice is what makes it
    look unlike the four selling pages.

    Body typography wins here. Headlines use the display face; the body
    stays in Plex at a reading size with tall Arabic leading (see the
    `.prose` system in app.css, switched to reading mode via
    width="narrow" on the blocks renderer).

    There is no big CTA in the hero and no CTA per section. A single soft
    prompt sits after the body, once the reader has taken what they came
    for. The sitewide mobile bar still carries the conversion.

    No table of contents by design: the body arrives as editor content
    blocks whose headings live inside rich_text HTML, and parsing that
    here would make this template a second interpreter of block data. If
    editors ever produce genuinely long pieces, a TOC belongs in the
    renderer, not in this page.

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
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <article>
        {{-- ===== Editorial opening - calm, in the reading column ===== --}}
        <header class="bg-white border-b border-neutral-200">
            <x-public.container width="narrow" class="py-10 md:py-16">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />

                @if ($article->category)
                    <p class="text-sm font-medium tracking-wide text-primary-700">{{ $article->category->name }}</p>
                @endif

                <h1 @class([
                    'font-display text-[1.75rem] leading-[1.25] md:text-[2.75rem] md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance',
                    'mt-3' => $article->category,
                ])>
                    {{ $page->title }}
                </h1>

                @if ($article->excerpt)
                    <p class="mt-5 text-lg md:text-xl text-neutral-600 leading-relaxed">{{ $article->excerpt }}</p>
                @endif

                @if ($article->author || $page->published_at)
                    <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-500">
                        @if ($article->author)
                            <span>{{ $article->author->name }}</span>
                        @endif
                        @if ($article->author && $page->published_at)
                            <span aria-hidden="true">·</span>
                        @endif
                        @if ($page->published_at)
                            <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->translatedFormat('j F Y') }}</time>
                        @endif
                    </p>
                @endif

                @if ($article->featuredMedia)
                    <figure class="mt-10">
                        <img
                            src="{{ $article->featuredMedia->url() }}"
                            alt="{{ $article->featuredMedia->alt_text ?? $page->title }}"
                            fetchpriority="high"
                            width="1200"
                            height="675"
                            class="w-full aspect-[16/9] object-cover"
                        >
                        @if ($article->featuredMedia->caption)
                            <figcaption class="mt-2 text-sm text-neutral-500">{{ $article->featuredMedia->caption }}</figcaption>
                        @endif
                    </figure>
                @endif
            </x-public.container>
        </header>

        {{-- ===== The body - reading column, editor blocks, FAQ deferred ===== --}}
        <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" width="narrow" />

        {{-- ===== Contextual navigation - inside the reading column, as a
                 ruled aside, not a card grid. Services and areas this
                 article is genuinely linked to. ===== --}}
        @if ($related->isNotEmpty() || $relatedAreas->isNotEmpty())
            <x-public.container width="narrow" class="pb-14 md:pb-20">
                <aside class="border-t border-neutral-200 pt-8" aria-label="روابط ذات صلة بالمقال">
                    <div class="grid sm:grid-cols-2 gap-x-10 gap-y-8">
                        @if ($related->isNotEmpty())
                            <div>
                                <h2 class="text-sm font-medium tracking-wide text-neutral-500">خدمات ذات صلة</h2>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($related as $service)
                                        <li>
                                            <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                                class="inline-flex items-center gap-2 text-ink-950 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                                                {{ $service->name }}
                                                <x-public.icon name="arrow-start" class="w-3.5 h-3.5 text-neutral-300 rtl:rotate-180" />
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($relatedAreas->isNotEmpty())
                            <div>
                                <h2 class="text-sm font-medium tracking-wide text-neutral-500">مناطق ذات صلة</h2>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($relatedAreas as $area)
                                        <li>
                                            <a href="{{ $urlResolver->urlForPage($area->page) }}"
                                                class="inline-flex items-center gap-2 text-ink-950 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                                                {{ $area->name }}
                                                <x-public.icon name="arrow-start" class="w-3.5 h-3.5 text-neutral-300 rtl:rotate-180" />
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

        {{-- ===== Applied evidence - one real project the controller
                 inferred from this article's own service/area links.
                 One, not a grid: it is an illustration of the article,
                 not a portfolio. ===== --}}
        @if ($leadProject)
            <section class="bg-white border-y border-neutral-200">
                <x-public.container width="narrow" class="py-12 md:py-16">
                    <x-public.section-marker label="مثال من أعمالنا" class="mb-6" />
                    <a href="{{ $urlResolver->urlForPage($leadProject->page) }}" class="group block">
                        @if ($leadProjectCover)
                            <img src="{{ $leadProjectCover->url() }}" alt="{{ $leadProjectCover->alt_text ?? $leadProject->title }}" loading="lazy"
                                class="w-full aspect-[16/9] object-cover">
                        @endif
                        <h2 class="mt-4 font-display text-xl md:text-2xl font-medium text-ink-950 group-hover:text-primary-700 transition-colors">
                            {{ $leadProject->title }}
                        </h2>
                        @if ($leadProject->area?->name || $leadProject->completed_at)
                            <p class="mt-1 text-sm text-neutral-500">
                                {{ collect([$leadProject->area?->name, $leadProject->completed_at?->translatedFormat('F Y')])->filter()->implode(' · ') }}
                            </p>
                        @endif
                    </a>
                </x-public.container>
            </section>
        @endif

        {{-- ===== Keep reading - same category, as an editorial list ===== --}}
        @if ($relatedArticles->isNotEmpty())
            <x-public.container width="narrow" class="py-12 md:py-16">
                <x-public.section-marker label="اقرأ أيضًا" heading class="mb-4" />
                <ul>
                    @foreach ($relatedArticles as $relatedArticle)
                        <li class="border-b border-neutral-200">
                            <a href="{{ $urlResolver->urlForPage($relatedArticle->page) }}" class="group block py-4">
                                <span class="block font-display text-lg font-medium text-ink-950 group-hover:text-primary-700 transition-colors text-balance">
                                    {{ $relatedArticle->title }}
                                </span>
                                @if ($relatedArticle->category?->name || $relatedArticle->page?->published_at)
                                    <span class="mt-1 block text-sm text-neutral-500">
                                        {{ collect([$relatedArticle->category?->name, $relatedArticle->page?->published_at?->translatedFormat('j F Y')])->filter()->implode(' · ') }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-public.container>
        @endif

        {{-- ===== FAQ - second blocks pass, reading width ===== --}}
        <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

        {{-- ===== A soft prompt, after the reading is done. Deliberately
                 NOT the navy band the selling pages close with - a ruled
                 note in the same column, with one blue action. ===== --}}
        <x-public.container width="narrow" class="pb-16 md:pb-24">
            <div class="border-t border-b border-neutral-200 py-8 md:py-10">
                <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950 text-balance">
                    تفضّل أن يقوم بذلك فريق متخصص؟
                </h2>
                <p class="mt-2 text-neutral-600 leading-relaxed">أرسل تفاصيل مساحتك وسنعود إليك بعرض سعر.</p>
                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <x-public.button :href="$quoteUrl" variant="cta" icon="check-circle">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                            <x-public.icon name="whatsapp" class="w-4 h-4 text-success-600" /> أو راسلنا على واتساب
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    </article>
</x-layouts.public>
