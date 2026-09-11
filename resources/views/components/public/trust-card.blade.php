{{--
    A single trust or guarantee claim (real warranty terms, real
    methodology, real response time, a real certification badge) - never
    an invented number or promise. Used for both the homepage "trust
    strip" and a Service Guarantee / Quality Policy page's guarantee grid,
    since both are structurally the same icon+title+description unit.
--}}
@props(['icon', 'title', 'description' => null])

<div {{ $attributes->class(['flex items-start gap-3']) }}>
    <span class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
        <x-public.icon :name="$icon" class="w-6 h-6" />
    </span>
    <div>
        <p class="font-medium text-neutral-900">{{ $title }}</p>
        @if ($description)
            <p class="mt-0.5 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
        @endif
    </div>
</div>
