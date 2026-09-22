<?php

namespace App\Enums;

/**
 * Operational tier of a district page.
 *
 * Tier A ("Evidence-Rich Local Authority") has verified local projects,
 * project evidence, local media and case studies - it is a full local
 * authority page backed by real executed work.
 *
 * Tier B ("Service Area") may still be published and indexable when its
 * content passes the PublishingGate quality checks (unique SEO metadata,
 * genuine useful content, not a doorway page). A verified project is NOT
 * a prerequisite for Tier B indexability.
 *
 * Tier C is a record-only entry with no publishable page.
 *
 * Promotion from B to A is a deliberate human step via areas:promote,
 * recorded with who/when/why. A Tier B page becoming indexable does NOT
 * auto-promote it to A.
 */
enum AreaTier: string
{
    case A = 'a';
    case B = 'b';
    case C = 'c';

    public function label(): string
    {
        return match ($this) {
            self::A => 'A — سلطة محلية بأدلة حقيقية',
            self::B => 'B — صفحة خدمة منطقة',
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
