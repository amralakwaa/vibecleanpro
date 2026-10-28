<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\AreaGroup;
use App\Models\Page;
use App\Seo\ValueObjects\BreadcrumbItem;

/**
 * Home -> listing index -> current page. The middle segment exists only for
 * page types that have a real, published index route; its label must match
 * that index page's own breadcrumb so the trail reads the same on both pages.
 *
 * @return array<int, BreadcrumbItem>
 */
class BreadcrumbResolver
{
    public function resolve(Page $page): array
    {
        return array_values(array_filter([
            new BreadcrumbItem('الرئيسية', '/'),
            $this->parent($page),
            new BreadcrumbItem($page->title, null),
        ]));
    }

    private function parent(Page $page): ?BreadcrumbItem
    {
        if ($page->type === PageType::Landing && $page->pageable_type === (new AreaGroup)->getMorphClass()) {
            return new BreadcrumbItem('مناطق التغطية', '/areas');
        }

        return match ($page->type) {
            PageType::Service => new BreadcrumbItem('خدماتنا', '/services'),
            PageType::Area => new BreadcrumbItem('مناطق التغطية', '/areas'),
            PageType::Project => new BreadcrumbItem('أعمالنا', '/projects'),
            PageType::Article => new BreadcrumbItem('المدونة', '/blog'),
            PageType::Offer => new BreadcrumbItem('العروض', '/offers'),
            default => null,
        };
    }
}
