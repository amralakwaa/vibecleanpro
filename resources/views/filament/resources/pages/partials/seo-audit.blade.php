@php
    use App\Seo\Enums\CheckSeverity;
@endphp
<div class="space-y-3" dir="rtl">
    @if ($result->canPublish())
        <div class="rounded-lg bg-success-50 dark:bg-success-950 px-4 py-2 text-success-700 dark:text-success-300 text-sm font-medium">
            ✓ لا توجد أخطاء تقنية تمنع النشر.
        </div>
    @else
        <div class="rounded-lg bg-danger-50 dark:bg-danger-950 px-4 py-2 text-danger-700 dark:text-danger-300 text-sm font-medium">
            ✕ توجد أخطاء تقنية تمنع نشر هذه الصفحة كمفهرسة. إن كانت الحالة الحالية "منشورة"، فسيُعاد ضبطها تلقائيًا إلى "مسودة" عند الحفظ حتى تُحل هذه الأخطاء.
        </div>
    @endif

    <ul class="space-y-1.5 text-sm">
        @foreach ($result->checks as $check)
            <li class="flex items-start gap-2">
                <span @class([
                    'shrink-0 font-bold',
                    'text-success-600 dark:text-success-400' => $check->severity === CheckSeverity::Pass,
                    'text-warning-600 dark:text-warning-400' => $check->severity === CheckSeverity::Warning,
                    'text-danger-600 dark:text-danger-400' => $check->severity === CheckSeverity::Error,
                ])>
                    {{ match ($check->severity) {
                        CheckSeverity::Pass => '✓',
                        CheckSeverity::Warning => '⚠',
                        CheckSeverity::Error => '✕',
                    } }}
                </span>
                <span class="text-gray-700 dark:text-gray-300">{{ $check->message }}</span>
            </li>
        @endforeach
    </ul>
</div>
