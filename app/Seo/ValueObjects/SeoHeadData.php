<?php

namespace App\Seo\ValueObjects;

/**
 * Everything a <head> (or an admin search-preview) needs for one page,
 * assembled once by SeoHeadResolver. The public Blade layout - built in a
 * later phase - will just render this; it should never re-derive any of
 * it.
 */
final readonly class SeoHeadData
{
    /**
     * @param  array{title: string, description: ?string, image: ?string, url: string, type: string}  $openGraph
     * @param  array<int, array<string, mixed>>  $structuredData
     * @param  array<int, BreadcrumbItem>  $breadcrumbs
     */
    public function __construct(
        public string $title,
        public ?string $metaDescription,
        public string $canonicalUrl,
        public string $robotsContent,
        public array $openGraph,
        public array $structuredData,
        public array $breadcrumbs,
    ) {}
}
