<?php

namespace App\Observers;

use App\Enums\AreaTier;
use App\Models\Area;

class AreaObserver
{
    /**
     * A district moved to (or created in) Tier B gets its page set to
     * noindex immediately - the editor never has to remember it, and a
     * later promotion to A is a deliberate step that records its reason.
     */
    public function saved(Area $area): void
    {
        if (! $area->wasRecentlyCreated && $area->wasChanged('tier') && $area->tier === AreaTier::A) {
            $area->promoted_at ??= now();
            $area->saveQuietly();
        }

        if ($area->tier !== AreaTier::B) {
            return;
        }

        $page = $area->page;

        if ($page) {
            $page->seoMetadata()->updateOrCreate([], ['robots_index' => false]);
        }
    }
}
