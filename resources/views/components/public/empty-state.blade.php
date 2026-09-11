{{-- Used whenever a section has no real content yet, instead of
     fabricating placeholder data (see Phase 5 report, item 30). --}}
@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class(['text-center py-10 px-6 rounded-2xl border border-dashed border-neutral-300 text-neutral-500']) }}>
    <x-public.icon :name="$icon" class="w-8 h-8 mx-auto text-neutral-400" />
    <p class="mt-3 font-medium text-neutral-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm">{{ $description }}</p>
    @endif
</div>
