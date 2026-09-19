<?php

namespace App\Observers;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Seo\UrlResolver;
use App\Support\Tracking\AttributionCode;
use App\Support\Tracking\PageContextResolver;

class LeadObserver
{
    public function __construct(
        private readonly AttributionCode $codes,
        private readonly PageContextResolver $pages,
        private readonly UrlResolver $urls,
    ) {}

    public function saving(Lead $lead): void
    {
        $this->stampPipelineDates($lead);
        $this->applyAttributionCode($lead);
    }

    /**
     * Each stage keeps the moment it was first reached; skipping ahead
     * (new straight to quoted) still records the earlier stages, so
     * "time to first contact" is never blank for a lead that progressed.
     */
    private function stampPipelineDates(Lead $lead): void
    {
        if (! $lead->isDirty('status') || ! $lead->status instanceof LeadStatus) {
            return;
        }

        $reached = match ($lead->status) {
            LeadStatus::Contacted, LeadStatus::Qualified => ['contacted_at'],
            LeadStatus::Quoted, LeadStatus::Won => ['contacted_at', 'quoted_at'],
            LeadStatus::Completed => ['contacted_at', 'quoted_at', 'completed_at'],
            default => [],
        };

        foreach ($reached as $column) {
            $lead->{$column} ??= now();
        }
    }

    /**
     * A salesperson logging a WhatsApp chat pastes the "V-…" code the
     * message carried; the page it came from then fills the source page,
     * and the service/area only where they are still empty.
     */
    private function applyAttributionCode(Lead $lead): void
    {
        if (! $lead->isDirty('attribution_code') || blank($lead->attribution_code)) {
            return;
        }

        $code = strtoupper(trim($lead->attribution_code));
        $lead->attribution_code = str_starts_with($code, AttributionCode::PREFIX) ? $code : AttributionCode::PREFIX.$code;

        $page = $this->codes->resolve($lead->attribution_code);

        if (! $page) {
            return;
        }

        $context = $this->pages->resolve($this->urls->pathForPage($page));

        $lead->source_page_id = $page->id;
        $lead->service_id ??= $context->serviceId;
        $lead->area_id ??= $context->areaId;
    }
}
