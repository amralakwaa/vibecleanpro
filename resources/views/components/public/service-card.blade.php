{{--
    Service Card - Direction 1 (Deep Petrol Ink). One clickable target (the
    whole card, via the stretched-link pattern) with a button-styled
    element at the bottom for visual clarity of the action - not a second,
    separately-focusable link, which would be invalid nested-link markup.
    Uses only existing Service data (short_description as the value
    statement) - no new fields.
--}}
@props(['service', 'url'])

<x-public.card :padded="false" class="relative overflow-hidden flex flex-col h-full border-neutral-200/80">
    <div class="aspect-[4/3] bg-neutral-100 overflow-hidden">
        @if ($service->featuredMedia)
            <img src="{{ $service->featuredMedia->url() }}" srcset="{{ $service->featuredMedia->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" alt="{{ $service->featuredMedia->alt_text }}"
                loading="lazy" class="w-full h-full object-cover" width="480" height="360">
        @else
            <div class="w-full h-full flex items-center justify-center text-primary-300">
                <x-public.icon name="sparkles" class="w-10 h-10" />
            </div>
        @endif
    </div>

    <div class="p-6 flex flex-col grow">
        @if ($service->is_featured)
            <x-public.badge tone="accent" class="mb-3 self-start">خدمة مميزة</x-public.badge>
        @endif

        <h3 class="font-semibold text-ink-950">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $service->name }}
            </a>
        </h3>

        @if ($service->short_description)
            <p class="mt-2 text-sm text-neutral-600 leading-relaxed line-clamp-2">{{ $service->short_description }}</p>
        @endif

        <span class="mt-5 inline-flex items-center justify-center gap-1.5 rounded-xl border border-primary-200 px-4 py-2.5 text-sm font-medium text-primary-800 self-start">
            اطلب الخدمة
            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
        </span>
    </div>
</x-public.card>
