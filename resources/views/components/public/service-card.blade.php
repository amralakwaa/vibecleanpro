@props(['service', 'url'])

<x-public.card :padded="false" class="relative overflow-hidden flex flex-col h-full">
    <div class="aspect-[4/3] bg-neutral-100 overflow-hidden">
        @if ($service->featuredMedia)
            <img src="{{ $service->featuredMedia->url() }}" alt="{{ $service->featuredMedia->alt_text }}"
                loading="lazy" class="w-full h-full object-cover" width="480" height="360">
        @else
            <div class="w-full h-full flex items-center justify-center text-primary-300">
                <x-public.icon name="sparkles" class="w-10 h-10" />
            </div>
        @endif
    </div>

    <div class="p-5 flex flex-col grow">
        @if ($service->is_featured)
            <x-public.badge tone="accent" class="mb-2 self-start">الأكثر طلبًا</x-public.badge>
        @endif

        <h3 class="font-semibold text-neutral-900">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $service->name }}
            </a>
        </h3>

        @if ($service->short_description)
            <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed line-clamp-2">{{ $service->short_description }}</p>
        @endif

        <span class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary-700">
            التفاصيل
            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
        </span>
    </div>
</x-public.card>
