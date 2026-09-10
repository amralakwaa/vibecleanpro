<?php

namespace App\Seo;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Redirect;
use App\Seo\ValueObjects\SeoDashboardSummary;
use Illuminate\Support\Facades\Cache;

/**
 * Backs the admin SEO Dashboard widget. Evaluating every published page's
 * Publishing Gate (and the Area-similarity scan inside it) is real work -
 * never something to do per public request - so the result is cached
 * briefly. 10 minutes is short enough that an editor who just fixed
 * something won't be confused for long, long enough that opening the
 * dashboard repeatedly doesn't reprocess every page each time.
 */
class SeoDashboardService
{
    private const CACHE_KEY = 'seo:dashboard-summary';

    private const CACHE_TTL_MINUTES = 10;

    public function __construct(
        private readonly IndexabilityEvaluator $indexability,
        private readonly PublishingGate $gate,
        private readonly OrphanPageAnalyzer $orphans,
        private readonly BrokenLinkDetector $brokenLinks,
        private readonly DuplicateSimilarityAnalyzer $similarity,
    ) {}

    public function summary(): SeoDashboardSummary
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), fn () => $this->compute());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function compute(): SeoDashboardSummary
    {
        $pages = Page::query()->with(['seoMetadata', 'contentBlocks', 'pageable'])->get();
        $published = $pages->where('status', PageStatus::Published);

        $indexableCount = $published->filter(fn (Page $page) => $this->indexability->evaluate($page)->indexable)->count();

        $gateResults = $published->map(fn (Page $page) => $this->gate->evaluate($page));

        return new SeoDashboardSummary(
            indexable: $indexableCount,
            noindex: $published->count() - $indexableCount,
            draft: $pages->where('status', PageStatus::Draft)->count(),
            review: $pages->where('status', PageStatus::Review)->count(),
            pagesWithErrors: $gateResults->filter(fn ($result) => ! $result->canPublish())->count(),
            pagesWithWarnings: $gateResults->filter(fn ($result) => $result->warnings()->isNotEmpty())->count(),
            orphanPages: $this->orphans->orphans()->count(),
            activeRedirects: Redirect::query()->where('is_active', true)->count(),
            brokenInternalLinks: $this->brokenLinks->detect()->count(),
            missingMetadata: $published->filter(fn (Page $page) => blank($page->seoMetadata?->meta_title) || blank($page->seoMetadata?->meta_description)
            )->count(),
            similarLocalPagePairs: $this->similarity->findSimilarAreaPages()->count(),
        );
    }
}
