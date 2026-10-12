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
 *
 * What is cached is the plain COUNT ARRAY, not the SeoDashboardSummary
 * object, and summary() rebuilds the object from it. PHP objects cannot
 * be unserialized from this application's cache at all - config/cache.php
 * sets 'serializable_classes' => false, Laravel's hardened default, so a
 * leaked APP_KEY cannot be turned into a gadget chain. Caching the object
 * itself returned __PHP_Incomplete_Class on every read after the first,
 * which this method's return type then rejected with a TypeError, so the
 * widget answered HTTP 500 for the whole 10-minute window. The same
 * array-then-reconstruct shape is used for the homepage's SeoHeadData in
 * HomeController; this is the application's convention, not a workaround.
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
        return new SeoDashboardSummary(...Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->compute(),
        ));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The summary as named counts, ready to spread into the value object.
     *
     * @return array{indexable: int, noindex: int, draft: int, review: int, pagesWithErrors: int, pagesWithWarnings: int, orphanPages: int, activeRedirects: int, brokenInternalLinks: int, missingMetadata: int, similarLocalPagePairs: int}
     */
    private function compute(): array
    {
        $pages = Page::query()->with(['seoMetadata', 'contentBlocks', 'pageable'])->get();
        $published = $pages->where('status', PageStatus::Published);

        $indexableCount = $published->filter(fn (Page $page) => $this->indexability->evaluate($page)->indexable)->count();

        $gateResults = $published->map(fn (Page $page) => $this->gate->evaluate($page));

        return [
            'indexable' => $indexableCount,
            'noindex' => $published->count() - $indexableCount,
            'draft' => $pages->where('status', PageStatus::Draft)->count(),
            'review' => $pages->where('status', PageStatus::Review)->count(),
            'pagesWithErrors' => $gateResults->filter(fn ($result) => ! $result->canPublish())->count(),
            'pagesWithWarnings' => $gateResults->filter(fn ($result) => $result->warnings()->isNotEmpty())->count(),
            'orphanPages' => $this->orphans->orphans()->count(),
            'activeRedirects' => Redirect::query()->where('is_active', true)->count(),
            'brokenInternalLinks' => $this->brokenLinks->detect()->count(),
            'missingMetadata' => $published->filter(fn (Page $page) => blank($page->seoMetadata?->meta_title) || blank($page->seoMetadata?->meta_description)
            )->count(),
            'similarLocalPagePairs' => $this->similarity->findSimilarAreaPages()->count(),
        ];
    }
}
