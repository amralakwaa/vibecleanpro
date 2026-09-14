{{--
    Mobile conversion bar. Previously three equal-width actions (quote,
    call, WhatsApp), which gave the thumb three identical targets and no
    hierarchy. Now: one dominant blue action that takes the available
    width, with WhatsApp beside it as the secondary channel in green.

    Phone is deliberately no longer a permanent third button - it stays
    reachable from the mobile menu and the footer, so the bar carries a
    decision rather than a menu. Fixed height, safe-area aware, lg:hidden;
    the layout gives <main> matching bottom padding so this never covers
    page content.
--}}
@props(['quoteUrl' => null, 'whatsappUrl' => null, 'phoneUrl' => null])

@if ($quoteUrl || $whatsappUrl)
    <div class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-neutral-200 pb-[env(safe-area-inset-bottom)]">
        <div class="flex items-center gap-2 p-3">
            @if ($quoteUrl)
                <x-public.button :href="$quoteUrl" variant="cta" size="md" icon="check-circle" class="grow min-h-12">
                    طلب خدمة
                </x-public.button>
            @endif

            @if ($whatsappUrl)
                {{-- Alongside the quote action it collapses to an
                     icon-only square (kept at a 48px+ tap target and
                     given its own aria-label); on its own it becomes the
                     full-width labelled action. --}}
                <x-public.button
                    :href="$whatsappUrl"
                    external
                    variant="whatsapp"
                    size="md"
                    icon="whatsapp"
                    :class="$quoteUrl ? 'shrink-0 !px-4 min-h-12 min-w-12' : 'grow min-h-12'"
                    :aria-label="$quoteUrl ? 'تواصل عبر واتساب' : null"
                >
                    @unless ($quoteUrl)
                        تواصل عبر واتساب
                    @endunless
                </x-public.button>
            @endif
        </div>
    </div>
@endif
