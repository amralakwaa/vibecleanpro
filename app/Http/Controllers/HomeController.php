<?php

namespace App\Http\Controllers;

use App\Enums\OfferAvailability;
use App\Models\Area;
use App\Models\BusinessProfile;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Seo\StructuredDataGenerator;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Response;

/**
 * The homepage is not backed by a Page model - see UrlResolver, which has
 * no prefix for it, and the Phase 5 report's Home Page Blueprint. It is
 * handled here directly rather than being forced through the generic Page
 * system, but it still produces the same SeoHeadData shape every other
 * public route does, so the layout never needs a homepage special case.
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly UrlResolver $urlResolver,
        private readonly StructuredDataGenerator $structuredData,
    ) {}

    public function index(): Response
    {
        $profile = BusinessProfile::query()->first();

        $services = Service::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with('featuredMedia')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $areas = Area::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->withCount('services')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        // Only projects that actually have both a "before" and an "after"
        // photo qualify for the homepage showcase - a project with just one
        // stage photographed has nothing to contrast, so it sits this
        // section out rather than rendering a misleading half-pair.
        $beforeAfterProjects = Project::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->whereHas('media', fn ($query) => $query->where('project_media.stage', 'before'))
            ->whereHas('media', fn ($query) => $query->where('project_media.stage', 'after'))
            ->with(['area', 'services', 'media'])
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->limit(3)
            ->get();

        // Hero image priority: the featured project's "after" photo, then
        // the most recently completed published project that has one -
        // never a stock or invented image (see the Phase 3 report).
        $heroProject = Project::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->whereHas('media', fn ($query) => $query->where('project_media.stage', 'after'))
            ->with('media')
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->first();
        $heroImage = $heroProject?->media->firstWhere('pivot.stage', 'after');

        $testimonials = Testimonial::query()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        // Same "currently reachable" rule as /offers (see
        // OffersIndexController): expired offers never appear here.
        $offers = Offer::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->where('is_active', true)
            ->with('featuredMedia')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Offer $offer) => $offer->availability() !== OfferAvailability::Expired)
            ->sortBy(fn (Offer $offer) => $offer->availability() === OfferAvailability::Active ? 0 : 1)
            ->values()
            ->take(3);

        // Sitewide FAQs (page_id = null - the same scope the Filament FAQ
        // resource already manages, see FaqResource::getEloquentQuery()),
        // not any single page's FAQ block.
        $faqs = Faq::query()
            ->whereNull('page_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $title = $profile?->name
            ? "{$profile->name} - خدمات تنظيف احترافية في الرياض"
            : 'خدمات تنظيف احترافية في الرياض';

        $seo = new SeoHeadData(
            title: $title,
            metaDescription: 'خدمات تنظيف منزلي وتجاري احترافية في الرياض: فرق مدربة، مواعيد موثوقة، ونتائج تدوم.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => $title,
                'description' => 'خدمات تنظيف منزلي وتجاري احترافية في الرياض.',
                'image' => $profile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/'),
                'type' => 'website',
            ],
            structuredData: $this->structuredData->sitewide(),
            breadcrumbs: [],
        );

        return response()->view('home', [
            'seo' => $seo,
            'businessProfile' => $profile,
            'services' => $services,
            'areas' => $areas,
            'beforeAfterProjects' => $beforeAfterProjects,
            'heroImage' => $heroImage,
            'testimonials' => $testimonials,
            'offers' => $offers,
            'faqs' => $faqs,
        ], 200);
    }
}
