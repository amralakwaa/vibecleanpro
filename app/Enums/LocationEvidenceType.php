<?php

namespace App\Enums;

/**
 * The kind of proof behind a project's location.
 *
 * Where `location_source` says how the location arrived (the channel),
 * this says what actually backs it (the document). The two answer
 * different questions: a client can "provide" a location by phone
 * (source = client_provided) with no document at all, or hand over a
 * signed contract (source = client_provided, evidence_type = contract).
 * The SEO gate keys on this - a district only ranks when there is a real
 * artefact a stranger could, in principle, check.
 */
enum LocationEvidenceType: string
{
    case Contract = 'contract';
    case Invoice = 'invoice';
    case PhotoMetadata = 'photo_metadata';
    case SiteReport = 'site_report';
    case ClientConfirmation = 'client_confirmation';
    case ManualRecord = 'manual_record';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'عقد',
            self::Invoice => 'فاتورة',
            self::PhotoMetadata => 'بيانات صورة',
            self::SiteReport => 'تقرير موقع',
            self::ClientConfirmation => 'تأكيد عميل',
            self::ManualRecord => 'سجل يدوي',
            self::Other => 'أخرى',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Contract => 'عنوان مثبت في عقد موقَّع.',
            self::Invoice => 'عنوان مثبت في فاتورة أو أمر عمل.',
            self::PhotoMetadata => 'إحداثيات أو بيانات وصفية مضمّنة في الصورة.',
            self::SiteReport => 'تقرير معاينة أو تنفيذ يذكر الموقع.',
            self::ClientConfirmation => 'تأكيد مكتوب من العميل بالموقع.',
            self::ManualRecord => 'سجل داخلي مدوَّن يدويًا (أضعف الأنواع).',
            self::Other => 'مصدر إثبات آخر يُوضَّح في المرجع.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
