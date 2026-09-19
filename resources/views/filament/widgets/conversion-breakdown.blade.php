<x-filament-widgets::widget>
    <x-filament::section :heading="'توزيع التحويلات — آخر '.$days.' يومًا'" description="نقرات واتساب والاتصال والنماذج، منسوبة إلى الصفحة التي حدثت فيها. الزيارات والترتيب في Search Console وGA4.">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($lists as $title => $rows)
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
                    @if ($rows->isEmpty())
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">لا توجد بيانات بعد.</p>
                    @else
                        <ul class="mt-2 divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($rows as $row)
                                <li class="flex items-center justify-between py-1.5 text-sm">
                                    <span class="text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                                    <span class="font-semibold tabular-nums text-gray-950 dark:text-white">{{ $row['total'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
