{{--
    Only render this when a real certification/license/accreditation
    exists (Civil Defense approval, a SASO product certificate, Chamber of
    Commerce membership, a real ISO certificate, etc.) - never a
    placeholder or an invented credential. `verifyUrl`, when the issuing
    body has a public lookup, is more trustworthy than the number alone.
--}}
@props(['name', 'issuer', 'description' => null, 'certificateNumber' => null, 'verifyUrl' => null, 'logo' => null])

<x-public.card class="flex items-start gap-4">
    @if ($logo)
        <img src="{{ is_string($logo) ? $logo : $logo->url() }}" alt="{{ $issuer }}" class="w-12 h-12 rounded-lg object-contain shrink-0">
    @else
        <span class="w-12 h-12 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
            <x-public.icon name="shield-check" class="w-6 h-6" />
        </span>
    @endif

    <div class="min-w-0">
        <p class="font-semibold text-neutral-900">{{ $name }}</p>
        <p class="text-sm text-neutral-500">{{ $issuer }}</p>

        @if ($description)
            <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">{{ $description }}</p>
        @endif

        @if ($certificateNumber)
            <p class="mt-2 text-xs text-neutral-400">رقم الشهادة: {{ $certificateNumber }}</p>
        @endif

        @if ($verifyUrl)
            <a href="{{ $verifyUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:text-primary-800">
                التحقق من الشهادة
                <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
            </a>
        @endif
    </div>
</x-public.card>
