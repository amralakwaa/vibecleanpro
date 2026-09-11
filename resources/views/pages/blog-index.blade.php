@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="wide" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">المدونة</h1>
        <p class="mt-3 text-neutral-600 max-w-2xl">مقالات ونصائح حول التنظيف المنزلي والتجاري.</p>
    </x-public.section>

    <x-public.section width="wide" class="!pt-0">
        @if ($featured || $articles->isNotEmpty())
            @if ($featured)
                <div class="mb-10 max-w-xl">
                    <x-public.badge tone="accent" class="mb-3">أحدث مقال</x-public.badge>
                    <x-public.article-card :article="$featured" :url="$urlResolver->urlForPage($featured->page)" :category-name="$featured->category?->name" />
                </div>
            @endif

            @if ($articles->isNotEmpty())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($articles as $article)
                        <x-public.article-card :article="$article" :url="$urlResolver->urlForPage($article->page)" :category-name="$article->category?->name" />
                    @endforeach
                </div>
            @endif

            <x-public.pagination :paginator="$articles" />
        @else
            <x-public.empty-state icon="mail" title="لا توجد مقالات منشورة حاليًا" />
        @endif
    </x-public.section>
</x-layouts.public>
