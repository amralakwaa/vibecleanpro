{{-- Generic surface card. Content-type cards (service/area/project/
     article/testimonial) build on top of this rather than duplicating the
     surface/border/radius/shadow rules. --}}
@props(['padded' => true])

<div {{ $attributes->class([
    'bg-white rounded-2xl border border-neutral-200',
    'transition-shadow duration-150 hover:shadow-md hover:shadow-neutral-900/5',
    $padded ? 'p-6' : '',
]) }}>
    {{ $slot }}
</div>
