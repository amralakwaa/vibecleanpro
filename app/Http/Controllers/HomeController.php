<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\BusinessProfile;
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

        $projects = Project::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['area', 'media'])
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->limit(3)
            ->get();

        $testimonials = Testimonial::query()
            ->orderByDesc('is_featured')
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
            'projects' => $projects,
            'testimonials' => $testimonials,
        ], 200);
    }
}
