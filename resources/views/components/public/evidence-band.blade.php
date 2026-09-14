{{--
    The proof unit: a real project's before/after pair, presented with no
    chrome at all - no card, no border, no shadow, no rounded frame, no
    duotone or filter. The two photographs touch, separated only by a
    hairline, so the eye compares them directly instead of reading two
    decorated tiles.

    Mobile is not the desktop layout shrunk: the pair stays side by side
    (the comparison is the entire point and stacking breaks it), but the
    crop turns portrait so each photo still gets real height on a phone.

    Metadata is printed only where it genuinely exists on the project -
    service, area and completion date are each optional and silently
    omitted rather than filled in.
--}}
@props([
    'project',
    'url',
    'before',
    'after',
    'serviceName' => null,
    'areaName' => null,
])

<figure {{ $attributes->merge() }}>
    {{-- gap-px over a neutral background paints the hairline between the
         two photos without either image needing a border of its own. --}}
    <div class="grid grid-cols-2 gap-px bg-neutral-200">
        <div class="bg-neutral-50">
            <figcaption class="px-3 py-2 text-xs font-medium tracking-wide text-neutral-500">قبل</figcaption>
            <img
                src="{{ $before->url() }}"
                alt="{{ $before->alt_text ?? 'قبل التنفيذ - '.$project->title }}"
                loading="lazy"
                class="w-full aspect-[3/4] md:aspect-[4/3] object-cover"
            >
        </div>

        <div class="bg-neutral-50">
            <p class="px-3 py-2 text-xs font-medium tracking-wide text-primary-700">بعد</p>
            <img
                src="{{ $after->url() }}"
                alt="{{ $after->alt_text ?? 'بعد التنفيذ - '.$project->title }}"
                loading="lazy"
                class="w-full aspect-[3/4] md:aspect-[4/3] object-cover"
            >
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <a href="{{ $url }}" class="font-medium text-ink-950 underline-offset-4 hover:underline hover:text-primary-700 transition-colors">
            {{ $project->title }}
        </a>

        @if ($serviceName || $areaName || $project->completed_at)
            <p class="text-sm text-neutral-500">
                {{ collect([$serviceName, $areaName, $project->completed_at?->translatedFormat('F Y')])->filter()->implode(' · ') }}
            </p>
        @endif
    </div>
</figure>
