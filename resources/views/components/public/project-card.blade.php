@props(['project', 'url', 'image' => null, 'areaName' => null])

<x-public.card :padded="false" class="relative overflow-hidden flex flex-col h-full">
    <div class="aspect-[4/3] bg-neutral-100 overflow-hidden">
        @if ($image)
            <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}" loading="lazy"
                class="w-full h-full object-cover" width="480" height="360">
        @else
            <div class="w-full h-full flex items-center justify-center text-primary-300">
                <x-public.icon name="briefcase" class="w-10 h-10" />
            </div>
        @endif
    </div>

    <div class="p-5">
        @if ($areaName)
            <p class="text-xs font-semibold text-primary-600 mb-1">{{ $areaName }}</p>
        @endif

        <h3 class="font-semibold text-neutral-900">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $project->title }}
            </a>
        </h3>

        @if ($project->summary)
            <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed line-clamp-2">{{ $project->summary }}</p>
        @endif
    </div>
</x-public.card>
