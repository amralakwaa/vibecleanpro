{{--
    Performance-safe video evidence card. The <video> element is NOT in the
    DOM until the viewer clicks play (Alpine x-if), so zero video bytes load on
    page load - only the small lazy-loaded poster image. This keeps the clip off
    the critical path entirely (no effect on LCP/initial load), which matters
    because the public pages are tuned to 97/100 mobile.

    Portrait clips (real 9:16 field videos). Self-hosted under
    storage/app/public/media/videos; posters are webp beside them.
--}}
@props(['slug', 'caption' => null])

@php
    // Root-relative so the URL is correct on any host/port and never bakes a
    // wrong absolute host into the cached homepage HTML.
    $videoUrl = '/storage/media/videos/'.$slug.'.mp4';
    $posterUrl = '/storage/media/videos/posters/'.$slug.'.webp';
@endphp

<figure x-data="{ playing: false }" class="group relative overflow-hidden rounded-2xl bg-ink-950 shadow-sm">
    <button type="button" x-show="!playing" @click="playing = true"
        class="relative block w-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
        aria-label="تشغيل الفيديو{{ $caption ? ' — '.$caption : '' }}">
        <img src="{{ $posterUrl }}" loading="lazy" decoding="async" alt="{{ $caption }}"
            width="508" height="900"
            class="w-full h-auto object-cover transition-transform duration-500 group-hover:scale-[1.03]">
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

    <template x-if="playing">
        <video src="{{ $videoUrl }}" poster="{{ $posterUrl }}" controls autoplay playsinline preload="metadata"
            class="w-full h-auto bg-black"></video>
    </template>
</figure>
