@php
    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، أرغب في طلب عرض سعر');
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    <x-public.section width="narrow">
        <x-public.breadcrumb :items="$seo->breadcrumbs" class="mb-5" />

        @if ($submitted)
            <div class="text-center py-10">
                <span class="mx-auto w-14 h-14 rounded-full bg-success-50 text-success-600 flex items-center justify-center">
                    <x-public.icon name="check-circle" class="w-7 h-7" />
                </span>
                <h1 class="mt-5 text-2xl font-bold text-neutral-900">تم استلام طلبك بنجاح</h1>
                <p class="mt-2 text-neutral-600">سيتواصل معك فريقنا في أقرب وقت ممكن.</p>

                @if ($whatsappUrl || $phoneUrl)
                    <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                        @if ($whatsappUrl)
                            <x-public.button :href="$whatsappUrl" external variant="whatsapp" icon="whatsapp">تواصل الآن عبر واتساب</x-public.button>
                        @endif
                        @if ($phoneUrl)
                            <x-public.button :href="$phoneUrl" variant="secondary" icon="phone">اتصل بنا الآن</x-public.button>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <h1 class="text-3xl font-bold tracking-tight text-neutral-900">اطلب عرض سعر</h1>
            <p class="mt-3 text-neutral-600">أخبرنا عن احتياجك وسنتواصل معك بعرض سعر مناسب.</p>

            @if ($errors->any())
                <x-public.alert tone="error" class="mt-6">
                    يرجى تصحيح الأخطاء التالية قبل الإرسال.
                </x-public.alert>
            @endif

            <form method="POST" action="{{ route('public.quote.store') }}" class="mt-8 space-y-5">
                @csrf

                {{-- Honeypot: hidden from real visitors via CSS, never via
                     type="hidden" (some bots skip those) - see
                     QuoteController::store(). --}}
                <div class="sr-only">
                    <label for="website_url">اتركه فارغًا</label>
                    <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <x-public.field.input name="name" label="الاسم الكامل" required :error="$errors->first('name')" value="{{ old('name') }}" />
                <x-public.field.input name="phone" type="tel" label="رقم الجوال" required :error="$errors->first('phone')" value="{{ old('phone') }}" />

                <div class="grid sm:grid-cols-2 gap-5">
                    <x-public.field.select name="service_id" label="الخدمة (اختياري)" placeholder="اختر الخدمة"
                        :options="$services->pluck('name', 'id')" :error="$errors->first('service_id')" :selected="old('service_id', request()->integer('service'))" />
                    <x-public.field.select name="area_id" label="المنطقة (اختياري)" placeholder="اختر المنطقة"
                        :options="$areas->pluck('name', 'id')" :error="$errors->first('area_id')" :selected="old('area_id', request()->integer('area'))" />
                </div>

                <x-public.field.textarea name="message" label="تفاصيل الطلب (اختياري)" :error="$errors->first('message')">{{ old('message') }}</x-public.field.textarea>

                <x-public.button type="submit" variant="cta" size="lg" class="w-full">إرسال الطلب</x-public.button>
            </form>
        @endif
    </x-public.section>
</x-layouts.public>
