<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Page;
use App\Models\Service;

/**
 * Central JSON-LD builder. Every block is built only from real data
 * (BusinessProfile, the Page and its typed entity) - a field with no real
 * value is simply omitted, never invented (see LocalBusiness: no
 * priceRange, no opening hours, no geo unless the data exists; and never
 * AggregateRating/Review - Google's guidelines treat a business rating
 * itself about itself as "self-serving" and ineligible, see the phase
 * report).
 *
 * Two entry points:
 * - sitewide(): Organization/LocalBusiness + WebSite, meant to appear on
 *   every page once.
 * - forPage(Page $page): WebPage + BreadcrumbList + a type-specific block
 *   (Service, Article) where one applies.
 */
class StructuredDataGenerator
{
    public function __construct(
        private readonly UrlResolver $urlResolver,
        private readonly CanonicalResolver $canonical,
        private readonly BreadcrumbResolver $breadcrumbs,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sitewide(): array
    {
        $profile = BusinessProfile::query()->first();

        if (! $profile) {
            return [];
        }

        $blocks = [$this->localBusiness($profile)];

        $blocks[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $profile->name,
            'url' => $this->urlResolver->absoluteUrl('/'),
        ];

        return $blocks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forPage(Page $page): array
    {
        $url = $this->canonical->resolve($page);

        $blocks = [[
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page->seoMetadata?->meta_title ?: $page->title,
            'url' => $url,
            ...($page->seoMetadata?->meta_description ? ['description' => $page->seoMetadata->meta_description] : []),
            ...($page->published_at ? ['datePublished' => $page->published_at->toAtomString()] : []),
            'dateModified' => $page->updated_at->toAtomString(),
        ]];

        $blocks[] = $this->breadcrumbList($page);

        $typed = match ($page->type) {
            PageType::Service => $page->pageable instanceof Service ? $this->service($page, $page->pageable) : null,
            PageType::Article => $page->pageable instanceof Article ? $this->article($page, $page->pageable) : null,
            default => null,
        };

        if ($typed) {
            $blocks[] = $typed;
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>
     */
    private function localBusiness(BusinessProfile $profile): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $this->urlResolver->absoluteUrl('/').'#business',
            'name' => $profile->name,
            'url' => $this->urlResolver->absoluteUrl('/'),
        ];

        if ($profile->phone) {
            $data['telephone'] = $profile->phone;
        }

        if ($profile->email) {
            $data['email'] = $profile->email;
        }

        if ($profile->address || $profile->city) {
            $data['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $profile->address,
                'addressLocality' => $profile->city,
                // The business is explicitly Riyadh, Saudi Arabia by definition
                // of this project - not a guessed/derived value.
                'addressCountry' => 'SA',
            ]);
        }

        if ($profile->latitude !== null && $profile->longitude !== null) {
            $data['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $profile->latitude,
                'longitude' => (float) $profile->longitude,
            ];
        }

        if ($profile->logo) {
            $data['logo'] = $profile->logo->url();
            $data['image'] = $profile->logo->url();
        }

        $socialLinks = array_values(array_filter((array) $profile->social_links));

        if ($socialLinks !== []) {
            $data['sameAs'] = $socialLinks;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function breadcrumbList(Page $page): array
    {
        $items = $this->breadcrumbs->resolve($page);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $index) => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item->label,
                'item' => $item->url ? $this->urlResolver->absoluteUrl($item->url) : null,
            ]))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function service(Page $page, Service $service): array
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->name,
            'description' => $service->short_description,
            'url' => $this->canonical->resolve($page),
            'provider' => ['@id' => $this->urlResolver->absoluteUrl('/').'#business'],
        ]);

        // Only areas that are themselves real, reachable public
        // destinations - never a Place naming a location our own site has
        // no live page for (see the Phase 6 report: found via a test that
        // caught an unpublished Area's name leaking into this JSON-LD).
        $areas = $service->areas()->whereHas('page', fn ($query) => $query->published())->get();

        if ($areas->isNotEmpty()) {
            $data['areaServed'] = $areas->map(fn ($area) => [
                '@type' => 'Place',
                'name' => $area->name,
            ])->all();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function article(Page $page, Article $article): array
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $article->excerpt,
            'url' => $this->canonical->resolve($page),
            'datePublished' => $page->published_at?->toAtomString(),
            'dateModified' => $page->updated_at->toAtomString(),
            'publisher' => ['@id' => $this->urlResolver->absoluteUrl('/').'#business'],
        ]);

        if ($article->featuredMedia) {
            $data['image'] = $article->featuredMedia->url();
        }

        if ($article->author) {
            $data['author'] = [
                '@type' => 'Person',
                'name' => $article->author->name,
            ];
        }

        return $data;
    }
}
