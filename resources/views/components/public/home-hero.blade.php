{{--
    Homepage hero, two states - deliberately NOT the shared
    hero.blade.php, which stays as-is for the typed detail pages.

    Evidence Hero  - used only when a real project photograph exists.
                     Near-full-bleed, with a navy gradient scrim applied
                     ONLY across the band the text sits in, so the
                     photograph itself is never tinted or filtered.
    Editorial Hero - the fallback when there is no real photo. Typography
                     and whitespace carry it; it never reaches for stock,
                     CGI, or an illustration to fill the space.

    A weak photo is worse than no photo, so the caller decides by passing
    $image or not - this component never invents one.
--}}
@props([
    'image' => null,
    'eyebrow' => null,
    'heading',
    'description' => null,
    'ctaUrl',
    'ctaLabel',
    'secondaryUrl' => null,
    'secondaryLabel' => null,
])

@if ($image)
    {{-- ===== Evidence Hero ===== --}}
    <section class="relative isolate min-h-[82svh] md:min-h-[78vh] flex items-end overflow-hidden">
        <img
            src="{{ $image->url() }}"
            alt="{{ $image->alt_text ?? $heading }}"
            fetchpriority="high"
            class="absolute inset-0 -z-10 w-full h-full object-cover object-center"
        >

        {{-- Reading scrims only - never a tint over the whole photo.
             Bottom: carries the headline block. Top: a short, much
             lighter band so the overlaid white header stays legible even
             when the photograph happens to be bright at the top edge
             (a real risk we cannot control, since these are real project
             photos rather than art-directed shots). --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink-950 via-ink-950/75 to-transparent" aria-hidden="true"></div>
        <div class="absolute inset-x-0 top-0 -z-10 h-28 bg-gradient-to-b from-ink-950/55 to-transparent" aria-hidden="true"></div>

        <x-public.container width="wide" class="w-full pb-14 pt-32 md:pb-20 md:pt-40">
            <div class="max-w-2xl">
                @if ($eyebrow)
                    <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-white/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500" aria-hidden="true"></span>
                        {{ $eyebrow }}
                    </p>
                @endif

                <h1 class="mt-4 font-display text-[2.125rem] leading-[1.15] md:text-6xl md:leading-[1.08] font-medium tracking-tight text-white text-balance">
                    {{ $heading }}
                </h1>

                @if ($description)
                    <p class="mt-5 text-white/85 leading-relaxed max-w-xl">{{ $description }}</p>
                @endif

                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="check-circle">{{ $ctaLabel }}</x-public.button>

                    @if ($secondaryUrl && $secondaryLabel)
                        <a href="{{ $secondaryUrl }}" class="inline-flex items-center gap-2 font-medium text-white underline-offset-4 hover:underline">
                            {{ $secondaryLabel }}
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    @endif
                </div>

                @if ($image->caption)
                    <p class="mt-8 text-xs text-white/60">{{ $image->caption }}</p>
                @endif
            </div>
        </x-public.container>
    </section>
@else
    {{-- ===== Editorial Hero (no real photograph available) ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-20 md:py-32">
            <div class="max-w-3xl">
                @if ($eyebrow)
                    <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-primary-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-600" aria-hidden="true"></span>
                        {{ $eyebrow }}
                    </p>
                @endif

                <h1 class="mt-5 font-display text-[2.125rem] leading-[1.15] md:text-6xl md:leading-[1.08] font-medium tracking-tight text-ink-950 text-balance">
                    {{ $heading }}
                </h1>

                @if ($description)
                    <p class="mt-6 text-lg text-neutral-600 leading-relaxed max-w-xl">{{ $description }}</p>
                @endif

                <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <x-public.button :href="$ctaUrl" variant="cta" size="lg" icon="check-circle">{{ $ctaLabel }}</x-public.button>

                    @if ($secondaryUrl && $secondaryLabel)
                        <a href="{{ $secondaryUrl }}" class="inline-flex items-center gap-2 font-medium text-primary-700 underline-offset-4 hover:underline">
                            {{ $secondaryLabel }}
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
@endif
