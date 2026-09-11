{{--
    Homepage before/after proof unit: title + real meta (area/service, only
    when the data exists - never invented) wrapped around the existing
    before-after.blade.php image pair. Composed as its own component
    because the homepage repeats this 2-3 times, unlike project.blade.php's
    single detail-page usage (see the Phase 3 report).
--}}
@props(['project', 'url', 'before', 'after', 'areaName' => null, 'serviceName' => null])

<x-public.card>
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if ($serviceName)
            <x-public.badge tone="primary">{{ $serviceName }}</x-public.badge>
        @endif
        @if ($areaName)
            <x-public.badge tone="neutral">
                <x-public.icon name="map-pin" class="w-3.5 h-3.5" />
                {{ $areaName }}
            </x-public.badge>
        @endif
    </div>

    <x-public.before-after :before="$before" :after="$after" />

    <h3 class="mt-4 font-semibold text-primary-900">
        <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">{{ $project->title }}</a>
    </h3>
</x-public.card>
