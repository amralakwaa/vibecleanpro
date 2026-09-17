{{--
    Blog Index - Knowledge Discovery, in the V2 system.

    An editorial front page, not a card grid and not a news portal. The
    newest piece leads as a featured spread (framed photograph over a
    navy plate, display title, excerpt, date); the rest run as ruled
    editorial rows - a small rounded photograph beside category / title /
    excerpt / date - so the index reads as the front of one publication
    with two shapes, never one card repeated.

    Categories are metadata only in the current architecture (there is
    no category route), so they appear as a legend of organising labels
    with the real count of published pieces - never as links to pages
    that do not exist. Pagination is unchanged.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);

    $categoryCount = fn (int $count): string => match (true) {
        $count === 1 => 'مقال واحد',
        $count === 2 => 'مقالان',
        $count <= 10 => $count.' مقالات',
        default => $count.' مقالًا',
    };
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Index head + category legend on the tinted field ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-24 -end-24 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="pt-8 pb-12 md:pt-12 md:pb-16">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">المدونة</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">مقالات ونصائح عملية حول العناية بالمساحات السكنية والتجارية.</p>

            @if ($categories->isNotEmpty())
                <ul class="mt-8 flex flex-wrap gap-2.5" aria-label="أقسام المدونة">
                    @foreach ($categories as $category)
                        <li class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-4 text-sm text-ink-950 backdrop-blur-sm">
                            <span class="font-medium">{{ $category->name }}</span>
                            <span class="text-neutral-500">{{ $categoryCount((int) $category->articles_count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-public.container>
    </section>

    @if ($featured || $articles->isNotEmpty())
        {{-- ===== 2. Featured spread - the newest piece ===== --}}
        @if ($featured)
            <section class="bg-white" aria-labelledby="blog-featured">
                <x-public.container width="wide" class="py-12 md:py-16">
                    <p class="text-sm font-medium tracking-wide text-primary-700 reveal">أحدث مقال</p>
                    <a href="{{ $urlResolver->urlForPage($featured->page) }}" @class(['group mt-4 grid gap-8 lg:gap-12 items-center reveal', 'lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]' => (bool) $featured->featuredMedia])>
                        @if ($featured->featuredMedia)
                            <div class="relative">
                                <div class="surface-atmos absolute inset-0 -translate-x-3 translate-y-3 md:-translate-x-5 md:translate-y-5 rounded-3xl -z-10" aria-hidden="true"></div>
                                <div class="relative overflow-hidden rounded-3xl ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/15 bg-white">
                                    <img src="{{ $featured->featuredMedia->url() }}" alt="{{ $featured->featuredMedia->alt_text ?? $featured->title }}"
                                        width="{{ $featured->featuredMedia->width ?: 1200 }}" height="{{ $featured->featuredMedia->height ?: 675 }}" fetchpriority="high"
                                        class="tile-media w-full aspect-[16/9] object-cover">
                                </div>
                            </div>
                        @endif
                        <div>
                            @if ($featured->category)
                                <p class="inline-flex items-center rounded-full bg-primary-50 text-primary-700 px-3 py-1 text-xs font-medium tracking-wide">{{ $featured->category->name }}</p>
                            @endif
                            <h2 id="blog-featured" @class(['font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors', 'mt-3' => $featured->category])>
                                {{ $featured->title }}
                            </h2>
                            @if ($featured->excerpt)
                                <p class="mt-4 text-neutral-600 leading-relaxed">{{ $featured->excerpt }}</p>
                            @endif
                            @if ($featured->page?->published_at)
                                <time datetime="{{ $featured->page->published_at->toDateString() }}" class="mt-5 block text-sm text-neutral-500">
                                    {{ $featured->page->published_at->translatedFormat('j F Y') }}
                                </time>
                            @endif
                            <span class="mt-6 inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 group-hover:underline">
                                اقرأ المقال
                                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                            </span>
                        </div>
                    </a>
                </x-public.container>
            </section>
        @endif

        {{-- ===== 3. Editorial rows ===== --}}
        @if ($articles->isNotEmpty())
            <section @class(['bg-white', 'border-t border-neutral-200' => (bool) $featured]) aria-labelledby="blog-rows">
                <x-public.container width="wide" class="py-12 md:py-16">
                    <h2 id="blog-rows" class="text-sm font-medium tracking-wide text-neutral-500">{{ $featured ? 'المزيد من المقالات' : 'المقالات' }}</h2>

                    <ul class="mt-4 divide-y divide-neutral-200 border-t border-neutral-200">
                        @foreach ($articles as $article)
                            <li class="reveal">
                                <a href="{{ $urlResolver->urlForPage($article->page) }}" @class([
                                    'group grid gap-5 lg:gap-8 items-start py-7 md:py-8',
                                    'sm:grid-cols-[200px_minmax(0,1fr)] lg:grid-cols-[260px_minmax(0,1fr)]' => (bool) $article->featuredMedia,
                                ])>
                                    @if ($article->featuredMedia)
                                        <span class="block overflow-hidden rounded-2xl ring-1 ring-ink-950/5">
                                            <img src="{{ $article->featuredMedia->url() }}" alt="{{ $article->featuredMedia->alt_text ?? $article->title }}" loading="lazy"
                                                width="{{ $article->featuredMedia->width ?: 520 }}" height="{{ $article->featuredMedia->height ?: 390 }}"
                                                class="tile-media w-full aspect-[4/3] object-cover">
                                        </span>
                                    @endif

                                    <span class="block min-w-0">
                                        @if ($article->category)
                                            <span class="block text-xs font-medium tracking-wide text-primary-700">{{ $article->category->name }}</span>
                                        @endif
                                        <span @class(['block font-display text-xl md:text-2xl font-medium leading-snug text-ink-950 text-balance group-hover:text-primary-700 transition-colors', 'mt-2' => $article->category])>
                                            {{ $article->title }}
                                        </span>
                                        @if ($article->excerpt)
                                            <span class="block mt-2 text-neutral-600 leading-relaxed line-clamp-2">{{ $article->excerpt }}</span>
                                        @endif
                                        <span class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-neutral-500">
                                            @if ($article->page?->published_at)
                                                <time datetime="{{ $article->page->published_at->toDateString() }}">{{ $article->page->published_at->translatedFormat('j F Y') }}</time>
                                            @endif
                                            <span class="inline-flex items-center gap-1.5 font-medium text-primary-700">
                                                اقرأ
                                                <x-public.icon name="arrow-start" class="w-3.5 h-3.5 rtl:rotate-180 transition-transform duration-300 group-hover:-translate-x-1" />
                                            </span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <x-public.pagination :paginator="$articles" />
                </x-public.container>
            </section>
        @endif
    @else
        {{-- Pre-launch, customer-facing: honest, not administrative. --}}
        <section class="bg-white">
            <x-public.container width="narrow" class="py-16 md:py-24">
                <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-8 md:p-10 reveal">
                    <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                    <h2 class="relative font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950">نجهّز أول مقالاتنا</h2>
                    <p class="relative mt-3 text-neutral-600 leading-relaxed">نصائح عملية عن العناية بالمساحات في طريقها إلى هنا. إلى ذلك الحين، خدماتنا وأعمالنا متاحة للتصفح.</p>
                    <div class="relative mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                        <a href="{{ route('public.services.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                            تصفح الخدمات
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                </div>
            </x-public.container>
        </section>
    @endif
</x-layouts.public>
