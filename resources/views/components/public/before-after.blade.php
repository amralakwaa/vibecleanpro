{{--
    The standard before/after proof pattern for a Project. Presentational
    only - the caller (currently pages/project.blade.php) decides which
    real project photos to pass in; this never invents or reorders which
    image is "before" vs "after".
--}}
@props(['before', 'after'])

<div {{ $attributes->class(['grid sm:grid-cols-2 gap-6']) }}>
    <div>
        <x-public.badge tone="neutral" class="mb-2">قبل</x-public.badge>
        <img src="{{ $before->url() }}" srcset="{{ $before->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" alt="{{ $before->alt_text ?? '' }}" loading="lazy" class="w-full rounded-2xl aspect-[4/3] object-cover">
    </div>
    <div>
        <x-public.badge tone="accent" class="mb-2">بعد</x-public.badge>
        <img src="{{ $after->url() }}" srcset="{{ $after->srcset() }}" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" alt="{{ $after->alt_text ?? '' }}" loading="lazy" class="w-full rounded-2xl aspect-[4/3] object-cover">
    </div>
</div>
