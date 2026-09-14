{{--
    Editorial service row - the replacement for the service card grid on
    the homepage. Image on one side, copy on the other, and the caller
    alternates $flip so consecutive rows mirror each other instead of
    marching down an identical grid.

    No card: no border, no shadow, no surface. The image itself carries
    the weight, and a hairline separates one row from the next.

    When a Service has no featured image the row renders as a text-only
    editorial entry rather than a grey placeholder box - an empty frame
    would advertise the missing photo instead of hiding it.
--}}
@props([
    'service',
    'url',
    'flip' => false,
])

<div {{ $attributes->class(['grid gap-8 md:gap-12 items-center', 'md:grid-cols-2' => (bool) $service->featuredMedia]) }}>
    @if ($service->featuredMedia)
        <div @class(['md:order-2' => $flip])>
            <img
                src="{{ $service->featuredMedia->url() }}"
                alt="{{ $service->featuredMedia->alt_text ?? $service->name }}"
                loading="lazy"
                width="900"
                height="600"
                class="w-full aspect-[16/11] md:aspect-[4/3] object-cover"
            >
        </div>
    @endif

    <div @class(['md:order-1' => $flip, 'max-w-xl' => ! $service->featuredMedia])>
        <h3 class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950 text-balance">
            {{ $service->name }}
        </h3>

        @if ($service->short_description)
            <p class="mt-3 text-neutral-600 leading-relaxed">{{ $service->short_description }}</p>
        @endif

        <a href="{{ $url }}" class="mt-5 inline-flex items-center gap-2 text-primary-700 font-medium underline-offset-4 hover:underline">
            تفاصيل الخدمة
            <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
        </a>
    </div>
</div>
