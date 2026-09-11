<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

/**
 * /contact - real BusinessProfile info + a simple message form. Distinct
 * from /quote: this is "get in touch", not a structured service/area
 * request (see the Phase 6 report).
 */
class ContactController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function index(): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        $seo = new SeoHeadData(
            title: 'تواصل معنا | '.($businessProfile?->name ?? config('app.name')),
            metaDescription: 'تواصل مع فريقنا عبر الهاتف أو واتساب أو نموذج التواصل.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/contact'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'تواصل معنا',
                'description' => 'تواصل مع فريقنا عبر الهاتف أو واتساب أو نموذج التواصل.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/contact'),
                'type' => 'website',
            ],
            structuredData: [],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('تواصل معنا', null),
            ],
        );

        return response()->view('pages.contact', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'submitted' => session('lead_submitted', false),
        ], 200);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        if ($request->filled('website_url')) {
            return Redirect::route('public.contact')->with('lead_submitted', true);
        }

        Lead::query()->create([
            ...$request->validated(),
            'landing_page' => session('lead_attribution.landing_page'),
            'source' => 'contact_form',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'utm_source' => session('lead_attribution.utm_source'),
            'utm_medium' => session('lead_attribution.utm_medium'),
            'utm_campaign' => session('lead_attribution.utm_campaign'),
            'utm_term' => session('lead_attribution.utm_term'),
            'utm_content' => session('lead_attribution.utm_content'),
        ]);

        return Redirect::route('public.contact')->with('lead_submitted', true);
    }
}
