{{--
    Trust Center body (trust + legal pages). Included by pages/standalone
    when the page type is Trust or Legal. Kept as its own partial so its
    nested @if/@unless structure compiles in isolation rather than inside
    the standalone switcher. Expects: $page, $seo, $faqs, $businessProfile,
    $whatsappUrl, $phoneUrl (shared from the including view).
--}}
@php
    $isHub = \App\Support\Content\TrustPolicies::isHub($page->slug);
    $policies = \App\Support\Content\TrustPolicies::all();
    $meta = \App\Support\Content\TrustPolicies::for($page->slug);
    $hubUrl = url('/'.\App\Support\Content\TrustPolicies::hubSlug());

    $trust = app(\App\Support\Content\TrustSections::class)->build($page->contentBlocks);

    // Real page titles for the family's cards; only published policies are
    // linked, so navigation never lands on a page pulled back to Review.
    $policyTitles = \App\Models\Page::query()
        ->whereIn('slug', array_keys($policies))
        ->where('status', \App\Enums\PageStatus::Published)
        ->pluck('title', 'slug');
@endphp

<x-public.trust-hero
    :title="$page->title"
    :lede="$trust['lede']"
    :icon="$meta['icon']"
    :eyebrow="$meta['eyebrow']"
    :breadcrumbs="$seo->breadcrumbs"
    :is-hub="$isHub"
    :updated="$page->updated_at"
    :back-url="$isHub ? null : $hubUrl"
/>

@if ($isHub)
    <x-public.trust-hub :policies="$policies" :titles="$policyTitles" />
@else
    {{-- Reading field: a contents rail beside the numbered policy prose,
         the text on a white panel over the neutral field for depth. --}}
    <section class="relative isolate bg-background overflow-hidden">
        <div class="glow-primary absolute top-16 -end-24 w-80 h-80 opacity-25" aria-hidden="true"></div>

        <x-public.container class="relative py-12 md:py-16">
            <div class="grid gap-8 lg:gap-12 xl:gap-16 lg:grid-cols-[15rem_minmax(0,1fr)]">
                <x-public.trust-policy-nav :sections="$trust['sections']" />

                <div class="min-w-0">
                    <div class="rounded-3xl bg-white ring-1 ring-ink-950/8 shadow-sm shadow-ink-950/5 p-6 sm:p-8 md:p-10">
                        <div class="prose prose-legal prose-reading max-w-none">
                            {!! $trust['body'] !!}
                        </div>
                    </div>
                </div>
            </div>
        </x-public.container>
    </section>
@endif

<x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

@unless ($isHub)
    <x-public.trust-related
        :current="$page->slug"
        :policies="$policies"
        :titles="$policyTitles"
        :hub-url="$hubUrl"
    />
@endunless

<x-public.blocks :blocks="$page->contentBlocks" :only="['cta']" width="narrow" />

{{-- A page with neither a CTA block nor channels still closes cleanly. --}}
@php($hasCtaBlock = $page->contentBlocks->contains(fn ($block) => $block->is_active && $block->type === 'cta' && ! empty($block->data['heading'])))
@if (! $hasCtaBlock && ($whatsappUrl || $phoneUrl))
    <x-public.cta
        title="لديك سؤال بخصوص هذه الصفحة؟"
        description="راسلنا وسنجيبك مباشرة."
        :quote-url="route('public.quote')"
        :whatsapp-url="$whatsappUrl"
        :phone-url="$phoneUrl"
    />
@endif
