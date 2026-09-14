{{--
    Areas Index - answers "do you cover my area?" as fast as possible.

    A typographic directory, not a wall of identical cards: each real
    AreaGroup is a ruled section with the group name in a narrow lead
    column and its areas running as hairline links in two or three
    columns beside it, so a visitor scans for their own neighbourhood the
    way they would scan a printed index. Group names are headings only -
    an AreaGroup has no page, so nothing here links to one - and the
    only text on the page is real data: group names, area names, and the
    number of services genuinely attached to each area.

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

    $directory = $groups->map(fn ($entry) => ['name' => $entry['group']->name, 'areas' => $entry['areas']])->values();
    if ($ungrouped->isNotEmpty()) {
        $directory->push(['name' => $directory->isEmpty() ? null : 'مناطق أخرى', 'areas' => $ungrouped]);
    }
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Header ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">مناطق التغطية</h1>
            <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">
                @if ($totalAreas >= 3)
                    نصل حاليًا إلى {{ $totalAreas }} {{ $totalAreas <= 10 ? 'أحياء' : 'حيًا' }} في الرياض وما حولها. ابحث عن منطقتك في الدليل أدناه.
                @else
                    ابحث عن منطقتك في الدليل أدناه، أو راسلنا مباشرة إن لم تجدها.
                @endif
            </p>
        </x-public.container>
    </section>

    {{-- ===== 2. The directory ===== --}}
    @if ($directory->isNotEmpty())
        <x-public.container width="wide">
            @foreach ($directory as $section)
                <section @class(['py-8 md:py-12', 'border-t border-neutral-200' => ! $loop->first, 'grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-12' => (bool) $section['name']])>
                    @if ($section['name'])
                        <h2 class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950 lg:pt-3">{{ $section['name'] }}</h2>
                    @endif

                    <ul @class(['grid sm:grid-cols-2 gap-x-8', 'lg:grid-cols-3' => ! $section['name'] || $section['areas']->count() > 6, 'lg:grid-cols-2' => $section['name'] && $section['areas']->count() <= 6])>
                        @foreach ($section['areas'] as $area)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $urlResolver->urlForPage($area->page) }}" class="group flex items-center justify-between gap-4 py-4 min-h-14">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors">{{ $area->name }}</span>
                                        @if ($label = $servicesLabel((int) $area->services_count))
                                            <span class="block mt-0.5 text-sm text-neutral-500">{{ $label }}</span>
                                        @endif
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 group-hover:text-primary-600 rtl:rotate-180 shrink-0 transition-colors" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </x-public.container>
    @else
        {{-- Customer-facing, never "nothing published". --}}
        <x-public.container width="narrow" class="py-16 md:py-24">
            <h2 class="font-display text-xl md:text-2xl font-medium text-ink-950">دليل المناطق قيد الإعداد</h2>
            <p class="mt-2 text-neutral-600 leading-relaxed">أخبرنا بمنطقتك وسنرد عليك مباشرة بما إذا كنا نصل إليها.</p>
        </x-public.container>
    @endif

    {{-- ===== 3. Not listed? A quiet ruled note, not a sales band ===== --}}
    @if ($whatsappUrl || $phoneUrl)
        <x-public.container width="wide" class="pb-16 md:pb-24">
            <div class="border-t border-b border-neutral-200 py-8 md:py-10 grid gap-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                <div>
                    <h2 class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950">منطقتك غير مذكورة؟</h2>
                    <p class="mt-2 text-neutral-600 leading-relaxed max-w-xl">أرسل لنا اسم الحي وسنخبرك مباشرة إن كان بإمكاننا الوصول إليك.</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" icon="whatsapp">اسأل عبر واتساب</x-public.button>
                    @endif
                    @if ($phoneUrl)
                        <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                            <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                        </a>
                    @endif
                </div>
            </div>
        </x-public.container>
    @endif
</x-layouts.public>
