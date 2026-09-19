<?php

namespace App\Enums;

enum LeadLostReason: string
{
    case Price = 'price';
    case Timing = 'timing';
    case Scope = 'scope';
    case NoResponse = 'no_response';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Price => 'السعر',
            self::Timing => 'التوقيت',
            self::Scope => 'خارج نطاق خدماتنا',
            self::NoResponse => 'لم يرد',
            self::Other => 'سبب آخر',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $reason) => [$reason->value => $reason->label()])->all();
    }
}
