<?php

namespace App\Seo\ValueObjects;

final readonly class SeoDashboardSummary
{
    public function __construct(
        public int $indexable,
        public int $noindex,
        public int $draft,
        public int $review,
        public int $pagesWithErrors,
        public int $pagesWithWarnings,
        public int $orphanPages,
        public int $activeRedirects,
        public int $brokenInternalLinks,
        public int $missingMetadata,
        public int $similarLocalPagePairs,
    ) {}
}
