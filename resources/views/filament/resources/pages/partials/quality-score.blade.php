@php
    $rows = [
        'التقنية' => $breakdown->technical,
        'المحتوى' => $breakdown->content,
        'المحلي' => $breakdown->local,
        'الثقة' => $breakdown->trust,
        'الروابط الداخلية' => $breakdown->internalLinking,
    ];

    $colorFor = fn (int $score) => match (true) {
        $score >= 80 => 'success',
        $score >= 50 => 'warning',
        default => 'danger',
    };
@endphp
<div class="space-y-3" dir="rtl">
    <div class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-800 px-4 py-3">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">نقاط جودة الصفحة (داخلي)</span>
        <span @class([
            'text-lg font-bold',
            'text-success-600 dark:text-success-400' => $colorFor($breakdown->overall) === 'success',
            'text-warning-600 dark:text-warning-400' => $colorFor($breakdown->overall) === 'warning',
            'text-danger-600 dark:text-danger-400' => $colorFor($breakdown->overall) === 'danger',
        ])>{{ $breakdown->overall }}%</span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
        @foreach ($rows as $label => $score)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-2 text-center">
                <div class="text-gray-500 dark:text-gray-400">{{ $label }}</div>
                @if ($score === null)
                    <div class="text-gray-400 dark:text-gray-500 mt-1">لا ينطبق</div>
                @else
                    <div @class([
                        'font-semibold mt-1',
                        'text-success-600 dark:text-success-400' => $colorFor($score) === 'success',
                        'text-warning-600 dark:text-warning-400' => $colorFor($score) === 'warning',
                        'text-danger-600 dark:text-danger-400' => $colorFor($score) === 'danger',
                    ])>{{ $score }}%</div>
                @endif
            </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 dark:text-gray-500">
        هذا مؤشر داخلي لاكتمال بيانات الصفحة فقط، وليس درجة أو تصنيفًا من Google. اكتمال البيانات لا يعني بالضرورة ترتيبًا أفضل في نتائج البحث.
    </p>
</div>
