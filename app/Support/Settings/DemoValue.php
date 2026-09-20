<?php

namespace App\Support\Settings;

/**
 * Recognises the placeholder values seeded for local development, so the
 * public site can refuse to present them as facts.
 *
 * Demo values deliberately use reserved, unroutable names (.invalid,
 * .test, .example) and obvious placeholder shapes, so this check never
 * misfires on a real value the owner enters.
 */
class DemoValue
{
    private const NEEDLES = ['example.invalid', '.invalid', '.test', 'example.com', 'example.org', 'demo-', 'DEMO', 'NOT-A-REAL'];

    public static function isDemo(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        $value = trim($value);

        foreach (self::NEEDLES as $needle) {
            if (str_contains(mb_strtolower($value), mb_strtolower($needle))) {
                return true;
            }
        }

        // A registration number of all zeros is a placeholder, never a real
        // commercial registration.
        return (bool) preg_match('/^0+$/', $value);
    }

    /**
     * The value when it is real, null when it is a placeholder.
     */
    public static function realOrNull(?string $value): ?string
    {
        return blank($value) || self::isDemo($value) ? null : trim($value);
    }
}
