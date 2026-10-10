<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\AreaGroup;
use App\Models\Page;

/**
 * The project's URL policy, in one place, decided once:
 *
 *   /services/{slug}   /areas/{slug}   /projects/{slug}
 *   /blog/{slug}        /offers/{slug}
 *   /{slug}             (trust, legal, landing - standalone pages)
 *
 * Short, human-readable, never database-ID-based, and stable: this is the
 * single place that would need to change if the policy ever did, which we
 * do not expect to do casually (see the project's SEO architecture notes).
 */
class UrlResolver
{
    /**
     * @var array<string, string>
     */
    private const PREFIXES = [
        'service' => 'services',
        'area' => 'areas',
        'project' => 'projects',
        'article' => 'blog',
        'offer' => 'offers',
    ];

    /**
     * One exception to the table above, and it lives here rather than in a
     * second URL policy elsewhere: a landing page attached to an AreaGroup
     * is a side of Riyadh (شمال/شرق/وسط), so it belongs under /areas with
     * the districts it groups - not at the root where a landing page
     * normally sits. PublicPageController::area serves it and
     * ::standalone refuses it, so it has exactly one URL.
     */
    public function pathForPage(Page $page): string
    {
        if ($page->type === PageType::Landing && $page->pageable_type === AreaGroup::class) {
            return '/areas/'.$page->slug;
        }

        return $this->pathFor($page->type, $page->slug);
    }

    public function pathFor(PageType $type, string $slug): string
    {
        $prefix = self::PREFIXES[$type->value] ?? null;

        return $prefix ? "/{$prefix}/{$slug}" : "/{$slug}";
    }

    public function urlForPage(Page $page): string
    {
        return $this->absoluteUrl($this->pathForPage($page));
    }

    public function absoluteUrl(string $path): string
    {
        return rtrim(config('app.url'), '/').$path;
    }

    /**
     * The route-parameter prefix a given page type is served under, if any
     * (standalone types have none - they are matched by the root catch-all).
     */
    public function prefixFor(PageType $type): ?string
    {
        return self::PREFIXES[$type->value] ?? null;
    }
}
