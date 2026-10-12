{{--
    Sidra's "حالة SEO" panel, rebuilt for this application's data. The bar
    classes (vcp-meter*) live in the panel theme, not inline, so the whole
    set can be retinted from one file.

    The percentage is announced to assistive technology through the row's
    aria attributes rather than through the bar's width, which a screen
    reader cannot see; the verdict word carries the same meaning as the
    colour, so nothing here depends on colour alone.
--}}
<x-filament-widgets::widget>
    <x-filament::section
        heading="حالة SEO"
        description="كل سطر نسبة من المنشور فعلاً، لا رقماً مجرداً — ما تحته 80% يستحق نظرة."
        icon="heroicon-o-chart-bar"
    >
        <div class="space-y-5">
            @foreach ($rows as $row)
                <div
                    role="group"
                    aria-label="{{ $row['label'] }}: {{ $row['verdict'] }}، {{ $row['percent'] }} بالمئة"
                >
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <span class="text-sm font-medium text-gray-100">{{ $row['label'] }}</span>

                        <span class="flex items-baseline gap-2 text-sm">
                            <span @class([
                                'font-semibold',
                                'text-green-400' => $row['tone'] === 'good',
                                'text-sky-400' => $row['tone'] === 'brand',
                                'text-amber-400' => $row['tone'] === 'warn',
                                'text-red-400' => $row['tone'] === 'bad',
                            ])>{{ $row['verdict'] }}</span>

                            <span class="text-xs text-gray-400" style="font-variant-numeric: tabular-nums;">
                                {{ $row['done'] }}/{{ $row['total'] }}
                            </span>
                        </span>
                    </div>

                    <div class="vcp-meter mt-2" aria-hidden="true">
                        <div
                            class="vcp-meter-fill vcp-meter-fill-{{ $row['tone'] }}"
                            style="width: {{ $row['percent'] }}%"
                        ></div>
                    </div>

                    <p class="mt-1.5 text-xs text-gray-400">{{ $row['hint'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- The diagnostic strip: counts whose healthy value is zero, so a
             share and a bar would say nothing. A figure that is NOT zero is
             the only one tinted, which makes "everything is fine" a quiet
             row of grey rather than a wall to read. --}}
        @if ($diagnostics !== [])
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-gray-700 pt-5 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($diagnostics as $item)
                    <div>
                        <p
                            @class([
                                'text-xl font-bold leading-none',
                                'text-red-400' => $item['bad'],
                                'text-gray-100' => ! $item['bad'],
                            ])
                            style="font-variant-numeric: tabular-nums;"
                        >{{ $item['value'] }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ $item['label'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
