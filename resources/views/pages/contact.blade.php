{{--
    Contact - "I want to reach the company", not "I want a quote".

    Calmer than /quote on purpose. The page opens with the real channels
    (phone, WhatsApp, email - each only if BusinessProfile actually has
    it) as a ruled list the visitor can act on in one tap, then a short
    message form for anything that is not a service request. Working
    hours and the address appear only when they are stored; nothing is
    invented about response times or availability.

    A visitor who actually wants a price is pointed to /quote in one line
    at the top, so the two forms never compete for the same intent.

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

    $fieldLabels = [
        'name' => 'الاسم',
        'phone' => 'رقم الجوال',
        'email' => 'البريد الإلكتروني',
        'message' => 'الرسالة',
    ];
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :mobile-bar="false">

    {{-- ===== 1. Header ===== --}}
    <section class="bg-white border-b border-neutral-200">
        <x-public.container width="wide" class="py-10 md:py-14">
            <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-6" />
            @if ($isBusinessContext)
                <p class="text-sm font-medium tracking-wide text-primary-700">حلول الشركات والمنشآت</p>
                <h1 class="mt-3 font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950 text-balance">تواصل بخصوص حلول النظافة لمنشأتك</h1>
                <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">أخبرنا عن احتياج منشأتك - تنظيف دوري، عقود تشغيل، أو إدارة مرافق - وسنتواصل معك لتحديد خطة مناسبة.</p>
            @else
                <h1 class="font-display text-[1.75rem] md:text-5xl leading-tight md:leading-[1.1] font-medium tracking-tight text-ink-950">تواصل معنا</h1>
                <p class="mt-4 text-lg text-neutral-600 max-w-2xl leading-relaxed">لأي سؤال أو استفسار، اختر القناة الأنسب لك أو اترك لنا رسالة.</p>
                <p class="mt-4 text-sm text-neutral-600">
                    تريد سعرًا لخدمة محددة؟
                    <a href="{{ route('public.quote') }}" class="inline-flex items-center gap-1.5 min-h-11 font-medium text-primary-700 underline-offset-4 hover:underline">
                        اطلب عرض سعر
                        <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                    </a>
                </p>
            @endif
        </x-public.container>
    </section>

    <x-public.container width="wide" class="py-10 md:py-16">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)] lg:gap-20 items-start">

            {{-- ===== 2. Real channels, one tap each ===== --}}
            <div>
                <h2 class="text-sm font-medium tracking-wide text-neutral-500">قنوات التواصل</h2>

                @if ($hasChannels)
                    <ul class="mt-3">
                        @if ($phoneUrl && $businessProfile?->phone)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $phoneUrl }}" class="group flex items-center justify-between gap-4 py-4 min-h-14">
                                    <span>
                                        <span class="block text-sm text-neutral-500">الهاتف</span>
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors" dir="ltr">{{ $businessProfile->phone }}</span>
                                    </span>
                                    <x-public.icon name="phone" class="w-5 h-5 text-neutral-400 group-hover:text-primary-600 shrink-0 transition-colors" />
                                </a>
                            </li>
                        @endif

                        @if ($whatsappUrl)
                            <li class="border-b border-neutral-200">
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="group flex items-center justify-between gap-4 py-4 min-h-14">
                                    <span>
                                        <span class="block text-sm text-neutral-500">واتساب</span>
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors">ابدأ محادثة</span>
                                    </span>
                                    <x-public.icon name="whatsapp" class="w-5 h-5 text-success-700 shrink-0" />
                                </a>
                            </li>
                        @endif

                        @if ($businessProfile?->email)
                            <li class="border-b border-neutral-200">
                                <a href="mailto:{{ $businessProfile->email }}" class="group flex items-center justify-between gap-4 py-4 min-h-14">
                                    <span class="min-w-0">
                                        <span class="block text-sm text-neutral-500">البريد الإلكتروني</span>
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors break-all" dir="ltr">{{ $businessProfile->email }}</span>
                                    </span>
                                    <x-public.icon name="mail" class="w-5 h-5 text-neutral-400 group-hover:text-primary-600 shrink-0 transition-colors" />
                                </a>
                            </li>
                        @endif
                    </ul>
                @else
                    <p class="mt-3 text-neutral-600 leading-relaxed">اترك لنا رسالة عبر النموذج وسنرد عليك.</p>
                @endif

                {{-- Stored facts only. --}}
                @if ($businessProfile?->working_hours || $businessProfile?->address)
                    <dl class="mt-8 space-y-5">
                        @if ($businessProfile?->working_hours)
                            <div>
                                <dt class="text-sm text-neutral-500">ساعات العمل</dt>
                                @foreach ($businessProfile->working_hours as $day => $hours)
                                    <dd class="mt-1 text-neutral-800">{{ $day }}: {{ $hours }}</dd>
                                @endforeach
                            </div>
                        @endif
                        @if ($businessProfile?->address)
                            <div>
                                <dt class="text-sm text-neutral-500">العنوان</dt>
                                <dd class="mt-1 text-neutral-800">{{ $businessProfile->address }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>

            {{-- ===== 3. The message form / its confirmation ===== --}}
            <div>
                @if ($submitted)
                    <div class="bg-white border border-neutral-200 rounded-2xl p-6 sm:p-8" role="status">
                        <p class="flex items-center gap-2.5 text-sm font-medium tracking-wide text-success-700">
                            <x-public.icon name="check-circle" class="w-5 h-5" />
                            تم استلام رسالتك
                        </p>
                        <h2 class="mt-3 font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950">وصلتنا رسالتك</h2>
                        <p class="mt-3 text-neutral-600 leading-relaxed">سنرد عليك على رقم الجوال الذي أدخلته. إن كان الأمر عاجلًا، فالهاتف وواتساب أسرع طريق.</p>
                        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-2 min-h-11 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
                            العودة إلى الصفحة الرئيسية
                            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                        </a>
                    </div>
                @else
                    <h2 class="text-sm font-medium tracking-wide text-neutral-500 mb-3">{{ $isBusinessContext ? 'أخبرنا عن منشأتك' : 'اترك لنا رسالة' }}</h2>

                    @if ($errors->any())
                        <div role="alert" class="mb-6 rounded-xl border border-error-500/30 bg-error-50 p-4 text-sm text-error-600">
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
                        class="bg-white border border-neutral-200 rounded-2xl p-5 sm:p-8 space-y-5">
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
                            <x-public.field.input name="phone" type="tel" label="رقم الجوال" required autocomplete="tel" inputmode="tel" dir="ltr" class="text-right" :error="$errors->first('phone')" value="{{ old('phone') }}" />
                        </div>
                        <x-public.field.input name="email" type="email" label="البريد الإلكتروني" help="اختياري." autocomplete="email" inputmode="email" dir="ltr" class="text-right" :error="$errors->first('email')" value="{{ old('email') }}" />
                        <x-public.field.textarea name="message" :label="$isBusinessContext ? 'احتياج منشأتك' : 'رسالتك'" :rows="5"
                            :help="$isBusinessContext ? 'نوع المنشأة، حجمها التقريبي، ونوع الخدمة المطلوبة.' : null"
                            :error="$errors->first('message')">{{ old('message') }}</x-public.field.textarea>

                        <x-public.button type="submit" variant="primary" size="lg" class="w-full sm:w-auto"
                            x-bind:disabled="submitting" x-bind:aria-busy="submitting">
                            <span x-show="! submitting">إرسال الرسالة</span>
                            <span x-show="submitting" x-cloak>جارٍ الإرسال…</span>
                        </x-public.button>
                    </form>
                @endif
            </div>
        </div>
    </x-public.container>
</x-layouts.public>
