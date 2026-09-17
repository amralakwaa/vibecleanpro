{{--
    Quote - the site's main conversion form, in the V2 system.

    Same six fields as before (name, phone, service, area, message, and
    the honeypot), same controller, same StoreLeadRequest, same
    post/redirect/get - nothing about what is stored or validated moved.
    The flow is unchanged too: need (service, area) -> contact method
    (name, phone) -> details -> submit, with ?service= / ?area= prefill.

    What V2 adds is the frame around that flow, shared with Contact so
    the two read as one communication system: a light tinted opening, the
    form as a framed white panel pulled up over the curve, and the side
    rail as a tinted card. Inside the panel the three groups carry a
    quiet 01/02/03 progression - visual grouping, never a wizard that
    hides fields behind "next" buttons.

    Context is real or absent: the prefilled service/area are named as
    pills under the heading (with the service's PublicPrice only when
    one is published), and a live line above the submit echoes whatever
    is selected in the form right now. The side rail says only what the
    lead pipeline actually does (LeadStatus: new -> contacted -> quoted),
    that this is a quote request and not a paid booking, and that there
    is no payment on the site. No response-time promise, no guarantees.

    WhatsApp is a secondary path under the form, carrying the chosen
    service/area in the message when they are real. The sticky mobile bar
    is off on this page: its "طلب خدمة" button would sit under the very
    form it links to, and nothing here is sticky over the keyboard.

    The success state names the next real step (a call to the number
    given) and hands over WhatsApp/phone for anyone who wants to move
    faster - without inventing a time.
--}}
@php
    $requestedService = $services->firstWhere('id', old('service_id', request()->integer('service')));
    $requestedArea = $areas->firstWhere('id', old('area_id', request()->integer('area')));
    // PublicPrice is the only price source: null (quote_only / hidden)
    // renders nothing, never a stand-in label.
    $requestedPrice = $requestedService?->publicPrice();

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

    // The privacy policy is an editor-created Legal page at the reserved
    // slug "privacy" (see PageResource). The link exists only while that
    // page is published - never a dead link, never a consent checkbox
    // (no legal basis has been decided yet).
    $privacyPage = \App\Models\Page::query()->where('type', \App\Enums\PageType::Legal)->where('slug', 'privacy')->published()->first();
    $privacyUrl = $privacyPage ? app(\App\Seo\UrlResolver::class)->urlForPage($privacyPage) : null;

    $fieldLabels = [
        'name' => 'الاسم',
        'phone' => 'رقم الجوال',
        'service_id' => 'الخدمة',
        'area_id' => 'المنطقة',
        'message' => 'تفاصيل الطلب',
    ];

    $pill = 'inline-flex items-center gap-2 min-h-11 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-4 text-sm text-ink-950 backdrop-blur-sm';
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :mobile-bar="false">
    @if ($submitted)
        {{-- ===== Success: honest next step, then the faster channels ===== --}}
        <section class="surface-tint relative isolate overflow-hidden">
            <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>
            <x-public.container width="narrow" class="relative pt-8 pb-16 md:pt-12 md:pb-24">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
                <div class="rounded-3xl bg-white ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/10 p-6 sm:p-10 reveal">
                    <p class="inline-flex items-center gap-2.5 rounded-full bg-success-50 ring-1 ring-success-100 px-3.5 py-1.5 text-sm font-medium text-success-700" role="status">
                        <x-public.icon name="check-circle" class="w-5 h-5" />
                        تم استلام طلبك بنجاح
                    </p>
                    <h1 class="mt-5 font-display text-[1.9rem] leading-[1.2] md:text-4xl md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">وصلنا طلبك</h1>
                    {{-- No channel is assumed and no time is promised. --}}
                    <p class="mt-4 text-lg text-neutral-600 leading-relaxed">تم استلام طلب عرض السعر، وسنتواصل معك عبر بيانات التواصل التي أدخلتها لتأكيد التفاصيل.</p>

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
                </div>
            </x-public.container>
        </section>
    @else
        {{-- ===== 1. Opening - light tinted field, the request in context ===== --}}
        <section class="surface-tint relative isolate overflow-hidden">
            <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>
            <x-public.container width="wide" class="relative pt-8 pb-20 md:pt-12 md:pb-28">
                <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700 backdrop-blur-sm">
                        <x-public.icon name="check-circle" class="w-4 h-4" />
                        طلب عرض سعر
                    </p>
                    <h1 class="mt-5 font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">اطلب عرض سعر</h1>
                    <p class="mt-4 text-lg text-neutral-600 leading-relaxed">حدد ما تحتاجه ورقم جوالك، ونعود إليك بعرض سعر لمكانك.</p>

                    {{-- Prefill, stated in words - only when it resolved to something real. --}}
                    @if ($requestedService || $requestedArea)
                        <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-neutral-600">
                            <span>تطلب:</span>
                            @if ($requestedService)
                                <span class="{{ $pill }}">
                                    <x-public.icon name="sparkles" class="w-4 h-4 text-primary-600" />
                                    <span class="font-medium">{{ $requestedService->name }}</span>
                                    @if ($requestedPrice)
                                        <span class="text-primary-700 tabular-nums">· {{ $requestedPrice->label() }}</span>
                                    @endif
                                </span>
                            @endif
                            @if ($requestedArea)
                                <span class="{{ $pill }}">
                                    <x-public.icon name="map-pin" class="w-4 h-4 text-primary-600" />
                                    <span class="font-medium">في {{ $requestedArea->name }}</span>
                                </span>
                            @endif
                            <span class="text-neutral-500">— يمكنك تغيير ذلك أدناه.</span>
                        </div>
                    @endif
                </div>
            </x-public.container>
            <x-public.wave shape="curve" position="bottom" class="text-background" />
        </section>

        <x-public.container width="wide" class="relative -mt-10 md:-mt-14 pb-16 md:pb-24">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-12 items-start">

                {{-- ===== 2. The form - one framed panel, three quiet steps ===== --}}
                <div class="reveal">
                    <div class="rounded-3xl bg-white ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/10 p-5 sm:p-8 md:p-10"
                        x-data="{
                            submitting: false,
                            service: '{{ $requestedService?->id }}',
                            area: '{{ $requestedArea?->id }}',
                            services: @js($services->pluck('name', 'id')->all()),
                            areas: @js($areas->pluck('name', 'id')->all()),
                        }">
                        <p class="text-sm text-neutral-500">الحقول المعلّمة بـ <span class="text-error-500" aria-hidden="true">*</span> مطلوبة.</p>

                        @if ($errors->any())
                            {{-- Focused on load so a keyboard or screen-reader user lands on
                                 the summary, whose links jump to the fields themselves. --}}
                            <div role="alert" tabindex="-1" x-init="$nextTick(() => $el.focus())"
                                class="mt-4 rounded-2xl border border-error-500/30 bg-error-50 p-4 text-sm text-error-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-error-500/40">
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

                        <form method="POST" action="{{ route('public.quote.store') }}" @submit="submitting = true" class="mt-5">
                            @csrf

                            {{-- Honeypot: hidden from real visitors via CSS, never via
                                 type="hidden" (some bots skip those) - see
                                 QuoteController::store(). --}}
                            <div class="sr-only" aria-hidden="true">
                                <label for="website_url">اتركه فارغًا</label>
                                <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                            </div>

                            <fieldset>
                                <legend class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full bg-primary-50 font-display text-sm font-medium text-primary-700 tabular-nums" aria-hidden="true">01</span>
                                    <span class="font-display text-lg md:text-xl font-medium tracking-tight text-ink-950">ماذا تحتاج؟</span>
                                </legend>
                                <div class="mt-4 grid sm:grid-cols-2 gap-5">
                                    <x-public.field.select name="service_id" label="الخدمة" x-model="service"
                                        :options="['' => 'اختر الخدمة'] + $services->pluck('name', 'id')->all()" :error="$errors->first('service_id')" :selected="$requestedService?->id" />
                                    <x-public.field.select name="area_id" label="المنطقة" x-model="area"
                                        :options="['' => 'اختر المنطقة'] + $areas->pluck('name', 'id')->all()" :error="$errors->first('area_id')" :selected="$requestedArea?->id" />
                                </div>
                                <p class="mt-2 text-sm text-neutral-500">الحقلان اختياريان — إن لم تجد ما يناسبك، اكتبه في التفاصيل.</p>
                            </fieldset>

                            <fieldset class="mt-8 pt-8 border-t border-neutral-200">
                                <legend class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full bg-primary-50 font-display text-sm font-medium text-primary-700 tabular-nums" aria-hidden="true">02</span>
                                    <span class="font-display text-lg md:text-xl font-medium tracking-tight text-ink-950">كيف نتواصل معك؟</span>
                                </legend>
                                <div class="mt-4 grid sm:grid-cols-2 gap-5">
                                    <x-public.field.input name="name" label="الاسم" required autocomplete="name" :error="$errors->first('name')" value="{{ old('name') }}" />
                                    <x-public.field.input name="phone" type="tel" label="رقم الجوال" required autocomplete="tel" inputmode="tel" dir="ltr" class="text-right"
                                        help="نتواصل معك على هذا الرقم لتأكيد الطلب." :error="$errors->first('phone')" value="{{ old('phone') }}" />
                                </div>
                            </fieldset>

                            <fieldset class="mt-8 pt-8 border-t border-neutral-200">
                                <legend class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full bg-primary-50 font-display text-sm font-medium text-primary-700 tabular-nums" aria-hidden="true">03</span>
                                    <span class="font-display text-lg md:text-xl font-medium tracking-tight text-ink-950">تفاصيل إضافية</span>
                                </legend>
                                <div class="mt-4">
                                    <x-public.field.textarea name="message" label="تفاصيل الطلب" :rows="4"
                                        help="اختياري. مثال: عدد الغرف أو المساحة التقريبية، الوقت المناسب لك." :error="$errors->first('message')">{{ old('message') }}</x-public.field.textarea>
                                </div>
                            </fieldset>

                            <div class="mt-8 pt-6 border-t border-neutral-200">
                                {{-- What the form holds right now, echoed from the selects
                                     (progressive: the pills above already state a prefill). --}}
                                <p class="mb-4 flex flex-wrap items-center gap-x-2 text-sm text-neutral-600" x-cloak x-show="service || area" aria-live="polite">
                                    <span>طلبك:</span>
                                    <span class="font-medium text-ink-950" x-show="service" x-text="services[service]"></span>
                                    <span class="font-medium text-ink-950" x-show="area" x-text="'في ' + areas[area]"></span>
                                </p>
                                <x-public.button type="submit" variant="cta" size="lg" icon="check-circle" class="w-full"
                                    x-bind:disabled="submitting" x-bind:aria-busy="submitting">
                                    <span x-show="! submitting">إرسال الطلب</span>
                                    <span x-show="submitting" x-cloak>جارٍ الإرسال…</span>
                                </x-public.button>
                                <p class="mt-3 text-center text-sm text-neutral-500">
                                    لا يوجد دفع عبر الموقع — هذا طلب عرض سعر وليس حجزًا مدفوعًا.
                                    @if ($privacyUrl)
                                        <a href="{{ $privacyUrl }}" class="inline-flex items-center min-h-11 underline underline-offset-4 hover:text-primary-700">سياسة الخصوصية</a>
                                    @endif
                                </p>
                            </div>
                        </form>
                    </div>

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

                {{-- ===== 3. Side rail: the chosen service, what really happens next ===== --}}
                <aside class="space-y-5 reveal">
                    @if ($requestedService)
                        <div class="rounded-3xl bg-white ring-1 ring-ink-950/10 shadow-lg shadow-primary-900/5 p-6">
                            <p class="text-sm font-medium tracking-wide text-primary-700">الخدمة المختارة</p>
                            <p class="mt-2 font-display text-xl font-medium tracking-tight text-ink-950">{{ $requestedService->name }}</p>
                            @if ($requestedPrice)
                                <p class="mt-1 text-sm text-neutral-600 tabular-nums">{{ $requestedPrice->label() }}</p>
                            @endif
                            @if ($requestedService->short_description)
                                <p class="mt-3 text-sm text-neutral-600 leading-relaxed">{{ $requestedService->short_description }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="surface-tint relative overflow-hidden rounded-3xl ring-1 ring-primary-200/60 p-6">
                        <div class="glow-primary absolute -top-16 -end-16 w-40 h-40 opacity-60" aria-hidden="true"></div>
                        <h2 class="relative text-sm font-medium tracking-wide text-primary-700">ماذا يحدث بعد الإرسال؟</h2>
                        <ol class="relative mt-4 space-y-4">
                            @foreach ($nextSteps as $step)
                                <li class="flex gap-3.5">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-white ring-1 ring-primary-200/70 font-display text-sm text-primary-700 tabular-nums shrink-0" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="text-neutral-700 leading-relaxed pt-1">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <p class="relative mt-5 pt-5 border-t border-primary-200/60 flex items-start gap-2.5 text-sm text-neutral-700 leading-relaxed">
                            <x-public.icon name="shield-check" class="w-5 h-5 text-primary-600 shrink-0" />
                            <span>طلب عرض سعر فقط: لا يوجد دفع أو حجز مدفوع عبر الموقع.</span>
                        </p>
                    </div>

                    @if ($phoneUrl && $businessProfile?->phone)
                        <div class="px-2">
                            <p class="text-sm font-medium tracking-wide text-neutral-500">تفضّل الاتصال؟</p>
                            <a href="{{ $phoneUrl }}" dir="ltr" class="mt-1 inline-flex items-center gap-2 min-h-11 font-medium text-ink-950 hover:text-primary-700 transition-colors">
                                <x-public.icon name="phone" class="w-4 h-4" /> {{ $businessProfile->phone }}
                            </a>
                        </div>
                    @endif
                </aside>
            </div>
        </x-public.container>
    @endif
</x-layouts.public>
