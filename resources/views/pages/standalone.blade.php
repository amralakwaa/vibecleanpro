{{--
    Shared template for Trust, Legal and Landing pages (guarantee, privacy,
    FAQ hub, campaign pages). Their content is freeform/blocks-driven, so
    one clean template covers all three; the About page (type About) has
    its own identity template because its content is structured data,
    not blocks.

    FAQ: a `faq` block renders the page's own FAQs; only a block whose
    source the editor set to "sitewide" renders the global pool (see
    PublicPageController::faqsForStandalone).
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، لدي سؤال بخصوص: '.$page->title);
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance max-w-3xl">{{ $page->title }}</h1>
        </x-public.container>
    </section>

    <x-public.blocks :blocks="$page->contentBlocks" :except="['faq']" width="narrow" />
    <x-public.blocks :blocks="$page->contentBlocks" :only="['faq']" :faqs="$faqs" width="narrow" />

    @if ($whatsappUrl || $phoneUrl)
        <x-public.container width="narrow" class="pb-16 md:pb-24">
            <div class="border-t border-b border-neutral-200 py-8 grid gap-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                <div>
                    <h2 class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950">لديك سؤال لم تجد إجابته هنا؟</h2>
                    <p class="mt-2 text-neutral-600 leading-relaxed">راسلنا وسنجيبك مباشرة.</p>
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
