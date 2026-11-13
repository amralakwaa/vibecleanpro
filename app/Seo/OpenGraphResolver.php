<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\Page;
use App\Models\Project;

/**
 * Fallback chain, in order: explicit OG field -> SEO metadata (meta title/
 * description) -> page/business defaults (page title, business logo).
 * Image URLs are always absolute, so they stay usable once this runs in
 * production behind a real domain.
 */
class OpenGraphResolver
{
    public function __construct(
        private readonly CanonicalResolver $canonical,
        private readonly DefaultShareImage $defaultShareImage,
    ) {}

    /**
     * @return array{title: string, description: ?string, image: ?string, url: string, type: string}
     */
    public function resolve(Page $page): array
    {
        $seo = $page->seoMetadata;

        $image = $seo?->ogImage?->url() ?: null;

        $image ??= $this->pageImage($page);

        $image ??= $this->defaultShareImage->url();

        if (! $image) {
            $image = BusinessProfile::query()->first()?->logo?->url();
        }

        return [
            'title' => $seo?->og_title ?: ($seo?->meta_title ?: $page->title),
            'description' => $seo?->og_description ?: $seo?->meta_description,
            'image' => $image,
            'url' => $this->canonical->resolve($page),
            'type' => $page->type === PageType::Article ? 'article' : 'website',
        ];
    }

    /**
     * The photograph the page itself is about: a service, article or offer's
     * featured image, or a project's "after" photo (its first photo if it has
     * no after stage).
     */
    private function pageImage(Page $page): ?string
    {
        $pageable = $page->pageable;

        if ($pageable instanceof Project) {
            $media = $pageable->media()->wherePivot('stage', 'after')->first() ?? $pageable->media()->first();

            return $media?->url();
        }

        if ($pageable && method_exists($pageable, 'featuredMedia')) {
            return $pageable->featuredMedia?->url();
        }

        return null;
    }
}
