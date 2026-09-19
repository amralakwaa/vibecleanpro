<?php

namespace App\Enums;

enum TestimonialSource: string
{
    case Google = 'google';
    case Whatsapp = 'whatsapp';
    case Survey = 'survey';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'مراجعة على Google',
            self::Whatsapp => 'رسالة واتساب من العميل',
            self::Survey => 'استبيان بعد الخدمة',
            self::Direct => 'إفادة مباشرة من العميل',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $source) => [$source->value => $source->label()])->all();
    }
}
