{{-- One numbered step in a process ("steps" content block, the homepage
     "how it works" section, or a future Request Quote flow overview). --}}
@props(['number', 'title', 'description' => null])

<li {{ $attributes->class(['text-center']) }}>
    <span class="mx-auto w-9 h-9 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-sm">
        {{ $number }}
    </span>
    <p class="mt-3 font-semibold text-neutral-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
    @endif
</li>
