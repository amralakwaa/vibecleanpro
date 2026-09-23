{{-- Native <details>/<summary> - free keyboard and screen-reader support,
     no JS needed. Used by the "faq" content block and (later) a FAQ Index
     page built from the same Faq model. --}}
@props(['question', 'answer'])

<details {{ $attributes->class(['group py-4']) }}>
    <summary class="flex items-center justify-between gap-3 cursor-pointer font-medium text-ink-950 list-none">
        {{ $question }}
        <x-public.icon name="chevron-down" class="w-4 h-4 text-neutral-400 transition-transform group-open:rotate-180 shrink-0" />
    </summary>
    <p class="mt-2 text-sm text-neutral-600 leading-relaxed">{{ $answer }}</p>
</details>
