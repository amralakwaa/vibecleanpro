{{--
    Blog Index - an editorial front page, not a card grid.

    The newest piece leads as a featured spread (wide image, display
    title, excerpt), then the rest run as ruled editorial rows: a small
    image beside category / title / excerpt / date. Two shapes, not one
    repeated card, and both keep the same reading measure the Article
    page uses, so the index reads as the front of the same publication.

    Categories are metadata only in the current architecture (there is
    no category route), so they appear as labels that organise the eye,
    never as links to pages that do not exist. Pagination is unchanged.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">المدونة</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">مقالات ونصائح عملية حول العناية بالمساحات السكنية والتجارية.</p>
        </x-public.container>
    </section>

    @if ($featured || $articles->isNotEmpty())
        {{-- ===== Featured spread ===== --}}
        @if ($featured)
            <x-public.container width="wide" class="py-12 md:py-16">
                <x-public.section-marker label="أحدث مقال" class="mb-8" />
                <a href="{{ $urlResolver->urlForPage($featured->page) }}" class="group grid lg:grid-cols-[1.4fr_1fr] gap-8 lg:gap-12 items-center">
                    @if ($featured->featuredMedia)
                        <img src="{{ $featured->featuredMedia->url() }}" alt="{{ $featured->featuredMedia->alt_text ?? $featured->title }}"
                            width="1200" height="675" fetchpriority="high"
                            class="w-full aspect-[16/9] object-cover">
                    @endif
                    <div>
                        @if ($featured->category)
                            <p class="text-sm font-medium tracking-wide text-primary-700">{{ $featured->category->name }}</p>
                        @endif
                        <h2 @class(['font-display text-2xl md:text-4xl leading-snug md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance group-hover:text-primary-700 transition-colors', 'mt-3' => $featured->category])>
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
                    </div>
                </a>
            </x-public.container>
        @endif

        {{-- ===== Editorial rows ===== --}}
        @if ($articles->isNotEmpty())
            <section @class(['border-t border-neutral-200' => (bool) $featured])>
                <x-public.container width="wide" class="py-12 md:py-16">
                    @if ($featured)
                        <x-public.section-marker label="المزيد من المقالات" heading class="mb-6" />
                    @endif

                    <ul>
                        @foreach ($articles as $article)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $urlResolver->urlForPage($article->page) }}" @class([
                                    'group grid gap-5 lg:gap-8 items-start py-7 md:py-8',
                                    'sm:grid-cols-[200px_minmax(0,1fr)] lg:grid-cols-[260px_minmax(0,1fr)]' => (bool) $article->featuredMedia,
                                ])>
                                    @if ($article->featuredMedia)
                                        <img src="{{ $article->featuredMedia->url() }}" alt="{{ $article->featuredMedia->alt_text ?? $article->title }}" loading="lazy"
                                            width="520" height="390"
                                            class="w-full aspect-[4/3] object-cover">
                                    @endif

                                    <div class="min-w-0">
                                        @if ($article->category)
                                            <p class="text-xs font-medium tracking-wide text-primary-700">{{ $article->category->name }}</p>
                                        @endif
                                        <h2 @class(['font-display text-xl md:text-2xl font-medium leading-snug text-ink-950 text-balance group-hover:text-primary-700 transition-colors', 'mt-2' => $article->category])>
                                            {{ $article->title }}
                                        </h2>
                                        @if ($article->excerpt)
                                            <p class="mt-2 text-neutral-600 leading-relaxed line-clamp-2">{{ $article->excerpt }}</p>
                                        @endif
                                        @if ($article->page?->published_at)
                                            <time datetime="{{ $article->page->published_at->toDateString() }}" class="mt-3 block text-sm text-neutral-500">
                                                {{ $article->page->published_at->translatedFormat('j F Y') }}
                                            </time>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <x-public.pagination :paginator="$articles" />
                </x-public.container>
            </section>
        @endif
    @else
        <x-public.container width="wide" class="py-16">
            <p class="text-neutral-600">لا توجد مقالات منشورة حاليًا.</p>
        </x-public.container>
    @endif
</x-layouts.public>
