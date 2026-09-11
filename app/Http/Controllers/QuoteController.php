<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Area;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Models\Service;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
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

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        // Invisible to a real visitor (see resources/views/pages/quote.blade.php) -
        // a filled honeypot pretends success without touching the database.
        if ($request->filled('website_url')) {
            return Redirect::route('public.quote')->with('lead_submitted', true);
        }

        Lead::query()->create([
            ...$request->validated(),
            'landing_page' => session('lead_attribution.landing_page'),
            'source' => 'quote_form',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'utm_source' => session('lead_attribution.utm_source'),
            'utm_medium' => session('lead_attribution.utm_medium'),
            'utm_campaign' => session('lead_attribution.utm_campaign'),
            'utm_term' => session('lead_attribution.utm_term'),
            'utm_content' => session('lead_attribution.utm_content'),
        ]);

        return Redirect::route('public.quote')->with('lead_submitted', true);
    }
}
