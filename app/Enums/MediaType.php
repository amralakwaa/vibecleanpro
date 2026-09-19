<?php

namespace App\Enums;

enum MediaType: string
{
    case Real = 'real';
    case Stock = 'stock';
    case Placeholder = 'placeholder';

    public function label(): string
    {
        return match ($this) {
            self::Real => 'صورة حقيقية من أعمالنا',
            self::Stock => 'صورة مرخّصة (ليست من أعمالنا)',
            self::Placeholder => 'صورة مؤقتة (Placeholder)',
        };
    }

    /**
     * Only the company's own photos may stand as proof of work: project
     * galleries, before/after pairs, Google Business Profile.
     */
    public function isEvidence(): bool
    {
        return $this === self::Real;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
