{{--
    Internal-standard document + verification page. Reached by document code
    (/trust/verify/VCP-QMS-001). Shows the full standard as a corporate document,
    confirms its issuer/version/status, and prints cleanly (browser print → PDF).
    Only publicly-visible credentials reach here (controller uses scopePublic).
--}}
@php($isInternal = $credential->is_internal)
<x-layouts.public :seo="$seo">
    <section class="surface-atmos relative isolate overflow-hidden text-white print:bg-white print:text-ink-950">
        <x-public.container width="narrow" class="pt-28 pb-14 md:pt-36 md:pb-20 print:pt-6 print:pb-4">
            <a href="{{ route('public.standalone', ['slug' => 'trust']) }}" class="inline-flex items-center gap-2 text-sm text-white/80 hover:text-white print:hidden">
                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                مركز الثقة
            </a>
            <p class="mt-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-1.5 text-sm font-medium backdrop-blur-sm print:border-ink-950/15 print:bg-transparent print:text-ink-950">
                <x-public.icon name="{{ $credential->icon ?: 'badge-check' }}" class="w-4 h-4" />
                {{ $isInternal ? 'معيار تشغيلي داخلي — فايب كلين برو' : 'Vibe Clean Pro' }}
            </p>
            <h1 class="mt-4 font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-balance print:text-ink-950">
                {{ $credential->name_ar }}
            </h1>
            @if ($credential->name_en)
                <p class="mt-2 text-lg text-white/70 print:text-neutral-500" dir="ltr">{{ $credential->name_en }}</p>
            @endif

            <dl class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 border-t border-white/10 pt-6 max-w-xl print:border-ink-950/10 print:text-ink-950">
                @if ($credential->document_code)
                    <div><dt class="text-xs text-white/60 print:text-neutral-500">رمز الوثيقة</dt><dd class="mt-0.5 font-medium tabular-nums" dir="ltr">{{ $credential->document_code }}</dd></div>
                @endif
                @if ($credential->version)
                    <div><dt class="text-xs text-white/60 print:text-neutral-500">الإصدار</dt><dd class="mt-0.5 font-medium tabular-nums" dir="ltr">{{ $credential->version }}</dd></div>
                @endif
                <div><dt class="text-xs text-white/60 print:text-neutral-500">الحالة</dt><dd class="mt-0.5 font-medium">{{ $credential->status->label() }}</dd></div>
                @if ($credential->issued_at)
                    <div><dt class="text-xs text-white/60 print:text-neutral-500">تاريخ الإصدار</dt><dd class="mt-0.5 font-medium tabular-nums">{{ $credential->issued_at->translatedFormat('j F Y') }}</dd></div>
                @endif
                @if ($credential->review_at)
                    <div><dt class="text-xs text-white/60 print:text-neutral-500">المراجعة القادمة</dt><dd class="mt-0.5 font-medium tabular-nums">{{ $credential->review_at->translatedFormat('j F Y') }}</dd></div>
                @endif
                <div><dt class="text-xs text-white/60 print:text-neutral-500">الجهة المُصدِرة</dt><dd class="mt-0.5 font-medium">{{ $credential->issuer }}</dd></div>
            </dl>
        </x-public.container>
        <x-public.wave shape="soft" position="bottom" class="text-white print:hidden" />
    </section>

    <section class="bg-white">
        <x-public.container width="narrow" class="py-12 md:py-16 print:py-4">
            {{-- Verification confirmation --}}
            <div class="flex items-start gap-3 rounded-2xl bg-primary-50 ring-1 ring-primary-200/60 p-4 md:p-5 print:ring-ink-950/15">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary-600 text-white shrink-0" aria-hidden="true">
                    <x-public.icon name="check" class="w-5 h-5" />
                </span>
                <div class="min-w-0 text-sm leading-relaxed text-ink-950">
                    <p class="font-semibold">وثيقة موثّقة لدى فايب كلين برو</p>
                    <p class="mt-1 text-neutral-600">
                        هذه {{ $isInternal ? 'وثيقة معيار تشغيلي داخلي' : 'وثيقة' }} صادرة عن <strong>فايب كلين برو</strong>، ويمكن التحقق منها عبر رمزها
                        @if ($credential->document_code)
                            <span dir="ltr" class="font-medium">{{ $credential->document_code }}</span>
                        @endif
                        على هذه الصفحة. @if ($isInternal) وهي ليست شهادة ISO أو اعتمادًا حكوميًا. @endif
                    </p>
                </div>
            </div>

            @if ($credential->summary_ar)
                <p class="mt-8 text-lg text-ink-950 leading-relaxed">{{ $credential->summary_ar }}</p>
            @endif

            @if ($credential->body_ar)
                <div class="prose prose-reading mt-8">
                    {!! app(\App\Support\Content\PublishedLinkFilter::class)->filter($credential->body_ar) !!}
                </div>
            @endif

            <div class="mt-10 flex flex-wrap items-center gap-3 print:hidden">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 min-h-11 rounded-xl bg-ink-950 px-5 text-sm font-semibold text-white hover:bg-ink-900 transition-colors">
                    <x-public.icon name="clipboard" class="w-4 h-4" />
                    طباعة / حفظ PDF
                </button>
                <a href="{{ route('public.standalone', ['slug' => 'trust']) }}" class="inline-flex items-center gap-2 min-h-11 px-4 text-sm font-medium text-ink-950 hover:text-primary-700">
                    كل المعايير والسياسات
                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                </a>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
