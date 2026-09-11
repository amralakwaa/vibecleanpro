@props([
    'name',
    'label' => null,
    'options' => [], // ['value' => 'label']
    'placeholder' => null,
    'error' => null,
    'help' => null,
    'required' => false,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-neutral-800 mb-1.5">
            {{ $label }}
            @if ($required) <span class="text-error-500">*</span> @endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            @if ($required) required aria-required="true" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
            {{ $attributes->class([
                'block w-full appearance-none rounded-xl border bg-white ps-4 pe-10 py-2.5 text-neutral-900',
                'transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/40',
                'disabled:bg-neutral-100 disabled:text-neutral-400 disabled:cursor-not-allowed',
                $error ? 'border-error-500 focus:border-error-500' : 'border-neutral-300 focus:border-primary-500',
            ]) }}
        >
            @if ($placeholder)
                <option value="" selected disabled>{{ $placeholder }}</option>
            @endif

            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
        </select>

        <x-public.icon name="chevron-down" class="w-4 h-4 absolute end-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" />
    </div>

    @if ($error)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-error-600">{{ $error }}</p>
    @elseif ($help)
        <p class="mt-1.5 text-sm text-neutral-500">{{ $help }}</p>
    @endif
</div>
