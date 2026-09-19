<?php

namespace App\Support\Tracking;

use App\Models\Area;
use App\Models\Article;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Seo\UrlResolver;

class PageContextResolver
{
    public function __construct(private readonly UrlResolver $urls) {}

    public static function normalizePath(string $path): string
    {
        $path = '/'.trim((string) parse_url($path, PHP_URL_PATH), '/');

        return mb_substr($path, 0, 255);
    }

    public function resolve(string $path): PageContext
    {
        $path = self::normalizePath($path);

        if (! preg_match('#^/(?:[a-z]+/)?([a-z0-9]+(?:-[a-z0-9]+)*)$#', $path, $matches)) {
            return new PageContext;
        }

        // pages.slug is unique across all types, so the last segment finds
        // at most one page; comparing its canonical path rejects a slug
        // requested under the wrong prefix.
        $page = Page::query()->where('slug', $matches[1])->published()->with('pageable')->first();

        if (! $page || $this->urls->pathForPage($page) !== $path) {
            return new PageContext;
        }

        $entity = $page->pageable;

        return match (true) {
            $entity instanceof Service => new PageContext($page->id, $entity->id),
            $entity instanceof Area => new PageContext($page->id, null, $entity->id),
            $entity instanceof Project => new PageContext($page->id, $entity->services()->orderBy('services.id')->value('services.id'), $entity->area_id),
            $entity instanceof Article, $entity instanceof Offer => new PageContext(
                $page->id,
                $entity->services()->orderBy('services.id')->value('services.id'),
                $entity->areas()->orderBy('areas.id')->value('areas.id'),
            ),
            default => new PageContext($page->id),
        };
    }
}
