<?php

namespace App\Seo\ValueObjects;

/**
 * An internal-only editorial signal, deliberately never called "Google
 * Score" or "Google Ranking Score" anywhere it is surfaced - it reflects
 * nothing Google actually computes, only how completely this page's own
 * Publishing Gate checks pass. A dimension is null when no check in that
 * category applies to this page's type (e.g. "local" for a Service page).
 */
final readonly class PageQualityBreakdown
{
    public function __construct(
        public int $overall,
        public ?int $technical,
        public ?int $content,
        public ?int $local,
        public ?int $trust,
        public ?int $internalLinking,
    ) {}
}
