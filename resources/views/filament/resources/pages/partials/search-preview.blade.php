{{--
    An approximate mockup only - Google decides its own actual rendering
    (title length, snippet source, sitelinks, etc.) at query time, and this
    never blocks saving on any character count.
--}}
<div class="space-y-1 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3" style="font-family: Arial, sans-serif;">
    <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">معاينة تقريبية (Search Preview) - ليست ضمانًا لشكل النتيجة الفعلي في Google</div>
    <div dir="ltr" class="text-left">
        <div class="text-sm text-[#202124] dark:text-gray-300 truncate">{{ $url }}</div>
        <div class="text-lg text-[#1a0dab] dark:text-blue-400 truncate">{{ $title }}</div>
        @if ($description)
            <div class="text-sm text-[#4d5156] dark:text-gray-400 line-clamp-2">{{ $description }}</div>
        @endif
    </div>
</div>
