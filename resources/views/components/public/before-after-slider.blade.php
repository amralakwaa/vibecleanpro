@props(['before', 'after', 'title' => ''])

{{--
    Before / After comparison slider.

    It renders only where a genuine pair exists - the caller decides that,
    and the caller is pages/project.blade.php, which pairs the nth before
    with the nth after and never invents a partner. Nothing here creates,
    crops or tints an image.

    Accessibility: the divider is a real <input type="range">, so it works
    with a keyboard and a screen reader announces it. The visual handle is
    decorative and inherits the same value. Without JavaScript the "after"
    image is fully visible and both images remain in the DOM.

    Both photographs keep their own alt text and neither is aria-hidden:
    the "before" frame is half the evidence, not decoration. Clipping it
    visually must not hide it from a screen reader or from image search.

    RTL: the site is Arabic. `inset-inline-start` and logical properties
    keep the reveal direction correct without a separate LTR branch.
--}}

<figure
    x-data="{ position: 50 }"
    class="group relative select-none"
    role="group"
    aria-label="مقارنة قبل وبعد{{ $title ? ' — '.$title : '' }}"
>
    <div class="relative overflow-hidden rounded-2xl ring-1 ring-ink-950/5 shadow-sm bg-neutral-100">
        {{-- Before is the base layer and comes first in the DOM: it is
             the reading order (قبل ثم بعد) and the order the evidence
             tests assert. --}}
        <img
            src="{{ $before->url() }}"
            @if (method_exists($before, 'srcset')) srcset="{{ $before->srcset() }}" @endif
            alt="{{ $before->alt_text ?? 'قبل التنفيذ'.($title ? ' - '.$title : '') }}"
            width="{{ $before->width ?: 1200 }}" height="{{ $before->height ?: 900 }}"
            loading="lazy" decoding="async"
            class="block w-full aspect-[4/3] object-cover"
        >

        {{-- After: revealed from the divider to the end edge. The inner
             image is widened so it stays aligned with the full frame
             instead of squashing as the divider moves. --}}
        <div class="absolute inset-y-0 end-0 overflow-hidden" :style="`inset-inline-start: ${position}%`">
            <img
                src="{{ $after->url() }}"
                @if (method_exists($after, 'srcset')) srcset="{{ $after->srcset() }}" @endif
                alt="{{ $after->alt_text ?? 'بعد التنفيذ'.($title ? ' - '.$title : '') }}"
                width="{{ $after->width ?: 1200 }}" height="{{ $after->height ?: 900 }}"
                loading="lazy" decoding="async"
                class="absolute inset-y-0 end-0 h-full max-w-none object-cover"
                :style="`width: ${position >= 100 ? 100 : 10000 / (100 - position)}%`"
            >
        </div>

        <span class="absolute top-3 start-3 z-10 inline-flex items-center rounded-full bg-ink-950/70 px-3 py-1 text-xs font-medium tracking-wide text-white backdrop-blur-sm">قبل</span>
        <span class="absolute top-3 end-3 z-10 inline-flex items-center gap-1 rounded-full bg-primary-600 px-3 py-1 text-xs font-medium tracking-wide text-white shadow-sm">
            <x-public.icon name="check" class="w-3 h-3" />
            بعد
        </span>

        {{-- Divider line + handle: decorative, driven by the range below --}}
        <div class="pointer-events-none absolute inset-y-0 z-10 w-0.5 bg-white/90 shadow-[0_0_0_1px_rgba(0,0,0,0.08)]" :style="`inset-inline-start: ${position}%`" aria-hidden="true">
            <span class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 rtl:translate-x-1/2 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-primary-700 shadow-md ring-1 ring-ink-950/5 transition-transform group-hover:scale-105">
                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
            </span>
        </div>

        <input
            type="range" min="0" max="100" step="1"
            x-model.number="position"
            class="absolute inset-0 z-20 h-full w-full cursor-ew-resize appearance-none bg-transparent opacity-0 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            aria-label="حرّك للمقارنة بين قبل وبعد"
        >
    </div>

    @if ($after->caption || $before->caption)
        <figcaption class="mt-2 text-xs text-neutral-500">{{ $after->caption ?? $before->caption }}</figcaption>
    @endif
</figure>
