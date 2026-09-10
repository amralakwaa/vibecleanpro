<?php

namespace App\Seo\ValueObjects;

/**
 * The one true answer to "should this page be indexable right now, and
 * why". Every other SEO concern (robots meta, sitemap inclusion, the
 * Publishing Gate) reads this instead of re-deriving its own opinion.
 */
final readonly class IndexabilityDecision
{
    /**
     * @param  array<int, string>  $reasons  Machine-readable reason codes (e.g. 'status_draft', 'manual_noindex').
     */
    public function __construct(
        public bool $indexable,
        public bool $follow,
        public array $reasons = [],
    ) {}

    public function reasonSummary(): string
    {
        return implode(', ', $this->reasons);
    }
}
