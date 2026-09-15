{{--
    About - the company identity page: who we are, who owns and runs the
    company, who does the work, why it exists, where it operates, and
    what it stands for.

    Nothing about the business is written here. Every statement is a
    column an editor fills in (business_profiles: tagline, identity
    statement, story, mission, vision, values, founder_*), a table an
    editor manages (team_members), or a count of what the site already
    publishes (areas, projects). A section whose data is empty - or whose
    visibility toggle is off - simply does not render; there is never a
    placeholder and never a customer-facing "no data yet".

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

    // Facts strip: the city is a stored value; the two counts are the
    // same published sets the /areas and /projects pages list, shown
    // only when there is enough to be worth stating.
    $facts = collect([
        $profile?->city ? ['label' => 'مقر العمل', 'value' => $profile->city, 'url' => null] : null,
        $publishedAreas >= 3 ? ['label' => 'مناطق التغطية', 'value' => $publishedAreas.' '.($publishedAreas <= 10 ? 'أحياء' : 'حيًا'), 'url' => route('public.areas.index')] : null,
        $publishedProjects >= 3 ? ['label' => 'أعمال موثقة', 'value' => $publishedProjects.' '.($publishedProjects <= 10 ? 'مشاريع' : 'مشروعًا'), 'url' => route('public.projects.index')] : null,
    ])->filter();

    // Section numbering follows what actually renders, so the sequence
    // never skips a number for a hidden section.
    $sectionNumber = 0;
    $arabicNumber = fn (int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">

    {{-- ===== 1. Identity ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <div class="max-w-3xl">
                @if ($profile?->tagline)
                    <p class="text-sm font-medium tracking-wide text-primary-700">{{ $profile->tagline }}</p>
                @endif
                <h1 @class(['font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance', 'mt-3' => $profile?->tagline])>
                    {{ $page->title }}
                </h1>
                @if ($profile?->identity_statement)
                    <p class="mt-5 text-lg md:text-xl text-neutral-600 leading-relaxed">{{ $profile->identity_statement }}</p>
                @endif
            </div>

            @if ($facts->isNotEmpty())
                <dl class="mt-10 grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-6 max-w-3xl">
                    @foreach ($facts as $fact)
                        <div class="border-t border-neutral-200 pt-4">
                            <dt class="text-sm text-neutral-500">{{ $fact['label'] }}</dt>
                            <dd class="mt-1 font-display text-lg md:text-xl font-medium text-ink-950">
                                @if ($fact['url'])
                                    <a href="{{ $fact['url'] }}" class="inline-flex items-center min-h-11 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">{{ $fact['value'] }}</a>
                                @else
                                    {{ $fact['value'] }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </x-public.container>
    </section>

    {{-- ===== 2. Story ===== --}}
    @if ($hasStory)
        @php $sectionNumber++; @endphp
        <x-public.section width="wide">
            <div class="grid gap-8 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-16">
                <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="قصتنا" heading />
                <div class="prose prose-reading max-w-2xl">
                    {!! $profile->story !!}
                </div>
            </div>
        </x-public.section>
    @endif

    {{-- ===== 3. Mission / vision ===== --}}
    @if ($hasMissionOrVision)
        @php $sectionNumber++; @endphp
        <section class="bg-white border-t border-b border-neutral-200">
            <x-public.container width="wide" class="py-14 md:py-20">
                <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="ما نسعى إليه" heading class="mb-8" />
                <div @class(['grid gap-10', 'md:grid-cols-2 md:gap-16' => $profile->mission && $profile->vision, 'max-w-2xl' => ! ($profile->mission && $profile->vision)])>
                    @if ($profile->mission)
                        <div>
                            <h3 class="text-sm font-medium tracking-wide text-neutral-500">رسالتنا</h3>
                            <p class="mt-3 font-display text-xl md:text-2xl font-light leading-relaxed text-ink-950 text-balance">{{ $profile->mission }}</p>
                        </div>
                    @endif
                    @if ($profile->vision)
                        <div>
                            <h3 class="text-sm font-medium tracking-wide text-neutral-500">رؤيتنا</h3>
                            <p class="mt-3 font-display text-xl md:text-2xl font-light leading-relaxed text-ink-950 text-balance">{{ $profile->vision }}</p>
                        </div>
                    @endif
                </div>
            </x-public.container>
        </section>
    @endif

    {{-- ===== 4. Values / operating principles ===== --}}
    @if ($values->isNotEmpty())
        @php $sectionNumber++; @endphp
        <x-public.section width="wide">
            <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="مبادئ العمل" heading class="mb-4" />
            <ol class="grid md:grid-cols-2 gap-x-16">
                @foreach ($values as $value)
                    <li class="flex gap-5 py-6 border-b border-neutral-200">
                        <span class="font-display text-sm text-primary-600 tabular-nums shrink-0 pt-1" aria-hidden="true">{{ $arabicNumber($loop->iteration) }}</span>
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

    {{-- ===== 5. Founder ===== --}}
    @if ($showFounder)
        @php $sectionNumber++; @endphp
        <section class="bg-white border-t border-b border-neutral-200">
            <x-public.container width="wide" class="py-14 md:py-20">
                <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="المؤسس" heading class="mb-8" />
                <div @class(['grid gap-8 items-start', 'md:grid-cols-[280px_minmax(0,1fr)] lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-16' => (bool) $profile->founderPhoto])>
                    @if ($profile->founderPhoto)
                        <img
                            src="{{ $profile->founderPhoto->url() }}"
                            alt="{{ $profile->founderPhoto->alt_text ?? $profile->founder_name }}"
                            loading="lazy"
                            width="640"
                            height="800"
                            class="w-full max-w-xs md:max-w-none aspect-[4/5] object-cover"
                        >
                    @endif
                    <div class="max-w-2xl">
                        <h3 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-ink-950 text-balance">{{ $profile->founder_name }}</h3>
                        @if ($profile->founder_title)
                            <p class="mt-2 text-primary-700 font-medium">{{ $profile->founder_title }}</p>
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
            </x-public.container>
        </section>
    @endif

    {{-- ===== 6. Team ===== --}}
    @if ($showTeam)
        @php $sectionNumber++; @endphp
        <x-public.section width="wide">
            <x-public.section-marker :number="$arabicNumber($sectionNumber)" label="الفريق" heading class="mb-8" />
            <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10">
                @foreach ($team as $member)
                    <li @class(['flex gap-5', 'border-t-2 border-ink-950 pt-5' => ! $member->photo])>
                        @if ($member->photo)
                            <img
                                src="{{ $member->photo->url() }}"
                                alt="{{ $member->photo->alt_text ?? $member->name }}"
                                loading="lazy"
                                width="160"
                                height="160"
                                class="w-20 h-20 md:w-24 md:h-24 rounded-full object-cover shrink-0"
                            >
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

    {{-- ===== 8. See for yourself, then talk to us ===== --}}
    <section class="bg-ink-950 text-white">
        <x-public.container width="wide" class="py-16 md:py-24">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="max-w-2xl">
                    <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">تعرّف على ما نفذناه فعليًا، ثم قرر.</h2>
                    <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3">
                        <a href="{{ route('public.projects.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white underline-offset-4 hover:underline">
                            أعمالنا
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                        <a href="{{ route('public.areas.index') }}" class="inline-flex items-center gap-2 min-h-11 font-medium text-white underline-offset-4 hover:underline">
                            مناطق التغطية
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-public.button :href="$quoteUrl" variant="cta" size="lg" icon="check-circle">اطلب عرض سعر</x-public.button>
                    @if ($whatsappUrl)
                        <x-public.button :href="$whatsappUrl" external variant="whatsapp" size="lg" icon="whatsapp">واتساب</x-public.button>
                    @endif
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
