{{--
    Homepage hero V2 - a brand moment, not a photo with a caption.

    Composition: an atmospheric navy field (two radial blue lights over
    the navy ground) with the real project photograph set into the
    trailing half of the canvas and faded into the field, so the copy
    sits on colour rather than on a busy picture. The headline block
    is top-aligned: on a phone the H1 and the primary action are inside
    the first ~65% of the screen instead of resting on the bottom edge.

    Two states, decided by the caller:
      photo   - the real "after" photograph of a published project
      no photo - the same field without an image; the layout does not
                 change shape, so the page is never "waiting" for a photo.

    Everything below the headline is real data passed in: quick service
    links (with the admin's public price when one exists) and a fact
    strip (city, published counts) - each hidden when empty.
--}}
@props([
    'image' => null,
    'eyebrow' => null,
    'heading',
    'description' => null,
    'ctaUrl',
    'ctaLabel',
    'whatsappUrl' => null,
    'secondaryUrl' => null,
    'secondaryLabel' => null,
    'services' => null, // Collection of Service with ->page loaded
    'facts' => [],      // [['label' => ..., 'value' => ...], ...]
])

@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $quickServices = $services?->take(4) ?? collect();
@endphp

<section class="surface-atmos relative isolate overflow-hidden text-white">
    @if ($image)
        {{-- The photograph is the hero's second half, not its wallpaper.
             On wide screens it owns the trailing 60% of the canvas at full
             strength and is masked - not framed - so it dissolves into the
             atmospheric field on its inner edge and along the top; a light
             navy multiply keeps its blacks in the brand's key, and the only
             scrim is a short one at the foot where the wave lands. On
             phones and tablets it sits behind the copy under a heavier
             top-to-bottom scrim so the headline stays legible. The crop
             favours the left third of the picture, where a hero photo
             should keep its subject - the copy owns the right side. --}}
        <div class="absolute inset-0 -z-10 lg:start-auto lg:end-0 lg:w-[60%] lg:[mask-image:linear-gradient(to_left,transparent_0%,black_45%),linear-gradient(to_bottom,transparent_0%,black_18%)] xl:[mask-image:linear-gradient(to_left,transparent_0%,black_30%),linear-gradient(to_bottom,transparent_0%,black_18%)] lg:[mask-composite:intersect] lg:[-webkit-mask-composite:source-in]" aria-hidden="true">
            <img
                src="{{ $image->url() }}"
                alt=""
                fetchpriority="high"
                width="{{ $image->width ?: 1600 }}"
                height="{{ $image->height ?: 1000 }}"
                class="w-full h-full object-cover object-[8%_center] lg:object-[20%_center] opacity-90 lg:opacity-100"
            >
            <div class="absolute inset-0 bg-primary-900/25 mix-blend-multiply"></div>
            {{-- 1024-1279 only: the headline column reaches further into the
                 photograph than on wider screens, so its inner half gets an
                 extra navy shield to keep the copy off the bright window. --}}
            <div class="hidden lg:block xl:hidden absolute inset-y-0 right-0 w-[45%] bg-gradient-to-l from-ink-950/75 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/65 via-45% to-ink-950/30 lg:from-ink-950/70 lg:via-transparent lg:via-45% lg:to-transparent"></div>
        </div>
    @endif

    <div class="glow-primary absolute -top-24 -end-24 w-[28rem] h-[28rem] -z-10 opacity-70" aria-hidden="true"></div>

    <x-public.container width="wide" class="relative pt-28 pb-28 md:pt-40 md:pb-36 lg:pb-40">
        <div class="max-w-2xl">
            @if ($eyebrow)
                <p class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/10 px-3.5 py-1.5 text-sm font-medium text-white/90 backdrop-blur-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-400" aria-hidden="true"></span>
                    {{ $eyebrow }}
                </p>
            @endif

            <h1 class="mt-5 font-display text-[2.25rem] leading-[1.12] sm:text-5xl md:text-6xl md:leading-[1.06] font-medium tracking-tight text-white text-balance">
                {{ $heading }}
            </h1>

            @if ($description)
                <p class="mt-5 text-lg text-ink-100/90 leading-relaxed max-w-xl">{{ $description }}</p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3" data-hero-cta>
                <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40">{{ $ctaLabel }}</x-public.button>

                @if ($whatsappUrl)
                    <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                @endif

                @if ($secondaryUrl && $secondaryLabel)
                    <a href="{{ $secondaryUrl }}" class="inline-flex items-center gap-2 min-h-11 px-1 font-medium text-white/90 underline-offset-4 hover:underline">
                        {{ $secondaryLabel }}
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 transition-transform group-hover:-translate-x-0.5" />
                    </a>
                @endif
            </div>

            {{-- Quick service rail: the real services, each a link, with
                 the public price when the admin set one. --}}
            @if ($quickServices->isNotEmpty())
                <ul class="mt-9 flex flex-wrap gap-2" aria-label="خدمات سريعة">
                    @foreach ($quickServices as $service)
                        <li>
                            <a href="{{ $urlResolver->urlForPage($service->page) }}"
                                class="group inline-flex items-center gap-2 min-h-11 rounded-full border border-white/15 bg-white/[0.07] px-4 text-sm text-white/90 backdrop-blur-sm transition-colors hover:bg-white/15 hover:border-white/30">
                                <span>{{ $service->name }}</span>
                                @if ($price = $service->publicPrice())
                                    <span class="text-primary-200 tabular-nums">{{ $price->label() }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($facts !== [])
            <dl class="mt-12 md:mt-16 flex flex-wrap gap-x-10 gap-y-4 border-t border-white/10 pt-6 max-w-2xl">
                @foreach ($facts as $fact)
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-white/75">{{ $fact['label'] }}</dt>
                        <dd class="mt-0.5 font-display text-lg font-medium text-white tabular-nums">{{ $fact['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </x-public.container>

    <x-public.wave shape="soft" position="bottom" class="text-white" />
</section>
