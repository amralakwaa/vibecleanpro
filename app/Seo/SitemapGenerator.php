<?php

namespace App\Seo;

use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * A single /sitemap.xml for now (see the route) - the project is small.
 * entries() is written so that splitting into a sitemap index later, if the
 * URL count ever grows, only means chunking this same list differently;
 * the eligibility rules below would not need to change.
 *
 * A page is included only if:
 * - IndexabilityEvaluator says it's indexable (draft/review/archived,
 *   scheduled-future, and manually-noindexed pages are excluded there).
 * - It is not soft-deleted (Page::published() / normal queries already
 *   exclude trashed rows).
 * - Its resolved canonical points at itself - a page whose editor set a
 *   custom canonical to a *different* URL is a duplicate/variant of that
 *   other page and should not also claim a sitemap slot.
 */
class SitemapGenerator
{
    public function __construct(
        private readonly IndexabilityEvaluator $indexability,
        private readonly CanonicalResolver $canonical,
        private readonly UrlResolver $urlResolver,
    ) {}

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    public function entries(): Collection
    {
        return Page::query()
            ->with('seoMetadata')
            ->published()
            ->orderBy('id')
            ->get()
            ->filter(fn (Page $page) => $this->isEligible($page))
            ->map(fn (Page $page) => [
                'loc' => $this->urlResolver->urlForPage($page),
                'lastmod' => $page->updated_at?->toAtomString(),
            ])
            ->values();
    }

    private function isEligible(Page $page): bool
    {
        if (! $this->indexability->evaluate($page)->indexable) {
            return false;
        }

        return $this->canonical->resolve($page) === $this->urlResolver->urlForPage($page);
    }
}
