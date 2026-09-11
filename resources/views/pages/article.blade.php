@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في الاستفسار حول موضوع: '.$page->title);
    $phoneUrl = $businessProfile?->phoneUrl();
    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.hero :heading="$page->title" :subheading="$article->excerpt" :image="$article->featuredMedia" :breadcrumbs="$seo->breadcrumbs">
        <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-primary-100">
            @if ($article->category)
                <x-public.badge tone="accent">{{ $article->category->name }}</x-public.badge>
            @endif
            @if ($page->published_at)
                <span class="inline-flex items-center gap-1.5"><x-public.icon name="clock" class="w-4 h-4" /> {{ $page->published_at->translatedFormat('j F Y') }}</span>
            @endif
            @if ($article->author)
                <span>{{ $article->author->name }}</span>
            @endif
        </div>
    </x-public.hero>

    <x-public.blocks :blocks="$page->contentBlocks" :faqs="$faqs" :related="$related" related-item-type="service" />

    @if ($relatedAreas->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header title="مناطق ذات صلة" align="center" class="mb-8" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($relatedAreas as $area)
                    <x-public.area-card :area="$area" :url="$urlResolver->urlForPage($area->page)" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($relatedProjects->isNotEmpty())
        <x-public.section>
            <x-public.section-header title="مشاريع مرتبطة بالموضوع" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($relatedProjects as $project)
                    <x-public.project-card :project="$project" :url="$urlResolver->urlForPage($project->page)"
                        :image="$project->media->firstWhere('pivot.stage', 'after') ?? $project->media->first()"
                        :area-name="$project->area?->name" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    @if ($relatedArticles->isNotEmpty())
        <x-public.section tone="surface">
            <x-public.section-header title="مقالات ذات صلة" align="center" class="mb-10" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($relatedArticles as $relatedArticle)
                    <x-public.article-card :article="$relatedArticle" :url="$urlResolver->urlForPage($relatedArticle->page)" :category-name="$relatedArticle->category?->name" />
                @endforeach
            </div>
        </x-public.section>
    @endif

    <x-public.section>
        <x-public.cta title="هل تحتاج مساعدة في هذا الأمر؟" description="فريقنا جاهز لمساعدتك." :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
    </x-public.section>
</x-layouts.public>
