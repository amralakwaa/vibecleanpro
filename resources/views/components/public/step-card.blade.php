{{-- One numbered step in a process ("steps" content block, the homepage
     "how it works" section, or a future Request Quote flow overview). The
     connecting line (lg:before) is decorative only - it is skipped on the
     first step via :first-of-type so it never dangles before step 1. --}}
@props(['number', 'title', 'description' => null])

<li {{ $attributes->class([
    // z-0 gives this <li> its own stacking context so the connecting
    // line's negative-space trick stays scoped here instead of leaking
    // behind the section's own background (it would otherwise paint
    // behind an ancestor with no stacking context of its own, rendering
    // invisible against any opaque page background).
    'relative z-0 text-center',
    'lg:first-of-type:before:content-none lg:before:content-[\'\'] lg:before:absolute lg:before:top-6 lg:before:end-1/2 lg:before:w-full lg:before:h-px lg:before:bg-primary-200 lg:before:-z-10',
]) }}>
    <span class="relative mx-auto w-12 h-12 rounded-full bg-primary-700 text-white flex items-center justify-center font-semibold">
        {{ $number }}
    </span>
    <p class="mt-4 font-semibold text-primary-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
    @endif
</li>
