@props(['tone' => 'info']) {{-- info | success | warning | error --}}

@php
    $tones = [
        'info' => ['bg' => 'bg-primary-50 text-primary-800 border-primary-200', 'icon' => 'info'],
        'success' => ['bg' => 'bg-success-50 text-success-600 border-success-500/30', 'icon' => 'check-circle'],
        'warning' => ['bg' => 'bg-warning-50 text-warning-600 border-warning-500/30', 'icon' => 'alert-triangle'],
        'error' => ['bg' => 'bg-error-50 text-error-600 border-error-500/30', 'icon' => 'alert-triangle'],
    ];
    $t = $tones[$tone];
@endphp

<div role="alert" {{ $attributes->class(['flex items-start gap-3 rounded-xl border p-4 text-sm', $t['bg']]) }}>
    <x-public.icon :name="$t['icon']" class="w-5 h-5 mt-0.5" />
    <div>{{ $slot }}</div>
</div>
