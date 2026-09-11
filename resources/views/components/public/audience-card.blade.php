{{--
    Large B2C/B2B routing card ("كيف نخدمك؟"). Purely navigational - no
    CMS data, since the site has no audience/segment field on Service to
    query against (see the Phase 3 report). One clickable target via the
    stretched-link pattern, same as ServiceCard/AreaCard/ProjectCard.
--}}
@props(['icon', 'title', 'description', 'url'])

<x-public.card :padded="false" class="relative flex flex-col items-start gap-4 p-8">
    <span class="w-14 h-14 rounded-2xl bg-primary-50 text-primary-700 flex items-center justify-center">
        <x-public.icon :name="$icon" class="w-7 h-7" />
    </span>

    <div>
        <h3 class="text-lg font-semibold text-primary-900">
            <a href="{{ $url }}" class="hover:text-primary-700 transition-colors">
                <span class="absolute inset-0"></span>
                {{ $title }}
            </a>
        </h3>
        <p class="mt-2 text-neutral-600 leading-relaxed">{{ $description }}</p>
    </div>

    <span class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700">
        اكتشف المزيد
        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
    </span>
</x-public.card>
