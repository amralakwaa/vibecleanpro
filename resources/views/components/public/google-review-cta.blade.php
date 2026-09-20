@props(['businessProfile' => null])

{{--
    Asks previous customers for an honest Google review. Renders nothing
    until a real review link is saved on the business profile. No rating is
    suggested, nothing is offered in return, and every customer is asked
    the same way (no filtering of who gets the link).
--}}
@if ($reviewUrl = $businessProfile?->publicGoogleReviewUrl())
    <div {{ $attributes->class('rounded-2xl ring-1 ring-ink-950/10 bg-white p-5') }}>
        <p class="flex items-center gap-2 text-sm font-medium text-ink-950">
            <x-public.icon name="star-outline" class="w-4 h-4 text-primary-600" />
            عميل سابق لدينا؟
        </p>
        <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">رأيك الصادق يساعد غيرك على الاختيار، ويساعدنا على التحسّن.</p>
        <a href="{{ $reviewUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1.5 min-h-11 text-sm font-medium text-primary-700 underline-offset-4 hover:underline">
            قيّم تجربتك معنا على Google
            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
        </a>
    </div>
@endif
