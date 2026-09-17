{{ $typeLabel }}

الاسم: {{ $lead->name }}
رقم الجوال: {{ $lead->phone }}
@if (filled($lead->email))
البريد الإلكتروني: {{ $lead->email }}
@endif
@if ($lead->service)
الخدمة: {{ $lead->service->name }}
@endif
@if ($lead->area)
المنطقة: {{ $lead->area->name }}
@endif
@if (filled($lead->landing_page))
صفحة الدخول: {{ $lead->landing_page }}
@endif
المصدر: {{ $sourceLabel }}
وقت الإرسال: {{ $submittedAt }}
@if (filled($lead->message))

{{ $lead->source === 'quote_form' ? 'تفاصيل الطلب' : 'الرسالة' }}:
{{ $lead->message }}
@endif
@if ($utm->isNotEmpty())

UTM:
@foreach ($utm as $key => $value)
{{ $key }}: {{ $value }}
@endforeach
@endif
@if ($adminUrl)

عرض الطلب في لوحة الإدارة: {{ $adminUrl }}
@endif
@if (filled($lead->ip_address) || filled($lead->user_agent))

--
IP: {{ $lead->ip_address }} · UA: {{ $lead->user_agent }}
@endif
