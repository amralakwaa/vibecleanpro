{{--
    The proof unit: a real project's before/after pair, presented so the
    comparison is understood before a word is read. The two photographs
    touch inside one rounded frame, split by a hairline with a small
    "transformation" badge on it; each photo carries its own label pinned
    to the picture - "قبل" muted, "بعد" in the brand blue - so the eye
    never has to hunt for which is which. No duotone, no filter, no
    decoration on the photographs themselves.

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

@php
    $meta = collect([$serviceName, $areaName, $project->completed_at?->translatedFormat('F Y')])->filter();
@endphp

<figure {{ $attributes->merge(['class' => 'group']) }}>
    <a href="{{ $url }}" class="relative block rounded-2xl overflow-hidden ring-1 ring-ink-950/5 shadow-sm transition-shadow duration-300 hover:shadow-xl hover:shadow-primary-900/10" aria-label="{{ $project->title }}">
        {{-- gap over a white background paints the hairline between the
             two photos without either image needing a border of its own. --}}
        <div class="grid grid-cols-2 gap-0.5 bg-white">
            <div class="relative bg-neutral-100">
                <img
                    src="{{ $before->url() }}" srcset="{{ $before->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                    alt="{{ $before->alt_text ?? 'قبل التنفيذ - '.$project->title }}"
                    loading="lazy"
                    width="{{ $before->width ?: 800 }}"
                    height="{{ $before->height ?: 600 }}"
                    class="w-full aspect-[3/4] md:aspect-[4/3] object-cover"
                >
                <span class="absolute top-3 start-3 inline-flex items-center rounded-full bg-ink-950/70 px-3 py-1 text-xs font-medium tracking-wide text-white backdrop-blur-sm">قبل</span>
            </div>

            <div class="relative bg-neutral-100">
                <img
                    src="{{ $after->url() }}" srcset="{{ $after->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                    alt="{{ $after->alt_text ?? 'بعد التنفيذ - '.$project->title }}"
                    loading="lazy"
                    width="{{ $after->width ?: 800 }}"
                    height="{{ $after->height ?: 600 }}"
                    class="w-full aspect-[3/4] md:aspect-[4/3] object-cover"
                >
                <span class="absolute top-3 start-3 inline-flex items-center gap-1 rounded-full bg-primary-600 px-3 py-1 text-xs font-medium tracking-wide text-white shadow-sm">
                    <x-public.icon name="check" class="w-3 h-3" />
                    بعد
                </span>
            </div>
        </div>

        {{-- The transformation badge sits on the hairline: a quiet arrow
             from "before" to "after" in the reading direction. --}}
        <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white text-primary-700 shadow-md ring-1 ring-ink-950/5" aria-hidden="true">
            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
        </span>
    </a>

    <figcaption class="mt-4">
        <a href="{{ $url }}" class="inline-flex items-center min-h-11 font-display text-lg font-medium tracking-tight text-ink-950 underline-offset-4 group-hover:underline group-hover:text-primary-700 transition-colors">
            {{ $project->title }}
        </a>

        @if ($meta->isNotEmpty())
            <ul class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-neutral-500">
                @foreach ($meta as $item)
                    <li class="flex items-center gap-2">
                        @if (! $loop->first)
                            <span class="w-1 h-1 rounded-full bg-neutral-300" aria-hidden="true"></span>
                        @endif
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
        @endif
    </figcaption>
</figure>
