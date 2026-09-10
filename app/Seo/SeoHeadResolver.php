<?php

namespace App\Seo;

use App\Models\Page;
use App\Seo\ValueObjects\SeoHeadData;

/**
 * The one place that assembles a page's full <head> data. A Blade view
 * (or an admin search-preview) should call this and render the result -
 * never re-derive title/canonical/robots/OG itself, so there is exactly
 * one implementation of each rule to keep correct.
 */
class SeoHeadResolver
{
    public function __construct(
        private readonly IndexabilityEvaluator $indexability,
        private readonly RobotsMetaRenderer $robots,
        private readonly CanonicalResolver $canonical,
        private readonly OpenGraphResolver $openGraph,
        private readonly StructuredDataGenerator $structuredData,
        private readonly BreadcrumbResolver $breadcrumbs,
    ) {}

    public function forPage(Page $page): SeoHeadData
    {
        $decision = $this->indexability->evaluate($page);
        $seo = $page->seoMetadata;

        return new SeoHeadData(
            title: $seo?->meta_title ?: $page->title,
            metaDescription: $seo?->meta_description,
            canonicalUrl: $this->canonical->resolve($page),
            robotsContent: $this->robots->render($decision),
            openGraph: $this->openGraph->resolve($page),
            structuredData: [...$this->structuredData->sitewide(), ...$this->structuredData->forPage($page)],
            breadcrumbs: $this->breadcrumbs->resolve($page),
        );
    }
}
