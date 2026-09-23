{{--
    The Trust Center hero. One atmospheric navy opening for every trust and
    legal page, so the whole family is recognisably one place, with a per-
    page icon and eyebrow the only things that change. Deep field -> wave ->
    white reading surface is the first step of the page's dark-to-light
    progression. Decorative layers are aria-hidden and non-interactive; the
    legal text itself never sits on this surface.

    @param string       $title        the page H1 (from the DB page title)
    @param string|null  $lede         the page's own opening line, lifted here
    @param string       $icon         icon name for the policy chip
    @param string       $eyebrow      short family label (e.g. الخصوصية)
    @param array        $breadcrumbs  BreadcrumbItem[] from SEO
    @param bool         $isHub        the /trust hub reads slightly larger
    @param \Illuminate\Support\Carbon|null $updated  real last-updated stamp
    @param string|null  $backUrl      link back to the hub (non-hub pages)
--}}
@props([
    'title',
    'lede' => null,
    'icon' => 'shield-check',
    'eyebrow' => 'مركز الثقة',
    'breadcrumbs' => [],
    'isHub' => false,
    'updated' => null,
    'backUrl' => null,
])

<section class="surface-atmos relative isolate overflow-hidden text-white">
    <div class="glow-primary absolute -top-24 -end-16 w-[26rem] h-[26rem] -z-10 opacity-50" aria-hidden="true"></div>
    <div class="absolute inset-0 -z-10 opacity-[0.06]" aria-hidden="true"
        style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 28px 28px;"></div>

    <x-public.container width="wide" @class(['relative', 'pt-8 md:pt-12 pb-16 md:pb-24' => $isHub, 'pt-8 md:pt-10 pb-14 md:pb-20' => ! $isHub])>
        @if (count($breadcrumbs) > 1)
            <nav aria-label="breadcrumb" class="text-sm mb-7">
                <ol class="flex flex-wrap items-center gap-2 text-ink-200">
                    @foreach ($breadcrumbs as $item)
                        <li class="flex items-center gap-2">
                            @if (! $loop->first)
                                <span aria-hidden="true" class="text-white/30">/</span>
                            @endif
                            @if ($item->url && ! $loop->last)
                                <a href="{{ $item->url }}" class="hover:text-white transition-colors">{{ $item->label }}</a>
                            @else
                                <span class="text-white font-medium" @if ($loop->last) aria-current="page" @endif>{{ $item->label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="max-w-3xl">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/10 ring-1 ring-white/15 text-white shrink-0" aria-hidden="true">
                    <x-public.icon :name="$icon" class="w-6 h-6" />
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/15 px-3.5 py-1.5 text-sm font-medium text-ink-100">
                    <x-public.icon name="shield-check" class="w-4 h-4 text-primary-300" />
                    {{ $eyebrow }}
                </span>
            </div>

            <h1 @class([
                'mt-6 font-display font-medium tracking-tight text-white text-balance',
                'text-[2.1rem] leading-[1.12] md:text-6xl md:leading-[1.05]' => $isHub,
                'text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08]' => ! $isHub,
            ])>{{ $title }}</h1>

            @if ($lede)
                <p class="mt-5 text-lg md:text-xl leading-relaxed text-ink-100/90 text-pretty">{{ $lede }}</p>
            @endif

            @if ($updated || $backUrl)
                <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-ink-200">
                    @if ($updated)
                        <span class="inline-flex items-center gap-1.5">
                            <x-public.icon name="clock" class="w-4 h-4 text-ink-300" />
                            آخر تحديث: {{ $updated->translatedFormat('j F Y') }}
                        </span>
                    @endif
                    @if ($updated && $backUrl)
                        <span aria-hidden="true" class="text-white/20">•</span>
                    @endif
                    @if ($backUrl)
                        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 font-medium text-white hover:text-primary-300 transition-colors">
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                            العودة إلى مركز الثقة
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </x-public.container>

    <x-public.wave shape="curve" position="bottom" class="text-background" />
</section>
