<?php

namespace App\Http\Controllers;

use App\Enums\AreaTier;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\BusinessProfile;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Response;

/**
 * /areas - groups published-page Areas by AreaGroup for a scannable
 * "north/east/west..." structure rather than a flat 40-item list (see the
 * Phase 6 report). Grouping here is presentation only: an AreaGroup never
 * gets its own indexable URL just because this index exists.
 */
class AreasIndexController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function index(): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        // The directory is the coverage list: every district the company
        // serves (Tier A and B). Only an area whose own page is published
        // is linked; the rest are plain text, so no thin page is created
        // or linked just to name a district. Tier C is outside coverage.
        $areas = Area::query()
            ->whereIn('tier', [AreaTier::A, AreaTier::B])
            ->with(['page' => fn ($query) => $query->published()])
            ->withCount('services')
            ->orderBy('sort_order')
            ->get();

        $grouped = $areas->groupBy('area_group_id');

        $groups = AreaGroup::query()
            ->whereIn('id', $grouped->keys()->filter())
            ->orderBy('sort_order')
            ->get()
            ->map(fn (AreaGroup $group) => [
                'group' => $group,
                'areas' => $grouped->get($group->id, collect()),
            ]);

        // Areas with no group at all still need to be reachable, just
        // under a neutral bucket instead of being silently dropped.
        $ungrouped = $grouped->get(null, collect());

        $businessProfileName = $businessProfile?->name ?? config('app.name');

        $publishedAreas = $areas->filter(fn (Area $area) => $area->page !== null)->values();

        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'مناطق تغطية خدمات التنظيف في الرياض',
            'numberOfItems' => $publishedAreas->count(),
            'itemListElement' => $publishedAreas->values()->map(fn (Area $area, int $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $area->name,
                'url' => $this->urlResolver->absoluteUrl('/areas/'.$area->page->slug),
            ]))->values()->all(),
        ];

        $breadcrumbList = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => $this->urlResolver->absoluteUrl('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'مناطق التغطية'],
            ],
        ];

        $seo = new SeoHeadData(
            title: 'مناطق التغطية | '.$businessProfileName,
            metaDescription: 'تعرف على المناطق التي نقدم فيها خدمات التنظيف في الرياض.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/areas'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'مناطق التغطية',
                'description' => 'تعرف على المناطق التي نقدم فيها خدمات التنظيف في الرياض.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/areas'),
                'type' => 'website',
            ],
            structuredData: [$itemList, $breadcrumbList],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('مناطق التغطية', null),
            ],
        );

        return response()->view('pages.areas-index', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'groups' => $groups,
            'ungrouped' => $ungrouped,
            'totalAreas' => $areas->count(),
        ], 200);
    }
}
