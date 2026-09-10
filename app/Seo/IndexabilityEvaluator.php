<?php

namespace App\Seo;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Seo\ValueObjects\IndexabilityDecision;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for "is this Page indexable right now". Nothing
 * else (robots meta, sitemap, Publishing Gate) makes this call itself -
 * they all ask this class.
 *
 * Default-safe rules:
 * - Missing/soft-deleted page: never indexable.
 * - Draft, Review: never indexable (not yet ready for the public web).
 * - Archived: never indexable - an archived page is deliberately retired
 *   content (paired with HTTP 410 at the routing layer), not a page we
 *   want Google to keep serving.
 * - Published but scheduled in the future (published_at > now): not yet
 *   indexable.
 * - Published and due: indexable, unless SEO metadata explicitly sets
 *   robots_index = false (an editor's manual override always wins).
 *
 * "follow" is evaluated independently of "indexable": Google's own
 * guidance is that a noindexed page can still usefully be crawled with
 * follow, so link equity keeps flowing through it, unless an editor
 * explicitly disabled robots_follow too.
 */
class IndexabilityEvaluator
{
    public function evaluate(?Page $page): IndexabilityDecision
    {
        if (! $page || $page->trashed()) {
            return new IndexabilityDecision(indexable: false, follow: false, reasons: ['page_missing']);
        }

        $reasons = [];
        $indexable = true;

        if ($page->status !== PageStatus::Published) {
            $indexable = false;
            $reasons[] = 'status_'.$page->status->value;
        } elseif ($page->published_at instanceof Carbon && $page->published_at->isFuture()) {
            $indexable = false;
            $reasons[] = 'scheduled_in_future';
        }

        $seo = $page->seoMetadata;

        if ($seo && $seo->robots_index === false) {
            $indexable = false;
            $reasons[] = 'manual_noindex';
        }

        $follow = ! $seo || $seo->robots_follow !== false;

        if ($indexable) {
            $reasons[] = 'published_and_eligible';
        }

        return new IndexabilityDecision(indexable: $indexable, follow: $follow, reasons: $reasons);
    }
}
