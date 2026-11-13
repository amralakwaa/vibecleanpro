@props(['businessProfile' => null, 'updatedAt' => null, 'showReviewer' => false, 'width' => 'wide'])

@php
    // Who stands behind the page and when it was last changed, in one line.
    // The reviewer is named only where showReviewer is set (service pages),
    // because that is the only place the WebPage reviewedBy/lastReviewed
    // schema claims a review - never assert a review the reviewer did not do.
    $reviewer = $showReviewer && filled($businessProfile?->content_reviewer_name)
        ? trim($businessProfile->content_reviewer_title.' '.$businessProfile->content_reviewer_name)
        : null;
    $brand = $businessProfile?->displayName();
@endphp

@if ($reviewer || filled($brand) || $updatedAt)
    <div {{ $attributes->merge(['class' => 'pb-6']) }}>
    <x-public.container :width="$width">
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-neutral-600">
            <x-public.icon name="badge-check" class="w-4 h-4 text-primary-600 shrink-0" />
            @if ($reviewer)
                <span>مراجعة المحتوى الفني: <strong class="font-medium text-ink-950">{{ $reviewer }}</strong></span>
            @elseif (filled($brand))
                <span>إعداد: <strong class="font-medium text-ink-950">فريق {{ $brand }}</strong></span>
            @endif
            @if ($updatedAt)
                <span aria-hidden="true">·</span>
                <span>آخر تحديث: <time datetime="{{ $updatedAt->toDateString() }}">{{ $updatedAt->translatedFormat('j F Y') }}</time></span>
            @endif
        </p>
    </x-public.container>
    </div>
@endif
