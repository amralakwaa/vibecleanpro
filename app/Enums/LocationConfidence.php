<?php

namespace App\Enums;

/**
 * How strong the evidence behind a location is, on a 0-4 ladder.
 *
 * Status says a human looked at it; confidence says what they had to look
 * at. A district can be "verified" as a decision and still rest on a
 * weak footing - an internal note is not a photograph. The SEO gate
 * demands both: verified status AND confidence of at least MediaEvidence
 * (3), because that is the first rung where the claim is backed by
 * something a stranger could check.
 *
 * Backed by int so `>= 3` is a plain numeric comparison at the gate.
 */
enum LocationConfidence: int
{
    case NoEvidence = 0;
    case InternalRecord = 1;
    case ClientProvided = 2;
    case MediaEvidence = 3;
    case FullyVerified = 4;

    /** The lowest confidence the SEO gate will accept. */
    public const SEO_THRESHOLD = 3;

    public function label(): string
    {
        return match ($this) {
            self::NoEvidence => '0 — لا دليل',
            self::InternalRecord => '1 — سجل داخلي',
            self::ClientProvided => '2 — أفاد بها العميل',
            self::MediaEvidence => '3 — دليل مصوَّر',
            self::FullyVerified => '4 — موثَّق بالكامل',
        };
    }

    public function meetsSeoThreshold(): bool
    {
        return $this->value >= self::SEO_THRESHOLD;
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
