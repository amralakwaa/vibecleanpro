<?php

namespace App\Observers;

use App\Models\Area;
use App\Models\SeoMetadata;

class SeoMetadataObserver
{
    /**
     * SEO metadata validation on save. Tier B districts are no longer
     * forced noindex here — a Tier B "Service Area" page may be indexed
     * when its content passes the PublishingGate quality checks.
     */
    public function saving(SeoMetadata $seo): void
    {
        //
    }
}
