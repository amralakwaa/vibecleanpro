{{--
    Performance-safe, crawlable video evidence card.

    The <video> element is in the page from the start, so search engines
    can see the clip, but with preload="none" and no poster attribute it
    downloads nothing until the viewer presses play. What the viewer sees
    first is the overlay: a small lazy-loaded poster image and a play
    button. Pressing it hides the overlay and starts the clip in place.
    The clip stays off the critical path (no effect on LCP/initial load).

    Each card also states its VideoObject (file, poster, upload date and
    duration from the file itself) - see App\Support\Media\VideoEvidence.

    Portrait clips (real 9:16 field videos). Self-hosted under
    storage/app/public/media/videos; posters are webp beside them.
--}}
@props(['slug', 'caption' => null])

@php
    // Root-relative so the URL is correct on any host/port and never bakes a
    // wrong absolute host into the cached homepage HTML.
    $videoUrl = '/storage/media/videos/'.$slug.'.mp4';
    $posterUrl = '/storage/media/videos/posters/'.$slug.'.webp';
    $videoSchema = \App\Support\Media\VideoEvidence::schema($slug, $caption);
@endphp

<figure x-data="{ playing: false }" class="group relative overflow-hidden rounded-2xl bg-ink-950 shadow-sm">
    <video x-ref="clip" src="{{ $videoUrl }}" preload="none" controls playsinline
        width="508" height="900" title="{{ $caption }}"
        style="aspect-ratio: 508 / 900" class="block w-full h-auto bg-black"></video>

    <button type="button" x-show="!playing" @click="playing = true; $nextTick(() => $refs.clip.play())"
        class="absolute inset-0 block w-full h-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
        aria-label="تشغيل الفيديو{{ $caption ? ' — '.$caption : '' }}">
        <img src="{{ $posterUrl }}" loading="lazy" decoding="async" alt="{{ $caption }}"
            width="508" height="900"
            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
        <span aria-hidden="true" class="absolute inset-0 flex items-center justify-center bg-ink-950/15 transition-colors group-hover:bg-ink-950/25">
            <span class="flex items-center justify-center w-16 h-16 rounded-full bg-white/90 text-primary-700 shadow-lg transition-colors group-hover:bg-white">
                <x-public.icon name="play" class="w-7 h-7 ms-1" />
            </span>
        </span>
        @if ($caption)
            <figcaption class="absolute inset-x-0 bottom-0 p-3 text-start text-sm font-medium text-white bg-gradient-to-t from-ink-950/85 to-transparent">
                {{ $caption }}
            </figcaption>
        @endif
    </button>

    @if ($videoSchema)
        <script type="application/ld+json">{!! json_encode($videoSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif
</figure>
