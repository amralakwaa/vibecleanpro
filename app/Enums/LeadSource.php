<?php

namespace App\Enums;

/**
 * Where a lead came in. leads.source stays a plain string column (older
 * rows and form sub-variants keep working); this enum is the vocabulary
 * the admin offers and labels.
 */
enum LeadSource: string
{
    case QuoteForm = 'quote_form';
    case ContactForm = 'contact_form';
    case ContactFormBusiness = 'contact_form_business';
    case Whatsapp = 'whatsapp';
    case Phone = 'phone';
    case Gbp = 'gbp';
    case Referral = 'referral';
    case Repeat = 'repeat';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::QuoteForm => 'نموذج عرض السعر',
            self::ContactForm => 'نموذج التواصل',
            self::ContactFormBusiness => 'نموذج التواصل (منشآت)',
            self::Whatsapp => 'واتساب',
            self::Phone => 'اتصال',
            self::Gbp => 'Google Business Profile',
            self::Referral => 'توصية',
            self::Repeat => 'عميل سابق',
            self::Other => 'أخرى',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? ($value ?: '—');
    }

    /**
     * Channels a salesperson logs by hand (a chat or a call, not a form).
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect([self::Whatsapp, self::Phone, self::Gbp, self::Referral, self::Repeat, self::Other])
            ->mapWithKeys(fn (self $source) => [$source->value => $source->label()])
            ->all();
    }
}
