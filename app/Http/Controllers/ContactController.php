<?php

namespace App\Http\Controllers;

use App\Enums\ConversionEventType;
use App\Events\LeadCreated;
use App\Http\Requests\StoreLeadRequest;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use App\Support\Tracking\ConversionRecorder;
use App\Support\Tracking\VisitorClassifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): Response
    {
        // "حلول الشركات" on the homepage links here with ?for=business - a
        // query param rather than a new route, so the B2B framing (heading,
        // intro, message prompt) survives the click without a dedicated
        // page (see the Homepage Conversion Review report).
        $isBusinessContext = $request->query('for') === 'business';

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
            'isBusinessContext' => $isBusinessContext,
        ], 200);
    }

    public function store(StoreLeadRequest $request, ConversionRecorder $conversions, VisitorClassifier $visitors): RedirectResponse
    {
        if ($request->filled('website_url')) {
            return Redirect::route('public.contact')->with('lead_submitted', true);
        }

        $lead = Lead::query()->create([
            ...$request->validated(),
            'landing_page' => session('lead_attribution.landing_page'),
            'source' => $request->input('context') === 'business' ? 'contact_form_business' : 'contact_form',
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

        $conversions->recordSubmission(ConversionEventType::ContactFormSubmit, $lead, $request);

        return Redirect::route('public.contact')->with('lead_submitted', true);
    }
}
