<?php

namespace App\Seo;

use App\Models\InternalLink;
use App\Seo\ValueObjects\BrokenLink;
use Illuminate\Support\Collection;

/**
 * internal_links.to_page_id is a real foreign key with cascadeOnDelete, so
 * a force-deleted target can never leave a dangling row - there is nothing
 * to detect there. What *can* happen, and is worth surfacing:
 *
 * - The target page is soft-deleted (trashed but still in the database).
 * - The target page is not currently indexable (draft/review/archived/
 *   manually noindexed) - a live, crawlable link pointing at content that
 *   won't actually be reachable/valuable to a visitor or Google.
 *
 * Note this project never needs to "update a link after a slug change":
 * internal_links point at a stable page_id, not a URL string, so they
 * resolve to the current canonical URL automatically (see UrlResolver).
 */
class BrokenLinkDetector
{
    public function __construct(private readonly IndexabilityEvaluator $indexability) {}

    /**
     * @return Collection<int, BrokenLink>
     */
    public function detect(): Collection
    {
        return InternalLink::query()
            ->where('is_active', true)
            ->with(['toPage' => fn ($query) => $query->withTrashed()->with('seoMetadata')])
            ->get()
            ->map(function (InternalLink $link) {
                if (! $link->toPage) {
                    return new BrokenLink($link, 'target_missing');
                }

                if ($link->toPage->trashed()) {
                    return new BrokenLink($link, 'target_deleted');
                }

                if (! $this->indexability->evaluate($link->toPage)->indexable) {
                    return new BrokenLink($link, 'target_not_indexable');
                }

                return null;
            })
            ->filter()
            ->values();
    }
}
