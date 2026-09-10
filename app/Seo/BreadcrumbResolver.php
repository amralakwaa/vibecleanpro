<?php

namespace App\Seo;

use App\Models\Page;
use App\Seo\ValueObjects\BreadcrumbItem;

/**
 * Deliberately shallow right now: Home -> current page. Category/listing
 * pages (e.g. a "Services" index) don't exist yet in this phase, and a
 * breadcrumb segment must point at a real, working URL - inventing one
 * would itself be a broken link. Once those listing pages exist, add a
 * segment here for them; the shape (array of BreadcrumbItem) doesn't need
 * to change.
 *
 * @return array<int, BreadcrumbItem>
 */
class BreadcrumbResolver
{
    public function resolve(Page $page): array
    {
        return [
            new BreadcrumbItem('الرئيسية', '/'),
            new BreadcrumbItem($page->title, null),
        ];
    }
}
