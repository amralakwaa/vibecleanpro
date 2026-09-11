@props(['article', 'url', 'categoryName' => null])

<x-public.card :padded="false" class="relative overflow-hidden flex flex-col h-full">
    <div class="aspect-[16/9] bg-neutral-100 overflow-hidden">
        @if ($article->featuredMedia)
            <img src="{{ $article->featuredMedia->url() }}" alt="{{ $article->featuredMedia->alt_text }}"
                loading="lazy" class="w-full h-full object-cover" width="480" height="270">
        @else
            <div class="w-full h-full flex items-center justify-center text-primary-300">
                <x-public.icon name="mail" class="w-9 h-9" />
            </div>
        @endif
    </div>

    <div class="p-5">
        @if ($categoryName)
            <p class="text-xs font-semibold text-primary-600 mb-1">{{ $categoryName }}</p>
        @endif

        <h3 class="font-semibold text-neutral-900">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $article->title }}
            </a>
        </h3>

        @if ($article->excerpt)
            <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed line-clamp-2">{{ $article->excerpt }}</p>
        @endif
    </div>
</x-public.card>
