<?php

namespace App\Seo;

use App\Enums\PageType;
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
    /**
     * The structural (controller-backed, always indexable) routes that no
     * Page record represents, each mapped to the Page type whose entries
     * it lists. Its lastmod is the newest change among those entries (the
     * Page or the entity behind it - a price edit touches the service row,
     * not the page). "/" and "/contact" draw on sources this generator
     * cannot date confidently (profile, testimonials, FAQs), so they carry
     * no lastmod at all rather than a borrowed one.
     *
     * @var array<string, PageType|null>
     */
    private const STRUCTURAL_PATHS = [
        '/' => null,
        '/services' => PageType::Service,
        '/areas' => PageType::Area,
        '/projects' => PageType::Project,
        '/blog' => PageType::Article,
        '/offers' => PageType::Offer,
        '/contact' => null,
    ];

    public function entries(): Collection
    {
        $pages = Page::query()
            ->with(['seoMetadata', 'pageable'])
            ->published()
            ->orderBy('id')
            ->get()
            ->filter(fn (Page $page) => $this->isEligible($page));

        $structural = collect(self::STRUCTURAL_PATHS)->map(fn (?PageType $type, string $path) => [
            'loc' => $this->urlResolver->absoluteUrl($path),
            'lastmod' => $type ? $this->newestChange($pages->where('type', $type)) : null,
        ])->values();

        return $structural
            ->concat($pages->map(fn (Page $page) => [
                'loc' => $this->urlResolver->urlForPage($page),
                'lastmod' => $page->updated_at?->toAtomString(),
            ]))
            ->unique('loc')
            ->values();
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    private function newestChange(Collection $pages): ?string
    {
        $newest = $pages
            ->flatMap(fn (Page $page) => [$page->updated_at, $page->pageable?->updated_at])
            ->filter()
            ->max();

        return $newest?->toAtomString();
    }

    private function isEligible(Page $page): bool
    {
        if (! $this->indexability->evaluate($page)->indexable) {
            return false;
        }

        return $this->canonical->resolve($page) === $this->urlResolver->urlForPage($page);
    }
}
