<?php

namespace App\Seo;

use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Published, indexable pages with zero inbound signal (see
 * InternalLinkAnalyzer). Admin-facing only - never run from a public
 * request (see security-performance-review guidance: this is an N+1-shaped
 * scan by nature, fine for an on-demand dashboard widget or command, not
 * for every page load).
 */
class OrphanPageAnalyzer
{
    public function __construct(
        private readonly InternalLinkAnalyzer $links,
        private readonly IndexabilityEvaluator $indexability,
    ) {}

    /**
     * @return Collection<int, Page>
     */
    public function orphans(): Collection
    {
        return Page::query()
            ->published()
            ->get()
            ->filter(fn (Page $page) => $this->indexability->evaluate($page)->indexable
                && $this->links->inboundCount($page) === 0)
            ->values();
    }
}
