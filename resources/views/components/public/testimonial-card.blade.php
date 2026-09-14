@props(['testimonial'])

<x-public.card class="h-full flex flex-col">
    <x-public.icon name="quote" class="w-7 h-7 text-primary-200" />

    <p class="mt-3 text-neutral-700 leading-relaxed grow">{{ $testimonial->content }}</p>

    <div class="mt-5 pt-4 border-t border-neutral-100 flex items-center justify-between">
        <span class="font-medium text-neutral-900 text-sm">{{ $testimonial->author_name }}</span>

        @if ($testimonial->rating)
            <span class="flex items-center gap-0.5 text-ink-800" aria-label="{{ $testimonial->rating }} من 5">
                @for ($i = 1; $i <= 5; $i++)
                    <x-public.icon :name="$i <= $testimonial->rating ? 'star' : 'star-outline'" class="w-4 h-4" />
                @endfor
            </span>
        @endif
    </div>
</x-public.card>
