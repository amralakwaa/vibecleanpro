<?php

namespace App\Http\Controllers;

use App\Enums\ConversionEventType;
use App\Events\LeadCreated;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Area;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Models\Service;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use App\Support\Tracking\ConversionRecorder;
use App\Support\Tracking\VisitorClassifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

/**
 * /quote - the site's main conversion form. Post/redirect/get: a
 * successful POST redirects back to GET so the success state survives a
 * refresh and the URL never carries a resubmittable form state (see the
 * Phase 6 report's Lead Success UX note).
 */
class QuoteController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function create(): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        $services = Service::query()->whereHas('page', fn ($query) => $query->published())->orderBy('sort_order')->get();
        $areas = Area::query()->whereHas('page', fn ($query) => $query->published())->orderBy('sort_order')->get();

        $seo = new SeoHeadData(
            title: 'اطلب عرض سعر | '.($businessProfile?->name ?? config('app.name')),
            metaDescription: 'اطلب عرض سعر لخدمة التنظيف التي تحتاجها في الرياض.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/quote'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'اطلب عرض سعر',
                'description' => 'اطلب عرض سعر لخدمة التنظيف التي تحتاجها في الرياض.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/quote'),
                'type' => 'website',
            ],
            structuredData: [],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('اطلب عرض سعر', null),
            ],
        );

        return response()->view('pages.quote', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'services' => $services,
            'areas' => $areas,
            'submitted' => session('lead_submitted', false),
        ], 200);
    }

    public function store(StoreLeadRequest $request, ConversionRecorder $conversions, VisitorClassifier $visitors): RedirectResponse
    {
        // Invisible to a real visitor (see resources/views/pages/quote.blade.php) -
        // a filled honeypot pretends success without touching the database.
        if ($request->filled('website_url')) {
            return Redirect::route('public.quote')->with('lead_submitted', true);
        }

        $lead = Lead::query()->create([
            ...$request->validated(),
            'landing_page' => session('lead_attribution.landing_page'),
            'source' => 'quote_form',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'device_type' => $visitors->deviceType($request->userAgent()),
            'utm_source' => session('lead_attribution.utm_source'),
            'utm_medium' => session('lead_attribution.utm_medium'),
            'utm_campaign' => session('lead_attribution.utm_campaign'),
            'utm_term' => session('lead_attribution.utm_term'),
            'utm_content' => session('lead_attribution.utm_content'),
        ]);

        // The lead is stored; telling the team is a separate, queued step
        // that can fail without touching the visitor's success.
        LeadCreated::dispatch($lead);

        $conversions->recordSubmission(ConversionEventType::QuoteFormSubmit, $lead, $request);

        return Redirect::route('public.quote')->with('lead_submitted', true);
    }
}
