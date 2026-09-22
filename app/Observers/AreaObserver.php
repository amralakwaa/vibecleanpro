<?php

namespace App\Observers;

use App\Enums\AreaTier;
use App\Models\Area;

class AreaObserver
{
    /**
     * Tier transitions: promoting to A records promoted_at. Tier B pages
     * are no longer forced noindex — a Service Area page may be indexed
     * when its content passes the quality gate.
     */
    public function saved(Area $area): void
    {
        if (! $area->wasRecentlyCreated && $area->wasChanged('tier') && $area->tier === AreaTier::A) {
            $area->promoted_at ??= now();
            $area->saveQuietly();
        }
    }
}
