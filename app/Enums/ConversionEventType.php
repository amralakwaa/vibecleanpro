<?php

namespace App\Enums;

enum ConversionEventType: string
{
    case WhatsappClick = 'whatsapp_click';
    case PhoneClick = 'phone_click';
    case QuoteFormStart = 'quote_form_start';
    case QuoteFormSubmit = 'quote_form_submit';
    case ContactFormSubmit = 'contact_form_submit';
    case FileUpload = 'file_upload';

    public function label(): string
    {
        return match ($this) {
            self::WhatsappClick => 'نقرة واتساب',
            self::PhoneClick => 'نقرة اتصال',
            self::QuoteFormStart => 'بدء نموذج عرض السعر',
            self::QuoteFormSubmit => 'إرسال نموذج عرض السعر',
            self::ContactFormSubmit => 'إرسال نموذج التواصل',
            self::FileUpload => 'رفع مرفق',
        };
    }

    /**
     * Only these may be reported by the browser. Submissions and uploads
     * are recorded by the server after they actually succeed, so a forged
     * beacon can never inflate them.
     *
     * @return list<self>
     */
    public static function browserReportable(): array
    {
        return [self::WhatsappClick, self::PhoneClick, self::QuoteFormStart];
    }
}
