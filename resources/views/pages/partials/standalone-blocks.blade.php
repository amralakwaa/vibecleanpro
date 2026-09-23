{{--
    Generic editor-blocks body (landing / campaign / other standalone
    pages). Included by pages/standalone for any standalone type that is not
    Trust or Legal. A light tinted opening and the editor's blocks at
    reading width - the original standalone behaviour. Expects: $page, $seo,
    $faqs, $whatsappUrl, $phoneUrl (shared from the including view).
--}}
@php
    // The opening's curve takes the colour of whatever renders first beneath
    // it - the first active block's surface, or the page field when there is
    // none. A navy editor band gets a straight edge.
    $firstBlock = $page->contentBlocks->first(fn ($block) => $block->is_active && ($block->type !== 'faq' || $faqs->isNotEmpty()));
    $waveAfterHead = match ($firstBlock?->type) {
        'hero', 'cta' => null,
        'inclusions', 'steps', 'packages', 'faq', 'related_content', 'price_factors' => 'text-white',
        default => 'text-background',
    };
@endphp

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

<x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" width="narrow" />
<x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

@if ($whatsappUrl || $phoneUrl)
    <x-public.container width="narrow" class="pb-16 md:pb-24">
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
    </x-public.container>
@endif
