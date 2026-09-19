{{--
    Areas Index - Coverage Discovery, in the V2 system.

    Answers "do you cover my area?" as a directory, not a wall of links:
    a tinted head with the CSS "here" mark and the real total, a legend
    of the real AreaGroups (in-page anchors), then one ruled section per
    group with the group's name and its real area count in a lead column
    and the areas as pill links beside it, each carrying the number of
    services genuinely attached to it. Group names are headings only - an
    AreaGroup has no page, so nothing here links to one - and the only
    text on the page is real data: group names, area names and counts.
    No map, no stock photograph of a neighbourhood.

    The controller's grouping, ordering and published filter are unchanged.
--}}
@php
    $urlResolver = app(\App\Seo\UrlResolver::class);
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في معرفة إن كنتم تخدمون منطقتي');
    $phoneUrl = $businessProfile?->phoneUrl();

    $servicesLabel = fn (int $count): ?string => match (true) {
        $count === 1 => 'خدمة واحدة',
        $count === 2 => 'خدمتان',
        $count >= 3 && $count <= 10 => $count.' خدمات',
        $count > 10 => $count.' خدمة',
        default => null,
    };
    $areasLabel = fn (int $count): string => match (true) {
        $count === 1 => 'حي واحد',
        $count === 2 => 'حيّان',
        $count <= 10 => $count.' أحياء',
        default => $count.' حيًا',
    };

    $directory = $groups->map(fn ($entry) => ['name' => $entry['group']->name, 'areas' => $entry['areas']])->values();
    if ($ungrouped->isNotEmpty()) {
        $directory->push(['name' => $directory->isEmpty() ? null : 'مناطق أخرى', 'areas' => $ungrouped]);
    }
    $namedSections = $directory->filter(fn ($section) => $section['name'] !== null);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Head - the real total, the groups as a legend ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -z-10 end-[-5rem] top-1/2 -translate-y-1/2 hidden md:block" aria-hidden="true">
            <div class="relative w-[22rem] h-[22rem] lg:w-[26rem] lg:h-[26rem]">
                <div class="absolute inset-0 rounded-full border border-primary-300/50"></div>
                <div class="absolute inset-[18%] rounded-full border border-primary-400/50"></div>
                <div class="absolute inset-[36%] rounded-full border border-primary-500/40"></div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-primary-600 shadow-[0_0_0_8px_rgba(37,99,235,0.15)]"></div>
            </div>
        </div>

        <x-public.container width="wide" class="relative pt-8 pb-12 md:pt-12 md:pb-16">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <div class="max-w-2xl">
                <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">مناطق التغطية</h1>
                <p class="mt-4 text-lg text-neutral-600 leading-relaxed">
                    @if ($totalAreas >= 3)
                        نصل حاليًا إلى {{ $totalAreas }} {{ $totalAreas <= 10 ? 'أحياء' : 'حيًا' }} في الرياض وما حولها. ابحث عن منطقتك في الدليل أدناه.
                    @else
                        ابحث عن منطقتك في الدليل أدناه، أو راسلنا مباشرة إن لم تجدها.
                    @endif
                </p>
            </div>

            @if ($namedSections->count() > 1)
                <nav class="mt-8" aria-label="مجموعات المناطق">
                    <ul class="flex flex-wrap gap-2.5">
                        @foreach ($namedSections as $index => $section)
                            <li>
                                <a href="#areas-group-{{ $index }}" class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-4 text-sm text-ink-950 backdrop-blur-sm transition-[box-shadow,color] hover:ring-primary-400 hover:text-primary-700">
                                    <span class="font-medium">{{ $section['name'] }}</span>
                                    <span class="text-neutral-500">{{ $areasLabel($section['areas']->count()) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </x-public.container>
    </section>

    {{-- ===== 2. The directory ===== --}}
    @if ($directory->isNotEmpty())
        <section class="bg-white" aria-label="دليل المناطق">
            <x-public.container width="wide" class="py-6 md:py-10">
                @foreach ($directory as $index => $section)
                    <div id="areas-group-{{ $index }}" @class(['scroll-mt-24 py-8 md:py-12', 'border-t border-neutral-200' => ! $loop->first, 'grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-12' => (bool) $section['name']])>
                        @if ($section['name'])
                            <div class="lg:pt-1 reveal">
                                <p class="font-display text-sm font-medium text-primary-600 tabular-nums" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                                <h2 class="mt-1 font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950">{{ $section['name'] }}</h2>
                                <p class="mt-1 text-sm text-neutral-500">{{ $areasLabel($section['areas']->count()) }}</p>
                            </div>
                        @endif

                        <ul class="flex flex-wrap gap-2.5 reveal">
                            @foreach ($section['areas'] as $area)
                                <li>
                                    @if ($area->page)
                                        <a href="{{ $urlResolver->urlForPage($area->page) }}" class="group inline-flex items-center gap-2.5 min-h-12 rounded-full bg-neutral-50 ring-1 ring-ink-950/10 ps-3.5 pe-4 py-1.5 text-ink-950 transition-[box-shadow,color,background-color] hover:bg-white hover:ring-primary-400 hover:text-primary-700">
                                            <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600 shrink-0" />
                                            <span class="font-medium">{{ $area->name }}</span>
                                            @if ($label = $servicesLabel((int) $area->services_count))
                                                <span class="text-sm text-neutral-500 group-hover:text-primary-600/80 transition-colors">· {{ $label }}</span>
                                            @endif
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-2 min-h-11 rounded-full ring-1 ring-ink-950/10 px-3.5 py-1.5 text-neutral-700">
                                            <x-public.icon name="map-pin" class="w-4 h-4 text-neutral-400 shrink-0" />
                                            {{ $area->name }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </x-public.container>
        </section>
    @else
        {{-- Customer-facing, never "nothing published". --}}
        <section class="bg-white">
            <x-public.container width="narrow" class="py-16 md:py-24">
                <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-8 md:p-10 reveal">
                    <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 opacity-70" aria-hidden="true"></div>
                    <h2 class="relative font-display text-xl md:text-3xl font-medium tracking-tight text-ink-950">دليل المناطق قيد الإعداد</h2>
                    <p class="relative mt-3 text-neutral-600 leading-relaxed">أخبرنا بمنطقتك وسنرد عليك مباشرة بما إذا كنا نصل إليها.</p>
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 3. Not listed? A tinted note with the real channels ===== --}}
    @if ($whatsappUrl || $phoneUrl)
        <section class="surface-tint relative overflow-hidden">
            <x-public.container width="wide" class="py-14 md:py-20">
                <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_auto] md:items-center reveal">
                    <div>
                        <p class="text-sm font-medium tracking-wide text-primary-700">لم تجد منطقتك؟</p>
                        <h2 class="mt-2 font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">منطقتك غير مذكورة؟</h2>
                        <p class="mt-3 text-neutral-600 leading-relaxed max-w-xl">أرسل لنا اسم الحي وسنخبرك مباشرة إن كان بإمكاننا الوصول إليك.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                        @if ($whatsappUrl)
                            <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">اسأل عبر واتساب</x-public.button>
                        @endif
                        @if ($phoneUrl)
                            <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                                <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                            </a>
                        @endif
                    </div>
                </div>
            </x-public.container>
        </section>
    @endif
</x-layouts.public>
