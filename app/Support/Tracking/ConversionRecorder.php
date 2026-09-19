<?php

namespace App\Support\Tracking;

use App\Enums\ConversionEventType;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Seo\UrlResolver;
use Illuminate\Http\Request;

class ConversionRecorder
{
    private const DEDUPE_SECONDS = 10;

    public function __construct(
        private readonly PageContextResolver $pages,
        private readonly VisitorClassifier $visitors,
        private readonly UrlResolver $urls,
    ) {}

    /**
     * A click or form-start reported by the visitor's browser. Bots are
     * dropped and a repeat of the same event on the same page within a few
     * seconds (double click, back-and-forth) counts once.
     */
    public function recordFromBrowser(ConversionEventType $type, string $path, Request $request): ?ConversionEvent
    {
        if ($this->visitors->isBot($request->userAgent())) {
            return null;
        }

        $path = PageContextResolver::normalizePath($path);
        $sessionHash = $this->sessionHash($request);

        $duplicate = ConversionEvent::query()
            ->where('session_hash', $sessionHash)
            ->where('event_type', $type)
            ->where('page_path', $path)
            ->where('created_at', '>=', now()->subSeconds(self::DEDUPE_SECONDS))
            ->exists();

        return $duplicate ? null : $this->store($type, $path, $request, $this->pages->resolve($path));
    }

    /**
     * A lead the server has just created - the authoritative conversion.
     * Service and area come from what the visitor actually chose.
     */
    public function recordSubmission(ConversionEventType $type, Lead $lead, Request $request): ConversionEvent
    {
        $sourcePath = $lead->sourcePage
            ? $this->urls->pathForPage($lead->sourcePage)
            : PageContextResolver::normalizePath($request->path());

        return $this->store($type, $sourcePath, $request, new PageContext($lead->source_page_id, $lead->service_id, $lead->area_id), $lead);
    }

    private function store(ConversionEventType $type, string $path, Request $request, PageContext $context, ?Lead $lead = null): ConversionEvent
    {
        $session = $request->session();

        return ConversionEvent::query()->create([
            'event_type' => $type,
            'page_id' => $context->pageId,
            'page_path' => $path,
            'service_id' => $context->serviceId,
            'area_id' => $context->areaId,
            'lead_id' => $lead?->id,
            'source' => $this->visitors->source(
                $session->get('lead_attribution.utm_source'),
                $session->get('lead_attribution.utm_medium'),
                $session->get('lead_attribution.utm_campaign'),
                $session->get('lead_attribution.referrer_host'),
            ),
            'utm_campaign' => $session->get('lead_attribution.utm_campaign'),
            'device_type' => $this->visitors->deviceType($request->userAgent()),
            'session_hash' => $this->sessionHash($request),
        ]);
    }

    /**
     * Links a visitor's events without storing anything that identifies
     * them: the session id is keyed with the app secret and cannot be
     * reversed or matched outside this application.
     */
    private function sessionHash(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }
}
