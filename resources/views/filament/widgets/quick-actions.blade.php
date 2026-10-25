{{--
    Sidra's quick-actions card. Each row is a real link, not a button that
    scripts a navigation, so it opens in a new tab on a middle click and
    reads as a link to a screen reader.
--}}
<x-filament-widgets::widget>
    <x-filament::section
        heading="إجراءات سريعة"
        description="أكثر ما تنشئه، بنقرة واحدة."
        icon="heroicon-o-bolt"
    >
        <div class="space-y-2">
            @foreach ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="flex items-center gap-3 rounded-lg border border-gray-700 bg-gray-900/40 px-4 py-3 text-sm font-medium text-gray-100 transition-colors hover:border-gray-600 hover:bg-gray-900"
                >
                    <x-filament::icon
                        :icon="$action['icon']"
                        class="h-5 w-5 shrink-0 text-gray-400"
                    />
                    <span>{{ $action['label'] }}</span>
                    <span class="ms-auto text-gray-500" aria-hidden="true">+</span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
