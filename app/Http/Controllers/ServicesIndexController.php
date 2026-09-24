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
            // The catalogue is 18 services and grows slowly; one page keeps
            // every service one click from the hub. Pagination stays wired
            // up for the day the list outgrows a single screen.
            ->paginate(24)
            ->withQueryString();

        $canonicalPath = '/services'.($services->currentPage() > 1 ? '?page='.$services->currentPage() : '');

        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'خدمات التنظيف في الرياض',
            'numberOfItems' => $services->total(),
            'itemListElement' => $services->values()->map(fn (Service $service, int $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $services->firstItem() + $i,
                'name' => $service->name,
                'url' => $service->page ? $this->urlResolver->absoluteUrl('/services/'.$service->page->slug) : null,
                'description' => $service->short_description ?: null,
            ]))->values()->all(),
        ];

        $breadcrumbList = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => $this->urlResolver->absoluteUrl('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'خدماتنا'],
            ],
        ];

        $seo = new SeoHeadData(
            title: 'خدمات التنظيف في الرياض | '.($businessProfile?->name ?? config('app.name')),
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
            structuredData: [$itemList, $breadcrumbList],
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
