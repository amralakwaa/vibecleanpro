{{--
    About - the company identity page, in the V2 system: who we are, who
    owns and runs the company, who does the work, why it exists, where it
    operates, and what it stands for.

    Nothing about the business is written here. Every statement is a
    column an editor fills in (business_profiles: tagline, identity
    statement, story, mission, vision, values, founder_*), a table an
    editor manages (team_members), or a count of what the site already
    publishes (areas, projects). A section whose data is empty - or whose
    visibility toggle is off - simply does not render; there is never a
    placeholder and never a customer-facing "no data yet".

    V2 leads with the brand, not a photograph: the opening is the navy
    field the homepage owns, carrying the tagline, the name the editor
    gave the page and the identity statement, with the real facts as a
    strip beneath. No stock crew pretends to be the team. The founder is
    the real photo, framed, when one is stored - and a typographic
    monogram from the stored name when it is not, never a stock person.
    Team members without a photo get the same monogram. Mission and
    vision are two statements in display type, values a numbered ruled
    list, and the page closes on the same decision band the other pages
    end with. Waves take the colour of whatever actually renders next.

    The Page record contributes the H1/SEO and, optionally, extra content
    blocks and FAQs below the identity sections. Wording that is not a
    business fact (section labels, "read our work") is the only prose
    written in this template.
--}}
@php
    $profile = $businessProfile;
    $urlResolver = app(\App\Seo\UrlResolver::class);

    $whatsappUrl = $profile?->whatsappUrl('مرحبًا، قرأت عنكم وأرغب في الاستفسار');
    $quoteUrl = route('public.quote');

    $values = collect($profile?->values ?? [])->filter(fn ($value) => filled($value['title'] ?? null))->values();
    $showFounder = (bool) $profile?->hasVisibleFounder();
    $showTeam = (bool) $profile?->show_team && $team->isNotEmpty();
    // RichEditor saves an empty field as '<p></p>' - only real text counts.
    $hasRichText = fn (?string $html): bool => filled(trim(strip_tags((string) $html)));
    $hasStory = $hasRichText($profile?->story);
    $hasMissionOrVision = filled($profile?->mission) || filled($profile?->vision);
    $hasFaqSection = $faqs->isNotEmpty() && $page->contentBlocks->contains(fn ($block) => $block->type === 'faq' && $block->is_active);

    // Facts strip: the city is a stored value; the two counts are the
    // same published sets the /areas and /projects pages list, shown
    // only when there is enough to be worth stating.
    $facts = collect([
        $profile?->city ? ['label' => 'مقر العمل', 'value' => $profile->city, 'url' => null] : null,
        $publishedAreas >= 3 ? ['label' => 'مناطق التغطية', 'value' => $publishedAreas.' '.($publishedAreas <= 10 ? 'أحياء' : 'حيًا'), 'url' => route('public.areas.index')] : null,
        $publishedProjects >= 3 ? ['label' => 'أعمال موثقة', 'value' => $publishedProjects.' '.($publishedProjects <= 10 ? 'مشاريع' : 'مشروعًا'), 'url' => route('public.projects.index')] : null,
    ])->filter();

    // A monogram is the first letter of a stored name - the typographic
    // stand-in for a portrait, never a stock face. An alef-initial name
    // (أحمد، إبراهيم) takes two letters, since a lone alef is just a stroke.
    $monogram = function (string $name): string {
        $name = trim($name);

        return mb_substr($name, 0, in_array(mb_substr($name, 0, 1), ['ا', 'أ', 'إ', 'آ'], true) ? 2 : 1);
    };

    // Section numbering follows what actually renders, so the sequence
    // never skips a number for a hidden section.
    $sectionNumber = 0;
    $arabicNumber = fn (int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);

    // Waves take the colour of the section they hand over to, and every
    // section is optional - so the colours come from the rendered order:
    // [top edge, bottom edge] per section, null for a navy band.
    $blockSurface = fn (string $type): array => match ($type) {
        'hero', 'cta' => [null, null],
        'inclusions', 'steps', 'packages', 'faq', 'related_content' => ['text-white', 'text-white'],
        'price_factors' => ['text-white', 'text-primary-50'],
        default => ['text-background', 'text-background'],
    };
    $surfaces = collect([
        $hasStory ? ['text-background', 'text-background'] : false,
        $hasMissionOrVision ? ['text-white', 'text-primary-50'] : false,
        $values->isNotEmpty() ? ['text-white', 'text-white'] : false,
        $showFounder ? ['text-background', 'text-background'] : false,
        $showTeam ? ['text-white', 'text-white'] : false,
        ...$page->contentBlocks->where('is_active', true)->where('type', '!=', 'faq')->map(fn ($block) => $blockSurface($block->type))->values()->all(),
        $hasFaqSection ? ['text-white', 'text-white'] : false,
    ])->filter(fn ($surface) => $surface !== false)->values();
    $waveAfterHero = $surfaces->first()[0] ?? null;
    $waveBeforeDecision = $surfaces->last()[1] ?? null;
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Identity - the brand field, the real facts ===== --}}
    <section class="surface-atmos relative isolate overflow-hidden text-white">
        <div class="glow-primary absolute -top-32 -end-24 w-[34rem] h-[34rem] -z-10 opacity-70" aria-hidden="true"></div>
        <div class="glow-primary absolute -bottom-40 start-1/4 w-[28rem] h-[28rem] -z-10 opacity-40" aria-hidden="true"></div>
        <x-public.container width="wide" @class(['relative pt-8 md:pt-12', 'pb-24 md:pb-32' => (bool) $waveAfterHero, 'pb-16 md:pb-24' => ! $waveAfterHero])>
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-8 [&_a]:text-white/80 [&_a:hover]:text-white [&_span]:text-white/90 [&_ol]:text-white/70" />
            <div class="max-w-3xl">
                @if ($profile?->tagline)
                    <p class="inline-flex items-center rounded-full bg-white/10 ring-1 ring-white/20 px-3.5 py-1.5 text-sm font-medium text-white backdrop-blur-sm">{{ $profile->tagline }}</p>
                @endif
                <h1 @class(['font-display text-[2.1rem] leading-[1.12] md:text-6xl md:leading-[1.05] font-medium tracking-tight text-white text-balance', 'mt-6' => $profile?->tagline])>
                    {{ $page->title }}
                </h1>
                @if ($profile?->identity_statement)
                    <p class="mt-6 text-lg md:text-2xl text-white/85 leading-relaxed md:leading-relaxed">{{ $profile->identity_statement }}</p>
                @endif
                <div class="mt-8 flex flex-wrap items-center gap-3" data-hero-cta>
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40">اطلب عرض سعر</x-public.button>
                    <a href="{{ route('public.projects.index') }}" class="inline-flex items-center gap-2 min-h-11 px-1 font-medium text-white/90 underline-offset-4 hover:underline">
                        شاهد أعمالنا
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </div>
            </div>

            @if ($facts->isNotEmpty())
                <dl class="mt-12 md:mt-16 grid grid-cols-2 sm:grid-cols-3 gap-4 max-w-3xl">
                    @foreach ($facts as $fact)
                        <div class="rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur-sm p-4 md:p-5">
                            <dt class="text-sm text-white/70">{{ $fact['label'] }}</dt>
                            <dd class="mt-1.5 font-display text-lg md:text-2xl font-medium text-white">
                                @if ($fact['url'])
                                    <a href="{{ $fact['url'] }}" class="inline-flex items-center gap-2 min-h-11 hover:text-primary-200 underline-offset-4 hover:underline transition-colors">
                                        {{ $fact['value'] }}
                                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180 text-white/60" />
                                    </a>
                                @else
                                    {{ $fact['value'] }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </x-public.container>
        @if ($waveAfterHero)
            <x-public.wave shape="soft" position="bottom" :class="$waveAfterHero" />
        @endif
    </section>

    {{-- ===== 2. Story - a reading column ===== --}}
    @if ($hasStory)
        @php $sectionNumber++; @endphp
        <x-public.section width="wide" density="feature">
            <div class="grid gap-8 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-16">
                <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="قصتنا" heading class="lg:pt-2" />
                <div class="prose prose-reading max-w-2xl reveal">
                    {!! $profile->story !!}
                </div>
            </div>
        </x-public.section>
    @endif

    {{-- ===== 3. Mission / vision - two statements in display type ===== --}}
    @if ($hasMissionOrVision)
        @php $sectionNumber++; @endphp
        <section class="surface-tint relative isolate overflow-hidden">
            <div class="glow-primary absolute -top-24 -start-24 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
            <x-public.container width="wide" class="relative py-14 md:py-20">
                <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="ما نسعى إليه" heading class="mb-8" />
                <div @class(['grid gap-6', 'md:grid-cols-2 md:gap-8' => $profile->mission && $profile->vision, 'max-w-2xl' => ! ($profile->mission && $profile->vision)])>
                    @if ($profile->mission)
                        <div class="rounded-3xl bg-white/80 ring-1 ring-primary-200/60 backdrop-blur-sm p-6 md:p-8 reveal">
                            <x-public.icon name="quote" class="w-8 h-8 text-primary-300" />
                            <h3 class="mt-4 text-sm font-medium tracking-wide text-primary-700">رسالتنا</h3>
                            <p class="mt-3 font-display text-xl md:text-2xl font-light leading-relaxed text-ink-950 text-balance">{{ $profile->mission }}</p>
                        </div>
                    @endif
                    @if ($profile->vision)
                        <div class="rounded-3xl bg-white/80 ring-1 ring-primary-200/60 backdrop-blur-sm p-6 md:p-8 reveal">
                            <x-public.icon name="sparkles" class="w-8 h-8 text-primary-300" />
                            <h3 class="mt-4 text-sm font-medium tracking-wide text-primary-700">رؤيتنا</h3>
                            <p class="mt-3 font-display text-xl md:text-2xl font-light leading-relaxed text-ink-950 text-balance">{{ $profile->vision }}</p>
                        </div>
                    @endif
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 4. Values / operating principles - a numbered ruled list ===== --}}
    @if ($values->isNotEmpty())
        @php $sectionNumber++; @endphp
        <x-public.section width="wide" tone="surface">
            <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="مبادئ العمل" heading class="mb-4" />
            <ol class="grid md:grid-cols-2 gap-x-16">
                @foreach ($values as $value)
                    <li class="flex gap-5 py-6 border-b border-neutral-200 reveal">
                        <span class="font-display text-3xl md:text-4xl font-medium text-primary-200 tabular-nums shrink-0 leading-none pt-0.5" aria-hidden="true">{{ $arabicNumber($loop->iteration) }}</span>
                        <div>
                            <h3 class="font-display text-xl font-medium tracking-tight text-ink-950">{{ $value['title'] }}</h3>
                            @if (filled($value['description'] ?? null))
                                <p class="mt-2 text-neutral-600 leading-relaxed">{{ $value['description'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-public.section>
    @endif

    {{-- ===== 5. Founder - the real photograph framed, or the name as type ===== --}}
    @if ($showFounder)
        @php $sectionNumber++; @endphp
        <x-public.section width="wide" density="feature">
            <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="المؤسس" heading class="mb-8 md:mb-12" />
            <div @class(['grid gap-8 md:gap-10 items-start', 'md:grid-cols-[280px_minmax(0,1fr)] lg:grid-cols-[340px_minmax(0,1fr)] lg:gap-16' => (bool) $profile->founderPhoto, 'md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] lg:gap-16' => ! $profile->founderPhoto])>
                @if ($profile->founderPhoto)
                    <div class="relative max-w-xs md:max-w-none reveal">
                        <div class="surface-atmos absolute inset-0 rounded-3xl -translate-x-3 translate-y-3 md:-translate-x-5 md:translate-y-5" aria-hidden="true"></div>
                        <img
                            src="{{ $profile->founderPhoto->url() }}" srcset="{{ $profile->founderPhoto->srcset() }}"
                            alt="{{ $profile->founderPhoto->alt_text ?? $profile->founder_name }}"
                            loading="lazy"
                            width="640"
                            height="800"
                            class="relative w-full aspect-[4/5] object-cover rounded-3xl ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/15"
                        >
                    </div>
                @else
                    {{-- No portrait stored: the name itself is the portrait, set as
                         type on the brand field - never a stock person. --}}
                    <div class="surface-atmos relative isolate overflow-hidden rounded-3xl p-7 md:p-9 min-h-[14rem] md:min-h-[18rem] flex items-end shadow-xl shadow-primary-900/15 reveal">
                        <div class="glow-primary absolute -top-16 -end-16 w-56 h-56 -z-10 opacity-70" aria-hidden="true"></div>
                        <h3 class="font-display text-3xl md:text-4xl lg:text-5xl md:leading-[1.15] font-medium tracking-tight text-white text-balance">{{ $profile->founder_name }}</h3>
                    </div>
                @endif
                <div class="max-w-2xl">
                    @if ($profile->founderPhoto)
                        <h3 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">{{ $profile->founder_name }}</h3>
                    @endif
                    @if ($profile->founder_title)
                        <p @class(['inline-flex items-center rounded-full bg-primary-50 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700', 'mt-2' => (bool) $profile->founderPhoto])>{{ $profile->founder_title }}</p>
                    @endif
                    @if ($profile->founder_bio)
                        <p class="mt-5 text-lg text-neutral-600 leading-relaxed">{{ $profile->founder_bio }}</p>
                    @endif
                    @if ($hasRichText($profile->founder_long_bio))
                        <div class="prose mt-6">
                            {!! $profile->founder_long_bio !!}
                        </div>
                    @endif
                </div>
            </div>
        </x-public.section>
    @endif

    {{-- ===== 6. Team - real people, real photos or monograms ===== --}}
    @if ($showTeam)
        @php $sectionNumber++; @endphp
        <x-public.section width="wide" tone="surface">
            <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="الفريق" heading class="mb-8" />
            <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($team as $member)
                    <li class="flex gap-5 rounded-3xl ring-1 ring-ink-950/10 bg-background p-5 reveal">
                        @if ($member->photo)
                            <img
                                src="{{ $member->photo->url() }}" srcset="{{ $member->photo->srcset() }}"
                                alt="{{ $member->photo->alt_text ?? $member->name }}"
                                loading="lazy"
                                width="160"
                                height="160"
                                class="w-20 h-20 rounded-full object-cover shrink-0 ring-1 ring-ink-950/10"
                            >
                        @else
                            <span class="surface-atmos flex items-center justify-center w-20 h-20 rounded-full shrink-0 font-display text-3xl font-medium text-white" aria-hidden="true">{{ $monogram($member->name) }}</span>
                        @endif
                        <div class="min-w-0">
                            <h3 class="font-display text-lg font-medium tracking-tight text-ink-950">{{ $member->name }}</h3>
                            @if ($member->role_title)
                                <p class="mt-0.5 text-sm text-primary-700">{{ $member->role_title }}</p>
                            @endif
                            @if ($member->bio)
                                <p class="mt-2 text-sm text-neutral-600 leading-relaxed">{{ $member->bio }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    {{-- ===== 7. Anything else the editor added, then FAQs ===== --}}
    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" width="narrow" />
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

    {{-- ===== 8. Decision - see for yourself, then talk to us ===== --}}
    <section @class(['surface-atmos relative isolate overflow-hidden text-white', 'border-t border-white/10' => ! $waveBeforeDecision])>
        @if ($waveBeforeDecision)
            <x-public.wave shape="soft" position="top" :class="$waveBeforeDecision" />
        @endif
        <div class="glow-primary absolute -bottom-24 start-1/3 w-[26rem] h-[26rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" @class(['pb-16 md:pb-24', 'pt-28 md:pt-36' => (bool) $waveBeforeDecision, 'pt-16 md:pt-24' => ! $waveBeforeDecision])>
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end reveal">
                <div class="max-w-2xl">
                    <h2 class="font-display text-3xl md:text-5xl md:leading-[1.1] font-medium tracking-tight text-white text-balance">تعرّف على ما نفذناه فعليًا، ثم قرر.</h2>
                    <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3">
                        <a href="{{ route('public.projects.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white/85 hover:text-white underline-offset-4 hover:underline transition-colors">
                            أعمالنا
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                        <a href="{{ route('public.areas.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white/85 hover:text-white underline-offset-4 hover:underline transition-colors">
                            مناطق التغطية
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle" class="shadow-lg shadow-primary-900/40">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
