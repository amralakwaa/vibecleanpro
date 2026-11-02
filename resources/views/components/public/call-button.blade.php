{{--
    Direct call: the dominant action next to "request a quote", because most
    customers prefer to ring. Renders nothing when the business has no phone,
    so callers can test the same condition to demote the quote button:
        $hasCall = filled($businessProfile?->phoneUrl());
    The number itself shows from the sm breakpoint up (a desktop visitor
    cannot tap to call, but can read the number); phones just see "اتصل الآن".
--}}
@props(['businessProfile' => null, 'size' => 'lg', 'numberClass' => 'hidden sm:inline'])

@php
    $phoneUrl = $businessProfile?->phoneUrl();
    $digits = preg_replace('/\D/', '', (string) $businessProfile?->phone);
    $number = preg_match('/^05\d{8}$/', $digits) ? preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1 $2 $3', $digits) : (string) $businessProfile?->phone;
@endphp

@if ($phoneUrl)
    <x-public.button :href="$phoneUrl" variant="cta" :size="$size" icon="phone" {{ $attributes->class(['shadow-lg shadow-primary-600/25 w-full sm:w-auto']) }}>
        اتصل الآن
        <span class="{{ $numberClass }} tabular-nums" dir="ltr">{{ $number }}</span>
    </x-public.button>
@endif
