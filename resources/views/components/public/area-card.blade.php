@props(['area', 'url', 'servicesCount' => null])

<x-public.card class="relative flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
        <x-public.icon name="map-pin" class="w-6 h-6" />
    </div>

    <div class="grow">
        <h3 class="font-semibold text-neutral-900">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $area->name }}
            </a>
        </h3>

        @if ($servicesCount !== null)
            <p class="mt-0.5 text-sm text-neutral-500">{{ $servicesCount }} خدمة متاحة في هذه المنطقة</p>
        @endif
    </div>

    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 rtl:rotate-180 shrink-0" />
</x-public.card>
