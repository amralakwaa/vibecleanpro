<?php

namespace App\Http\Controllers;

use App\Enums\OfferAvailability;
use App\Models\BusinessProfile;
use App\Models\Offer;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use App\Support\Content\IndexCompanion;
use Illuminate\Http\Response;

/**
 * /offers only ever lists what is genuinely Active or Scheduled right now
 * (see Offer::availability()) - an expired offer stays reachable at its
 * own URL (old links/SEO signal), it just never appears in this index.
 */
class OffersIndexController extends Controller
{
    public function __construct(
        private readonly UrlResolver $urlResolver,
        private readonly IndexCompanion $indexCompanion,
    ) {}

    public function index(): Response
    {
        $businessProfile = BusinessProfile::query()->first();
        $companion = $this->indexCompanion->find('offers');

        // 'page' is eager-loaded because the view resolves each offer's
        // URL through UrlResolver::urlForPage() - without it every listed
        // offer costs its own pages query. 'services.featuredMedia' feeds
        // OfferPrice (the honest "before" price) and the covered service's
        // photograph, which an offer without its own picture borrows.
        $offers = Offer::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->where('is_active', true)
            ->with(['featuredMedia', 'page', 'services.featuredMedia'])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Offer $offer) => $offer->availability() !== OfferAvailability::Expired)
            ->sortBy(fn (Offer $offer) => $offer->availability() === OfferAvailability::Active ? 0 : 1)
            ->values();

        $offerList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'عروض التنظيف بالرياض',
            'numberOfItems' => $offers->count(),
            'itemListElement' => $offers->map(fn (Offer $offer, int $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $offer->title,
                'url' => $offer->page ? $this->urlResolver->absoluteUrl('/offers/'.$offer->page->slug) : null,
            ]))->values()->all(),
        ];

        $breadcrumbList = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => $this->urlResolver->absoluteUrl('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'العروض'],
            ],
        ];

        $seo = new SeoHeadData(
            title: $this->indexCompanion->title($companion, 'عروض وباقات التنظيف بالرياض | '.($businessProfile?->displayName() ?: config('app.name'))),
            metaDescription: $this->indexCompanion->description($companion, 'عروض وباقات تنظيف بالرياض بأسعار موسمية — تنظيف فلل وشقق ومكاتب. عروض محدودة المدة تشمل التنظيف الشامل وجلي الرخام وتنظيف المكيفات. اطلع على التفاصيل واحجز الآن.'),
            canonicalUrl: $this->urlResolver->absoluteUrl('/offers'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'العروض',
                'description' => 'عروض وباقات تنظيف بالرياض بأسعار موسمية — تنظيف فلل وشقق ومكاتب. عروض محدودة المدة تشمل التنظيف الشامل وجلي الرخام وتنظيف المكيفات.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/offers'),
                'type' => 'website',
            ],
            structuredData: [$offerList, $breadcrumbList, ...$this->indexCompanion->structuredData($companion)],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('العروض', null),
            ],
        );

        return response()->view('pages.offers-index', [
            'companion' => $companion,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'offers' => $offers,
        ], 200);
    }
}
