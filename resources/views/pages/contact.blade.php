{{--
    Contact - "I want to reach the company", not "I want a quote" - in
    the V2 system.

    Contact and Quote are one communication system, not two designs: the
    same light tinted opening handing over to the page field, the same
    framed white form panel pulled up over the wave, the same error
    summary, the same success panel. Contact is the calmer of the two:
    the real channels (phone, WhatsApp, email - each only if
    BusinessProfile actually has it) lead as a ringed card the visitor can
    act on in one tap, and the form is one group, not a guided journey.
    Working hours and the address appear only when they are stored;
    nothing is invented about response times or availability.

    A visitor who actually wants a price is pointed to /quote in one line
    at the top, and a business visitor to ?for=business from the channels
    card, so the intents never compete inside the same form.

    ?for=business keeps its existing meaning (heading, intro, message
    prompt, and the contact_form_business source via the hidden context
    field) - same controller, same StoreLeadRequest, same fields.
--}}
@php
    $whatsappUrl = $businessProfile?->whatsappUrl($isBusinessContext
        ? 'مرحبًا، أرغب في الاستفسار عن حلول النظافة لمنشأتنا'
        : 'مرحبًا، أرغب في التواصل معكم');
    $phoneUrl = $businessProfile?->phoneUrl();
    $hasChannels = ($phoneUrl && $businessProfile?->phone) || $whatsappUrl || $businessProfile?->email;
    $businessContactUrl = route('public.contact', ['for' => 'business']);

    // The privacy policy is an editor-created Legal page at the reserved
    // slug "privacy" (see PageResource). The link exists only while that
    // page is published - never a dead link, never a consent checkbox
    // (no legal basis has been decided yet).
    $privacyPage = \App\Models\Page::query()->where('type', \App\Enums\PageType::Legal)->where('slug', 'privacy')->published()->first();
    $privacyUrl = $privacyPage ? app(\App\Seo\UrlResolver::class)->urlForPage($privacyPage) : null;

    $fieldLabels = [
        'name' => 'الاسم',
        'phone' => 'رقم الجوال',
        'email' => 'البريد الإلكتروني',
        'message' => 'الرسالة',
    ];

    $channelRow = 'group flex items-center gap-4 py-4 min-h-14 -mx-2 px-2 rounded-2xl transition-colors hover:bg-primary-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40';
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :mobile-bar="false">

    {{-- ===== 1. Opening - light tinted field, curve into the page ===== --}}
    <section class="surface-tint relative isolate overflow-hidden">
        <div class="glow-primary absolute -top-32 -end-24 w-[30rem] h-[30rem] -z-10 opacity-60" aria-hidden="true"></div>
        <x-public.container width="wide" class="relative pt-8 pb-20 md:pt-12 md:pb-28">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            <div class="max-w-2xl">
                @if ($isBusinessContext)
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-3.5 py-1.5 text-sm font-medium text-primary-700 backdrop-blur-sm">
                        <x-public.icon name="briefcase" class="w-4 h-4" />
                        حلول الشركات والمنشآت
                    </p>
                    <h1 class="mt-5 font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950 text-balance">تواصل بخصوص حلول النظافة لمنشأتك</h1>
                    <p class="mt-4 text-lg text-neutral-600 leading-relaxed">أخبرنا عن احتياج منشأتك - تنظيف دوري، عقود تشغيل، أو إدارة مرافق - وسنتواصل معك لتحديد خطة مناسبة.</p>
                @else
                    <h1 class="font-display text-[2rem] leading-[1.15] md:text-5xl md:leading-[1.08] font-medium tracking-tight text-ink-950">تواصل معنا</h1>
                    <p class="mt-4 text-lg text-neutral-600 leading-relaxed">لأي سؤال أو استفسار، اختر القناة الأنسب لك أو اترك لنا رسالة.</p>
                    <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-neutral-600">
                        تريد سعرًا لخدمة محددة؟
                        <a href="{{ route('public.quote') }}" class="inline-flex items-center gap-2 min-h-11 rounded-full bg-white/80 ring-1 ring-primary-200/70 px-4 font-medium text-primary-700 backdrop-blur-sm transition-[box-shadow] hover:ring-primary-400">
                            اطلب عرض سعر
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </p>
                @endif
            </div>
        </x-public.container>
        <x-public.wave shape="curve" position="bottom" class="text-background" />
    </section>

    {{-- ===== 2. Channels beside the form, both pulled up over the curve ===== --}}
    <x-public.container width="wide" class="relative -mt-10 md:-mt-14 pb-16 md:pb-24">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:gap-12 items-start">

            {{-- Real channels, one tap each - a ringed card. --}}
            <aside class="rounded-3xl bg-white ring-1 ring-ink-950/10 shadow-lg shadow-primary-900/5 p-6 md:p-8 reveal" aria-labelledby="contact-channels">
                <h2 id="contact-channels" class="text-sm font-medium tracking-wide text-primary-700">قنوات التواصل</h2>

                @if ($hasChannels)
                    <ul class="mt-3 divide-y divide-neutral-200">
                        @if ($phoneUrl && $businessProfile?->phone)
                            <li>
                                <a href="{{ $phoneUrl }}" class="{{ $channelRow }}">
                                    <span class="flex items-center justify-center w-11 h-11 rounded-full bg-primary-50 text-primary-700 shrink-0" aria-hidden="true">
                                        <x-public.icon name="phone" class="w-5 h-5" />
                                    </span>
                                    <span class="min-w-0 grow">
                                        <span class="block text-sm text-neutral-500">الهاتف</span>
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors" dir="ltr">{{ $businessProfile->phone }}</span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-400 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                </a>
                            </li>
                        @endif

                        @if ($whatsappUrl)
                            <li>
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $channelRow }}">
                                    <span class="flex items-center justify-center w-11 h-11 rounded-full bg-success-50 text-success-700 shrink-0" aria-hidden="true">
                                        <x-public.icon name="whatsapp" class="w-5 h-5" />
                                    </span>
                                    <span class="min-w-0 grow">
                                        <span class="block text-sm text-neutral-500">واتساب</span>
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors">ابدأ محادثة</span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-400 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                </a>
                            </li>
                        @endif

                        @if ($businessProfile?->email)
                            <li>
                                <a href="mailto:{{ $businessProfile->email }}" class="{{ $channelRow }}">
                                    <span class="flex items-center justify-center w-11 h-11 rounded-full bg-primary-50 text-primary-700 shrink-0" aria-hidden="true">
                                        <x-public.icon name="mail" class="w-5 h-5" />
                                    </span>
                                    <span class="min-w-0 grow">
                                        <span class="block text-sm text-neutral-500">البريد الإلكتروني</span>
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors break-all" dir="ltr">{{ $businessProfile->email }}</span>
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-400 rtl:rotate-180 shrink-0 transition-transform duration-300 group-hover:-translate-x-1" />
                                </a>
                            </li>
                        @endif
                    </ul>
                @else
                    <p class="mt-3 text-neutral-600 leading-relaxed">اترك لنا رسالة عبر النموذج وسنرد عليك.</p>
                @endif

                {{-- Stored facts only. --}}
                @if ($businessProfile?->working_hours || $businessProfile?->address || $businessProfile?->service_area)
                    <dl class="mt-6 pt-6 border-t border-neutral-200 space-y-5">
                        @if ($businessProfile?->working_hours)
                            <div class="flex gap-3">
                                <x-public.icon name="clock" class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" />
                                <div>
                                    <dt class="text-sm text-neutral-500">ساعات العمل</dt>
                                    @foreach ($businessProfile->working_hours as $day => $hours)
                                        <dd class="mt-1 text-neutral-800">{{ $day }}: {{ $hours }}</dd>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        {{-- A stored address only. With none, the stored service
                             area is stated instead - never an invented street. --}}
                        @if ($businessProfile?->address || $businessProfile?->service_area)
                            <div class="flex gap-3">
                                <x-public.icon name="map-pin" class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" />
                                <div>
                                    <dt class="text-sm text-neutral-500">{{ $businessProfile->address ? 'العنوان' : 'نطاق الخدمة' }}</dt>
                                    <dd class="mt-1 text-neutral-800">{{ $businessProfile->address ?: 'نقدم خدماتنا داخل '.$businessProfile->service_area.'.' }}</dd>
                                </div>
                            </div>
                        @endif
                    </dl>
                @endif

                {{-- Shown only for a real, saved profile link (a seeded
                     placeholder never reaches a visitor). --}}
                @if ($googleProfileUrl = $businessProfile?->publicGoogleBusinessProfileUrl())
                    <a href="{{ $googleProfileUrl }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center gap-1.5 min-h-11 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                        عرض ملفنا على Google
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                @endif

                <x-public.google-review-cta :business-profile="$businessProfile" class="mt-6" />

                {{-- The business path, named once - the same form with its own framing. --}}
                @if (! $isBusinessContext)
                    <div class="surface-tint relative overflow-hidden rounded-2xl ring-1 ring-primary-200/60 p-5 mt-6">
                        <p class="flex items-center gap-2 text-sm font-medium text-primary-700">
                            <x-public.icon name="briefcase" class="w-4 h-4" />
                            للشركات والمنشآت
                        </p>
                        <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">تنظيف دوري أو عقود تشغيل؟ أخبرنا عن منشأتك.</p>
                        <a href="{{ $businessContactUrl }}" class="mt-2 inline-flex items-center gap-1.5 min-h-11 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                            تواصل بخصوص منشأتك
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                @endif
            </aside>

            {{-- ===== 3. The message form / its confirmation - the framed panel ===== --}}
            <div class="reveal">
                @if ($submitted)
                    <div class="rounded-3xl bg-white ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/10 p-6 sm:p-10" role="status">
                        <p class="inline-flex items-center gap-2.5 rounded-full bg-success-50 ring-1 ring-success-100 px-3.5 py-1.5 text-sm font-medium text-success-700">
                            <x-public.icon name="check-circle" class="w-5 h-5" />
                            تم استلام رسالتك
                        </p>
                        <h2 class="mt-5 font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950">وصلتنا رسالتك</h2>
                        {{-- No channel is assumed (email is optional here) and no time is promised. --}}
                        <p class="mt-3 text-neutral-600 leading-relaxed">تم استلام رسالتك، وسنتواصل معك عبر بيانات التواصل التي أدخلتها. إن كان الأمر عاجلًا، يمكنك التواصل معنا مباشرة عبر الهاتف أو واتساب.</p>
                        @if ($whatsappUrl || $phoneUrl)
                            <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                                @if ($whatsappUrl)
                                    <x-public.button :href="$whatsappUrl" external variant="whatsapp" icon="whatsapp">تواصل عبر واتساب</x-public.button>
                                @endif
                                @if ($phoneUrl)
                                    <a href="{{ $phoneUrl }}" class="inline-flex items-center gap-2 min-h-11 text-sm font-medium text-neutral-700 hover:text-primary-700 transition-colors">
                                        <x-public.icon name="phone" class="w-4 h-4" /> أو اتصل بنا
                                    </a>
                                @endif
                            </div>
                        @endif
                        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-2 min-h-11 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                            العودة إلى الصفحة الرئيسية
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                @else
                    <div class="rounded-3xl bg-white ring-1 ring-ink-950/10 shadow-xl shadow-primary-900/10 p-5 sm:p-8 md:p-10">
                        <h2 class="font-display text-xl md:text-2xl font-medium tracking-tight text-ink-950">{{ $isBusinessContext ? 'أخبرنا عن منشأتك' : 'اترك لنا رسالة' }}</h2>
                        <p class="mt-1.5 text-sm text-neutral-500">الحقول المعلّمة بـ <span class="text-error-500" aria-hidden="true">*</span> مطلوبة.</p>

                        @if ($errors->any())
                            {{-- Focused on load so a keyboard or screen-reader user lands on
                                 the summary, whose links jump to the fields themselves. --}}
                            <div role="alert" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())"
                                class="mt-6 rounded-2xl border border-error-500/30 bg-error-50 p-4 text-sm text-error-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-error-500/40">
                                <p class="font-medium">لم تُرسل الرسالة بعد — راجع الحقول التالية:</p>
                                <ul class="mt-2 space-y-1">
                                    @foreach ($errors->keys() as $field)
                                        <li>
                                            <a href="#{{ $field }}" class="underline underline-offset-4">{{ $fieldLabels[$field] ?? $field }}</a>: {{ $errors->first($field) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('public.contact.store') }}"
                            x-data="{ submitting: false }" @submit="submitting = true"
                            class="mt-6 space-y-5">
                            @csrf

                            <div class="sr-only" aria-hidden="true">
                                <label for="website_url">اتركه فارغًا</label>
                                <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                            </div>

                            @if ($isBusinessContext)
                                <input type="hidden" name="context" value="business">
                            @endif

                            <div class="grid sm:grid-cols-2 gap-5">
                                <x-public.field.input name="name" label="الاسم" required autocomplete="name" :error="$errors->first('name')" value="{{ old('name') }}" />
                                <x-public.field.input name="phone" type="tel" label="رقم الجوال" required autocomplete="tel" inputmode="tel" dir="ltr" class="text-right" help="نستخدمه للتواصل معك." :error="$errors->first('phone')" value="{{ old('phone') }}" />
                            </div>
                            <x-public.field.input name="email" type="email" label="البريد الإلكتروني" help="اختياري." autocomplete="email" inputmode="email" dir="ltr" class="text-right" :error="$errors->first('email')" value="{{ old('email') }}" />
                            <x-public.field.textarea name="message" :label="$isBusinessContext ? 'احتياج منشأتك' : 'رسالتك'" :rows="5"
                                :help="$isBusinessContext ? 'نوع المنشأة، حجمها التقريبي، ونوع الخدمة المطلوبة.' : 'اختياري - اكتب ما تريد سؤالنا عنه.'"
                                :error="$errors->first('message')">{{ old('message') }}</x-public.field.textarea>

                            <div class="pt-2 flex flex-wrap items-center gap-x-6 gap-y-3">
                                <x-public.button type="submit" variant="primary" size="lg" class="w-full sm:w-auto"
                                    x-bind:disabled="submitting" x-bind:aria-busy="submitting">
                                    <span x-show="! submitting">إرسال الرسالة</span>
                                    <span x-show="submitting" x-cloak>جارٍ الإرسال…</span>
                                </x-public.button>
                                @if ($privacyUrl)
                                    <a href="{{ $privacyUrl }}" class="inline-flex items-center min-h-11 text-sm text-neutral-500 underline underline-offset-4 hover:text-primary-700">سياسة الخصوصية</a>
                                @endif
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </x-public.container>
</x-layouts.public>
