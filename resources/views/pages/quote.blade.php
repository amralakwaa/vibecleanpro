{{--
    Quote - the site's main conversion form, kept short and guided.

    Same six fields as before (name, phone, service, area, message, and
    the honeypot), same controller, same StoreLeadRequest, same
    post/redirect/get - nothing about what is stored or validated moved.
    What changed is the journey around them:

      - the form asks "what do you need" (service, area) before "how do
        we reach you" (name, phone), so a visitor arriving from a Service
        or Area page sees their choice confirmed at the top instead of
        starting with a blank name field
      - ?service= / ?area= prefill is stated in words above the form
        when it resolves to a real published entry, and silently ignored
        when it does not (unchanged semantics; the select simply has no
        matching option)
      - the side rail says only what the lead pipeline actually does
        (LeadStatus: new -> contacted -> quoted) and that there is no
        payment on the site. No response-time promise, no guarantees.
      - WhatsApp is a secondary path under the form, carrying the chosen
        service/area in the message when they are real
      - the sticky mobile bar is off on this page: its "طلب خدمة" button
        would sit under the very form it links to

    The success state names the next real step (a call to the number
    given) and hands over WhatsApp/phone for anyone who wants to move
    faster - without inventing a time.
--}}
@php
    $requestedService = $services->firstWhere('id', old('service_id', request()->integer('service')));
    $requestedArea = $areas->firstWhere('id', old('area_id', request()->integer('area')));

    $whatsappMessage = 'مرحبًا، أرغب في طلب عرض سعر'
        .($requestedService ? ' لخدمة '.$requestedService->name : '')
        .($requestedArea ? ' في '.$requestedArea->name : '');
    $whatsappUrl = $businessProfile?->whatsappUrl($whatsappMessage);
    $phoneUrl = $businessProfile?->phoneUrl();

    // Only what is certain to happen: the request is stored with what
    // was chosen, and the phone number given is how the team follows up.
    // LeadStatus is a free admin field (any order, incl. lost/spam), so
    // no third "then you get the quote" step is promised here.
    $nextSteps = [
        'نستلم طلبك مع الخدمة والمنطقة التي حددتها.',
        'نتواصل معك على رقم الجوال لتأكيد التفاصيل.',
    ];

    $fieldLabels = [
        'name' => 'الاسم',
        'phone' => 'رقم الجوال',
        'service_id' => 'الخدمة',
        'area_id' => 'المنطقة',
        'message' => 'تفاصيل الطلب',
    ];
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :mobile-bar="false">
    @if ($submitted)
        {{-- ===== Success: honest next step, then the faster channels ===== --}}
        <section class="bg-white border-b border-neutral-200">
            <x-public.container width="narrow" class="py-14 md:py-20">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
                <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-success-700" role="status">
                    <x-public.icon name="check-circle" class="w-5 h-5" />
                    تم استلام طلبك بنجاح
                </p>
                <h1 class="mt-4 font-display text-[1.75rem] md:text-4xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">وصلنا طلبك</h1>
                <p class="mt-4 text-lg text-neutral-600 leading-relaxed">الخطوة التالية عندنا: نتواصل معك على رقم الجوال الذي أدخلته لتأكيد التفاصيل.</p>

                @if ($whatsappUrl || $phoneUrl)
                    <div class="mt-8 border-t border-neutral-200 pt-6">
                        <p class="text-sm font-medium tracking-wide text-neutral-500">تفضّل التواصل الآن؟</p>
                        <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-3">
                            @if ($whatsappUrl)
                                <x-public.button :href="$whatsappUrl" external variant="whatsapp" icon="whatsapp">تواصل عبر واتساب</x-public.button>
                            @endif
                            @if ($phoneUrl)
                                <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                                    <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 min-h-11 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                    العودة إلى الصفحة الرئيسية
                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                </a>
            </x-public.container>
        </section>
    @else
        {{-- ===== 1. Header ===== --}}
        <section class="bg-white border-b border-neutral-200">
            <x-public.container width="wide" class="py-10 md:py-14">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
                <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">اطلب عرض سعر</h1>
                <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">حدد ما تحتاجه ورقم جوالك، ونعود إليك بعرض سعر لمكانك.</p>
            </x-public.container>
        </section>

        <x-public.container width="wide" class="py-10 md:py-16">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16 items-start">

                {{-- ===== 2. The form ===== --}}
                <div>
                    {{-- Prefill, stated in words - only when it resolved to something real. --}}
                    @if ($requestedService || $requestedArea)
                        <p class="mb-6 flex flex-wrap items-baseline gap-x-2 text-sm text-neutral-600">
                            <span>تطلب:</span>
                            @if ($requestedService)
                                <span class="font-medium text-ink-950">{{ $requestedService->name }}</span>
                            @endif
                            @if ($requestedService && $requestedArea)
                                <span aria-hidden="true" class="text-neutral-300">·</span>
                            @endif
                            @if ($requestedArea)
                                <span class="font-medium text-ink-950">في {{ $requestedArea->name }}</span>
                            @endif
                            <span class="text-neutral-500">— يمكنك تغيير ذلك أدناه.</span>
                        </p>
                    @endif

                    @if ($errors->any())
                        <div role="alert" class="mb-6 rounded-xl border border-error-500/30 bg-error-50 p-4 text-sm text-error-600">
                            <p class="font-medium">لم يُرسل الطلب بعد — راجع الحقول التالية:</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($errors->keys() as $field)
                                    <li>
                                        <a href="#{{ $field }}" class="underline underline-offset-4">{{ $fieldLabels[$field] ?? $field }}</a>: {{ $errors->first($field) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.quote.store') }}"
                        x-data="{ submitting: false }" @submit="submitting = true"
                        class="bg-white border border-neutral-200 rounded-2xl p-5 sm:p-8">
                        @csrf

                        {{-- Honeypot: hidden from real visitors via CSS, never via
                             type="hidden" (some bots skip those) - see
                             QuoteController::store(). --}}
                        <div class="sr-only" aria-hidden="true">
                            <label for="website_url">اتركه فارغًا</label>
                            <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                        </div>

                        <fieldset>
                            <legend class="font-display text-lg font-medium text-ink-950">ماذا تحتاج؟</legend>
                            <div class="mt-4 grid sm:grid-cols-2 gap-5">
                                <x-public.field.select name="service_id" label="الخدمة"
                                    :options="['' => 'اختر الخدمة'] + $services->pluck('name', 'id')->all()" :error="$errors->first('service_id')" :selected="$requestedService?->id" />
                                <x-public.field.select name="area_id" label="المنطقة"
                                    :options="['' => 'اختر المنطقة'] + $areas->pluck('name', 'id')->all()" :error="$errors->first('area_id')" :selected="$requestedArea?->id" />
                            </div>
                            <p class="mt-2 text-sm text-neutral-500">الحقلان اختياريان — إن لم تجد ما يناسبك، اكتبه في التفاصيل.</p>
                        </fieldset>

                        <fieldset class="mt-8 pt-8 border-t border-neutral-200">
                            <legend class="font-display text-lg font-medium text-ink-950">كيف نتواصل معك؟</legend>
                            <div class="mt-4 grid sm:grid-cols-2 gap-5">
                                <x-public.field.input name="name" label="الاسم" required autocomplete="name" :error="$errors->first('name')" value="{{ old('name') }}" />
                                <x-public.field.input name="phone" type="tel" label="رقم الجوال" required autocomplete="tel" inputmode="tel" dir="ltr" class="text-right"
                                    help="نتواصل معك على هذا الرقم لتأكيد الطلب." :error="$errors->first('phone')" value="{{ old('phone') }}" />
                            </div>
                        </fieldset>

                        <div class="mt-8 pt-8 border-t border-neutral-200">
                            <x-public.field.textarea name="message" label="تفاصيل الطلب" :rows="4"
                                help="اختياري. مثال: عدد الغرف أو المساحة التقريبية، الوقت المناسب لك." :error="$errors->first('message')">{{ old('message') }}</x-public.field.textarea>
                        </div>

                        <div class="mt-8">
                            <x-public.button type="submit" variant="cta" size="lg" icon="check-circle" class="w-full"
                                x-bind:disabled="submitting" x-bind:aria-busy="submitting">
                                <span x-show="! submitting">إرسال الطلب</span>
                                <span x-show="submitting" x-cloak>جارٍ الإرسال…</span>
                            </x-public.button>
                            <p class="mt-3 text-center text-sm text-neutral-500">لا يوجد دفع عبر الموقع — تطلب عرض السعر ونتواصل معك.</p>
                        </div>
                    </form>

                    {{-- WhatsApp: secondary, outside the form, never beside the submit. --}}
                    @if ($whatsappUrl)
                        <p class="mt-6 text-sm text-neutral-600">
                            تفضّل الكتابة مباشرة؟
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 min-h-11 font-medium text-neutral-800 hover:text-primary-700 underline-offset-4 hover:underline transition-colors">
                                <x-public.icon name="whatsapp" class="w-4 h-4 text-success-700" /> أرسل الطلب عبر واتساب
                            </a>
                        </p>
                    @endif
                </div>

                {{-- ===== 3. Side rail: what really happens next ===== --}}
                <aside class="lg:pt-2">
                    <h2 class="text-sm font-medium tracking-wide text-neutral-500">ماذا يحدث بعد الإرسال؟</h2>
                    <ol class="mt-4">
                        @foreach ($nextSteps as $step)
                            <li class="flex gap-4 py-3.5 border-b border-neutral-200 last:border-b-0">
                                <span class="font-display text-sm text-primary-600 tabular-nums shrink-0 pt-0.5" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-neutral-700 leading-relaxed">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>

                    @if ($phoneUrl && $businessProfile?->phone)
                        <div class="mt-8 pt-6 border-t border-neutral-200">
                            <p class="text-sm font-medium tracking-wide text-neutral-500">تفضّل الاتصال؟</p>
                            <a href="{{ $phoneUrl }}" dir="ltr" class="mt-2 inline-flex items-center gap-2 min-h-11 font-medium text-ink-950 hover:text-primary-700 transition-colors">
                                <x-public.icon name="phone" class="w-4 h-4" /> {{ $businessProfile->phone }}
                            </a>
                        </div>
                    @endif
                </aside>
            </div>
        </x-public.container>
    @endif
</x-layouts.public>
