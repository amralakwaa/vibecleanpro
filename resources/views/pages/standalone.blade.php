{{--
    Shared template for Trust, Legal and Landing pages (guarantee, privacy,
    FAQ hub, campaign pages), in the V2 system. Their content is
    freeform/blocks-driven, so one template covers all three; the About
    page (type About) has its own identity template because its content
    is structured data, not blocks.

    Two presentations, chosen by page type - never by content:

      - Trust / Landing: a readable V2 page. The light tinted opening
        with the CSS glow and a curve into the page field, the editor's
        blocks at reading width, and a tinted "still have a question?"
        panel with the real channels. Readable, not a heavy landing page:
        no navy hero, no stock photograph, no claims of its own.
      - Legal: a calm shell. A plain white opening, a narrow column, no
        gradient anywhere near the legal text, and a quiet text-only
        contact line at the end. The template adds no legal wording of
        its own - what the page says is exactly what the editor published.

    FAQ: a `faq` block renders the page's own FAQs; only a block whose
    source the editor set to "sitewide" renders the global pool (see
    PublicPageController::faqsForStandalone).
--}}
@php
    $isLegal = $page->type === \App\Enums\PageType::Legal;
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، لدي سؤال بخصوص: '.$page->title);
    $phoneUrl = $businessProfile?->phoneUrl();

    // The opening's curve takes the colour of whatever renders first
    // beneath it - the first active block's surface, or the page field
    // when there is none. A navy editor band gets a straight edge.
    $firstBlock = $page->contentBlocks->first(fn ($block) => $block->is_active && ($block->type !== 'faq' || $faqs->isNotEmpty()));
    $waveAfterHead = match ($firstBlock?->type) {
        'hero', 'cta' => null,
        'inclusions', 'steps', 'packages', 'faq', 'related_content', 'price_factors' => 'text-white',
        default => 'text-background',
    };
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    @if ($isLegal)
        {{-- ===== Legal: calm, narrow, plain ===== --}}
        <section class="bg-white border-b border-neutral-200">
            <x-public.container width="narrow" class="py-10 md:py-14">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
                <h1 class="font-display text-[1.75rem] md:text-4xl leading-tight md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance">{{ $page->title }}</h1>
            </x-public.container>
        </section>
    @else
        {{-- ===== Trust / Landing: the light tinted opening ===== --}}
        <section class="surface-tint relative isolate overflow-hidden">
            <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>
            <x-public.container width="wide" @class(['relative pt-8 md:pt-12', 'pb-14 md:pb-20' => (bool) $waveAfterHead, 'pb-10 md:pb-14' => ! $waveAfterHead])>
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
                <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950 text-balance max-w-3xl">{{ $page->title }}</h1>
            </x-public.container>
            @if ($waveAfterHead)
                <x-public.wave shape="curve" position="bottom" :class="$waveAfterHead" />
            @endif
        </section>
    @endif

    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" width="narrow" />
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

    @if ($whatsappUrl || $phoneUrl)
        <x-public.container width="narrow" class="pb-16 md:pb-24">
            @if ($isLegal)
                <p class="border-t border-neutral-200 pt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-neutral-600">
                    <span>لديك سؤال بخصوص هذه الصفحة؟</span>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 min-h-11 font-medium text-neutral-800 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                            <x-public.icon name="whatsapp" class="w-4 h-4 text-success-700" /> اسأل عبر واتساب
                        </a>
                    @endif
                    @if ($phoneUrl)
                        <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-1.5 min-h-11 font-medium text-neutral-800 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                            <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                        </a>
                    @endif
                </p>
            @else
                <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-6 md:p-8 grid gap-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center reveal">
                    <div class="glow-primary absolute -top-16 -end-16 w-48 h-48 opacity-60" aria-hidden="true"></div>
                    <div class="relative">
                        <h2 class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950">لديك سؤال لم تجد إجابته هنا؟</h2>
                        <p class="mt-2 text-neutral-600 leading-relaxed">راسلنا وسنجيبك مباشرة.</p>
                    </div>
                    <div class="relative flex flex-wrap items-center gap-x-6 gap-y-3">
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
            @endif
        </x-public.container>
    @endif
</x-layouts.public>
