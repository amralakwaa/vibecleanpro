@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في التواصل معكم');
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="wide" class="!pb-6">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />
        @if ($isBusinessContext)
            <x-public.badge tone="primary" class="mb-3">حلول الشركات والمنشآت</x-public.badge>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">تواصل بخصوص حلول النظافة لمنشأتك</h1>
            <p class="mt-3 text-neutral-600 max-w-2xl">أخبرنا عن احتياج منشأتك أو شركتك - تنظيف دوري، عقود تشغيل، أو إدارة مرافق - وسيتواصل معك فريقنا لتحديد خطة مناسبة.</p>
        @else
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-neutral-900">تواصل معنا</h1>
            <p class="mt-3 text-neutral-600 max-w-2xl">يسعدنا تواصلك معنا لأي استفسار.</p>
        @endif
    </x-public.section>

    <x-public.section width="wide" class="!pt-0">
        <div class="grid lg:grid-cols-[1fr_1.3fr] gap-10">
            <div class="space-y-5">
                @if ($phoneUrl && $businessProfile?->phone)
                    <x-public.card class="flex items-center gap-4">
                        <span class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                            <x-public.icon name="phone" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-sm text-neutral-500">الهاتف</p>
                            <a href="{{ $phoneUrl }}" class="font-medium text-neutral-900 hover:text-primary-700">{{ $businessProfile->phone }}</a>
                        </div>
                    </x-public.card>
                @endif

                @if ($whatsappUrl)
                    <x-public.card class="flex items-center gap-4">
                        <span class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                            <x-public.icon name="whatsapp" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-sm text-neutral-500">واتساب</p>
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-neutral-900 hover:text-primary-700">
                                تواصل عبر واتساب
                            </a>
                        </div>
                    </x-public.card>
                @endif

                @if ($businessProfile?->email)
                    <x-public.card class="flex items-center gap-4">
                        <span class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                            <x-public.icon name="mail" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-sm text-neutral-500">البريد الإلكتروني</p>
                            <a href="mailto:{{ $businessProfile->email }}" class="font-medium text-neutral-900 hover:text-primary-700">{{ $businessProfile->email }}</a>
                        </div>
                    </x-public.card>
                @endif

                @if ($businessProfile?->working_hours)
                    <x-public.card class="flex items-start gap-4">
                        <span class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                            <x-public.icon name="clock" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-sm text-neutral-500 mb-1">ساعات العمل</p>
                            @foreach ($businessProfile->working_hours as $day => $hours)
                                <p class="text-sm text-neutral-700">{{ $day }}: {{ $hours }}</p>
                            @endforeach
                        </div>
                    </x-public.card>
                @endif

                @if ($businessProfile?->address)
                    <x-public.card class="flex items-start gap-4">
                        <span class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                            <x-public.icon name="map-pin" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-sm text-neutral-500">الموقع</p>
                            <p class="font-medium text-neutral-900">{{ $businessProfile->address }}</p>
                        </div>
                    </x-public.card>
                @endif

                @if (! $phoneUrl && ! $whatsappUrl && ! $businessProfile?->email)
                    <x-public.empty-state icon="mail" title="بيانات التواصل غير متوفرة حاليًا" />
                @endif
            </div>

            <div>
                @if ($submitted)
                    <x-public.alert tone="success">
                        تم استلام رسالتك بنجاح، سيتواصل معك فريقنا قريبًا.
                    </x-public.alert>
                @else
                    @if ($errors->any())
                        <x-public.alert tone="error" class="mb-5">
                            يرجى تصحيح الأخطاء التالية قبل الإرسال.
                        </x-public.alert>
                    @endif

                    <form method="POST" action="{{ route('public.contact.store') }}" class="space-y-5">
                        @csrf

                        <div class="sr-only">
                            <label for="website_url">اتركه فارغًا</label>
                            <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                        </div>

                        @if ($isBusinessContext)
                            <input type="hidden" name="context" value="business">
                        @endif

                        <x-public.field.input name="name" label="الاسم الكامل" required :error="$errors->first('name')" value="{{ old('name') }}" />
                        <x-public.field.input name="phone" type="tel" label="رقم الجوال" required :error="$errors->first('phone')" value="{{ old('phone') }}" />
                        <x-public.field.input name="email" type="email" label="البريد الإلكتروني (اختياري)" :error="$errors->first('email')" value="{{ old('email') }}" />
                        <x-public.field.textarea name="message" :label="$isBusinessContext ? 'أخبرنا عن احتياجات منشأتك' : 'رسالتك'" :error="$errors->first('message')">{{ old('message') }}</x-public.field.textarea>

                        <x-public.button type="submit" variant="primary" size="lg" class="w-full">إرسال الرسالة</x-public.button>
                    </form>
                @endif
            </div>
        </div>
    </x-public.section>
</x-layouts.public>
