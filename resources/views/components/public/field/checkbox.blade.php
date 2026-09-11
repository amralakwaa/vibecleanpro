@props(['name', 'label', 'error' => null, 'required' => false])

<div>
    <label for="{{ $name }}" class="flex items-start gap-2.5 cursor-pointer">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="checkbox"
            @if ($required) required aria-required="true" @endif
            {{ $attributes->class([
                'mt-0.5 w-4 h-4 rounded border-neutral-300 text-primary-600',
                'focus:outline-none focus:ring-2 focus:ring-primary-500/40',
                $error ? 'border-error-500' : '',
            ]) }}
        >
        <span class="text-sm text-neutral-700">{{ $label }}</span>
    </label>

    @if ($error)
        <p class="mt-1.5 text-sm text-error-600">{{ $error }}</p>
    @endif
</div>
