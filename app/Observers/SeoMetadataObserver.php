<?php

namespace App\Observers;

use App\Enums\AreaTier;
use App\Models\Area;
use App\Models\SeoMetadata;

class SeoMetadataObserver
{
    /**
     * Tier B district pages are noindex no matter which screen saved them:
     * the Area form saves the area first and its page's SEO fields after,
     * so enforcing it here is the only place no save path can bypass.
     */
    public function saving(SeoMetadata $seo): void
    {
        $area = $seo->page?->pageable;

        if ($area instanceof Area && $area->tier === AreaTier::B) {
            $seo->robots_index = false;
        }
    }
}
