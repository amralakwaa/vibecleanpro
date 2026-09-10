<?php

namespace App\Seo;

use App\Models\Area;
use App\Models\Article;
use App\Models\InternalLink;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;

/**
 * "Inbound signal" for a page is not just rows in internal_links: the CMS
 * content graph itself (Service<->Area, Project->Area, Article<->Service/
 * Area, Offer<->Service/Area) will become real, crawlable links once the
 * public templates render "related services", "available in these areas",
 * etc. - so a page connected only through those relations is not actually
 * an orphan in practice. We count both, rather than building a crawler to
 * verify the future frontend's exact HTML.
 */
class InternalLinkAnalyzer
{
    public function inboundCount(Page $page): int
    {
        return $this->explicitInboundCount($page) + $this->relationalInboundCount($page);
    }

    public function explicitInboundCount(Page $page): int
    {
        return InternalLink::query()
            ->where('to_page_id', $page->id)
            ->where('is_active', true)
            ->count();
    }

    public function relationalInboundCount(Page $page): int
    {
        $entity = $page->pageable;

        return match (true) {
            $entity instanceof Service => $entity->areas()->count() + $entity->projects()->count()
                + $entity->articles()->count() + $entity->offers()->count(),
            $entity instanceof Area => $entity->services()->count() + $entity->projects()->count()
                + $entity->articles()->count() + $entity->offers()->count(),
            $entity instanceof Project => $entity->services()->count() + ($entity->area_id ? 1 : 0),
            $entity instanceof Article => $entity->services()->count() + $entity->areas()->count(),
            $entity instanceof Offer => $entity->services()->count() + $entity->areas()->count(),
            default => 0,
        };
    }
}
