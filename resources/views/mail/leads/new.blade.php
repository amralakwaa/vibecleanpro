{{--
    Internal new-lead email (HTML). Plain, right-to-left, inline styles
    only - email clients ignore stylesheets. The rows that matter to the
    person calling back come first; IP and user agent sit in a muted
    technical footer, never in the main body.
--}}
@php
    $rows = collect([
        ['الاسم', $lead->name],
        ['رقم الجوال', $lead->phone],
        ['البريد الإلكتروني', $lead->email],
        ['الخدمة', $lead->service?->name],
        ['المنطقة', $lead->area?->name],
        ['صفحة الدخول', $lead->landing_page],
        ['المصدر', $sourceLabel],
        ['وقت الإرسال', $submittedAt],
    ])->filter(fn (array $row) => filled($row[1]));
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $typeLabel }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:Tahoma,Arial,sans-serif;color:#0b1220;direction:rtl;text-align:right;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
        <tr>
            <td style="padding:28px 28px 8px;">
                <h1 style="margin:0;font-size:20px;line-height:1.4;">{{ $typeLabel }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 28px 0;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                    @foreach ($rows as [$label, $value])
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#6b7280;font-size:13px;width:34%;vertical-align:top;">{{ $label }}</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;font-size:15px;vertical-align:top;" @if (in_array($label, ['رقم الجوال', 'البريد الإلكتروني', 'صفحة الدخول'], true)) dir="ltr" align="right" @endif>{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        @if (filled($lead->message))
            <tr>
                <td style="padding:16px 28px 0;">
                    <p style="margin:0 0 6px;color:#6b7280;font-size:13px;">{{ $lead->source === 'quote_form' ? 'تفاصيل الطلب' : 'الرسالة' }}</p>
                    <p style="margin:0;padding:14px 16px;background:#f9fafb;border-radius:8px;font-size:15px;line-height:1.7;white-space:pre-wrap;">{{ $lead->message }}</p>
                </td>
            </tr>
        @endif
        @if ($utm->isNotEmpty())
            <tr>
                <td style="padding:16px 28px 0;">
                    <p style="margin:0 0 6px;color:#6b7280;font-size:13px;">مصدر الحملة (UTM)</p>
                    <p style="margin:0;font-size:13px;line-height:1.8;" dir="ltr" align="right">
                        @foreach ($utm as $key => $value)
                            {{ $key }}: {{ $value }}@if (! $loop->last)<br>@endif
                        @endforeach
                    </p>
                </td>
            </tr>
        @endif
        @if ($adminUrl)
            <tr>
                <td style="padding:24px 28px 8px;">
                    <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 20px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;">عرض الطلب في لوحة الإدارة</a>
                </td>
            </tr>
        @endif
        @if (filled($lead->ip_address) || filled($lead->user_agent))
            <tr>
                <td style="padding:20px 28px 28px;">
                    <p style="margin:0;padding-top:14px;border-top:1px solid #eef0f3;color:#9ca3af;font-size:11px;line-height:1.7;" dir="ltr" align="right">
                        {{ collect(['IP: '.$lead->ip_address, 'UA: '.$lead->user_agent])->filter(fn ($line) => strlen($line) > 4)->implode(' · ') }}
                    </p>
                </td>
            </tr>
        @else
            <tr><td style="padding:0 0 20px;"></td></tr>
        @endif
    </table>
</body>
</html>
