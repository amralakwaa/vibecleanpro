<?php

namespace App\Http\Controllers;

use App\Enums\OfferAvailability;
use App\Models\BusinessProfile;
use App\Models\Offer;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Response;

/**
 * /offers only ever lists what is genuinely Active or Scheduled right now
 * (see Offer::availability()) - an expired offer stays reachable at its
 * own URL (old links/SEO signal), it just never appears in this index.
 */
class OffersIndexController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function index(): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        $offers = Offer::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->where('is_active', true)
            ->with('featuredMedia')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Offer $offer) => $offer->availability() !== OfferAvailability::Expired)
            ->sortBy(fn (Offer $offer) => $offer->availability() === OfferAvailability::Active ? 0 : 1)
            ->values();

        $seo = new SeoHeadData(
            title: 'العروض | '.($businessProfile?->name ?? config('app.name')),
            metaDescription: 'العروض المتاحة حاليًا على خدمات التنظيف لدينا.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/offers'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'العروض',
                'description' => 'العروض المتاحة حاليًا على خدمات التنظيف لدينا.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/offers'),
                'type' => 'website',
            ],
            structuredData: [],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('العروض', null),
            ],
        );

        return response()->view('pages.offers-index', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'offers' => $offers,
        ], 200);
    }
}
