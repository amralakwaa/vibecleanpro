<?php

namespace App\Enums;

/**
 * Indexing tier of a district page (RIYADH_AREAS_MASTER_MAP §1). Only Tier A
 * - proven search demand plus content written for that district - may be
 * indexed; Tier B is a noindex coverage page, so a long list of similar
 * district pages can never become doorway pages in Google's index.
 */
enum AreaTier: string
{
    case A = 'a';
    case B = 'b';
    case C = 'c';

    public function label(): string
    {
        return match ($this) {
            self::A => 'A — صفحة محلية كاملة (تُفهرس)',
            self::B => 'B — صفحة تغطية (noindex)',
            self::C => 'C — سجل فقط (بلا صفحة منشورة)',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $tier) => [$tier->value => $tier->label()])->all();
    }
}
