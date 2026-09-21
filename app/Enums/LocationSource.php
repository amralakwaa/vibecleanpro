<?php

namespace App\Enums;

/**
 * Where a project's location came from.
 *
 * A district on a case study is a local ranking signal, so its
 * provenance matters as much as the value. "Al-Yasmin" typed from memory
 * and "Al-Yasmin" confirmed by the client are the same word and not the
 * same fact - only one of them may power SEO. This records which it is,
 * and the verified flag on the project decides whether it is trusted.
 */
enum LocationSource: string
{
    case ClientProvided = 'client_provided';
    case InternalRecord = 'internal_record';
    case ImageMetadata = 'image_metadata';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ClientProvided => 'أفاد بها العميل',
            self::InternalRecord => 'سجل داخلي',
            self::ImageMetadata => 'بيانات وصفية للصورة',
            self::Other => 'مصدر آخر',
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
