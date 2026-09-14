<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Service;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * /services - not backed by a Page model (same reasoning as HomeController):
 * a listing index is a structural site page, not editor content, so it
 * builds its own SeoHeadData rather than going through SeoHeadResolver.
 */
class ServicesIndexController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function index(Request $request): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        // 'page' feeds UrlResolver::urlForPage() for every row and
        // 'category' drives the index's grouping - both eager-loaded so a
        // page of services never costs one query per service.
        $services = Service::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['featuredMedia', 'page', 'category'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        $canonicalPath = '/services'.($services->currentPage() > 1 ? '?page='.$services->currentPage() : '');

        $seo = new SeoHeadData(
            title: 'خدمات التنظيف | '.($businessProfile?->name ?? config('app.name')),
            metaDescription: 'تصفح جميع خدمات التنظيف المتاحة لدينا في الرياض.',
            canonicalUrl: $this->urlResolver->absoluteUrl($canonicalPath),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'خدمات التنظيف',
                'description' => 'تصفح جميع خدمات التنظيف المتاحة لدينا في الرياض.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/services'),
                'type' => 'website',
            ],
            structuredData: [],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('خدماتنا', null),
            ],
        );

        return response()->view('pages.services-index', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'services' => $services,
        ], 200);
    }
}
