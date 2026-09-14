<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\BusinessProfile;
use App\Models\Project;
use App\Models\Service;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * /projects - optional ?service= / ?area= filters narrow the *visible*
 * list only. The canonical always points at the bare /projects URL (see
 * the Phase 6 report's note on Phase 4's canonical policy): a filtered
 * view is a display convenience, not a distinct indexable page, so it
 * must never fragment ranking signal across dozens of parameter URLs.
 */
class ProjectsIndexController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function index(Request $request): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        $serviceFilter = Service::query()->whereKey($request->query('service'))->first();
        $areaFilter = Area::query()->whereKey($request->query('area'))->first();

        // 'page' resolves each entry's URL, 'services' is the real
        // metadata shown beside the area and completion date.
        $projects = Project::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['area', 'media', 'page', 'services'])
            ->when($serviceFilter, fn ($query) => $query->whereHas('services', fn ($q) => $q->whereKey($serviceFilter->id)))
            ->when($areaFilter, fn ($query) => $query->where('area_id', $areaFilter->id))
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->paginate(12)
            ->withQueryString();

        $filterServices = Service::query()->whereHas('page', fn ($query) => $query->published())->orderBy('sort_order')->get();
        $filterAreas = Area::query()->whereHas('page', fn ($query) => $query->published())->orderBy('sort_order')->get();

        $seo = new SeoHeadData(
            title: 'أعمالنا ومشاريعنا | '.($businessProfile?->name ?? config('app.name')),
            metaDescription: 'تصفح نماذج من مشاريعنا المنفذة في الرياض.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/projects'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'أعمالنا ومشاريعنا',
                'description' => 'تصفح نماذج من مشاريعنا المنفذة في الرياض.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/projects'),
                'type' => 'website',
            ],
            structuredData: [],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('أعمالنا', null),
            ],
        );

        return response()->view('pages.projects-index', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'projects' => $projects,
            'filterServices' => $filterServices,
            'filterAreas' => $filterAreas,
            'activeService' => $serviceFilter,
            'activeArea' => $areaFilter,
        ], 200);
    }
}
