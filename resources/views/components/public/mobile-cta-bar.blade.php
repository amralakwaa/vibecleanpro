{{--
    Thumb-friendly, non-obtrusive mobile conversion bar (Phase 5 report,
    item 10). Fixed height, safe-area aware, lg:hidden so it never
    interferes with desktop. The page's own <main> gets bottom padding
    from the layout to compensate so this never covers content.
--}}
@props(['whatsappUrl' => null, 'phoneUrl' => null])

@if ($whatsappUrl || $phoneUrl)
    <div class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white border-t border-neutral-200 pb-[env(safe-area-inset-bottom)]">
        <div class="grid grid-cols-2 gap-2 p-2.5">
            @if ($phoneUrl)
                <x-public.button href="{{ $phoneUrl }}" variant="secondary" size="md" icon="phone" class="w-full">
                    اتصال
                </x-public.button>
            @endif
            @if ($whatsappUrl)
                <x-public.button href="{{ $whatsappUrl }}" external variant="cta" size="md" icon="whatsapp" class="w-full">
                    واتساب
                </x-public.button>
            @endif
        </div>
    </div>
@endif
