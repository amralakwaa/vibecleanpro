{{--
    Thumb-friendly, non-obtrusive mobile conversion bar (Phase 5 report,
    item 10). Fixed height, safe-area aware, lg:hidden so it never
    interferes with desktop. The page's own <main> gets bottom padding
    from the layout to compensate so this never covers content.

    quoteUrl leads as the one terracotta ("cta") action - it is the site's
    primary conversion path (see QuoteController's own docblock) - while
    call/WhatsApp stay secondary. Column count adapts to however many of
    the three are actually available (see the Homepage Conversion Review
    report, item 5).
--}}
@props(['quoteUrl' => null, 'whatsappUrl' => null, 'phoneUrl' => null])

@php
    $columns = collect([$quoteUrl, $phoneUrl, $whatsappUrl])->filter()->count();
    $gridClass = match ($columns) {
        3 => 'grid-cols-3',
        2 => 'grid-cols-2',
        default => 'grid-cols-1',
    };
@endphp

@if ($quoteUrl || $whatsappUrl || $phoneUrl)
    <div class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white border-t border-neutral-200 pb-[env(safe-area-inset-bottom)]">
        <div class="grid {{ $gridClass }} gap-2 p-2.5">
            @if ($quoteUrl)
                <x-public.button href="{{ $quoteUrl }}" variant="cta" size="md" icon="check-circle" class="w-full">
                    اطلب خدمة
                </x-public.button>
            @endif
            @if ($phoneUrl)
                <x-public.button href="{{ $phoneUrl }}" variant="secondary" size="md" icon="phone" class="w-full">
                    اتصال
                </x-public.button>
            @endif
            @if ($whatsappUrl)
                <x-public.button href="{{ $whatsappUrl }}" external :variant="$quoteUrl ? 'secondary' : 'cta'" size="md" icon="whatsapp" class="w-full">
                    واتساب
                </x-public.button>
            @endif
        </div>
    </div>
@endif
