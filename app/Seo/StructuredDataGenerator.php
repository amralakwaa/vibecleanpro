<?php

namespace App\Seo;

use App\Enums\AreaTier;
use App\Enums\PageType;
use App\Enums\ServicePricingMode;
use App\Models\Area;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Support\Pricing\PublicPrice;
use Illuminate\Database\Eloquent\Collection;

/**
 * Central JSON-LD builder. Every block is built only from real data
 * (BusinessProfile, the Page and its typed entity) - a field with no real
 * value is simply omitted, never invented (see LocalBusiness: no
 * priceRange, no opening hours, no geo unless the data exists; and never
 * AggregateRating/Review - Google's guidelines treat a business rating
 * itself about itself as "self-serving" and ineligible, see the phase
 * report).
 *
 * Two entry points:
 * - sitewide(): Organization/LocalBusiness + WebSite, meant to appear on
 *   every page once.
 * - forPage(Page $page): WebPage + BreadcrumbList + a type-specific block
 *   (Service, Article) where one applies.
 */
class StructuredDataGenerator
{
    public function __construct(
        private readonly UrlResolver $urlResolver,
        private readonly CanonicalResolver $canonical,
        private readonly BreadcrumbResolver $breadcrumbs,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sitewide(): array
    {
        $profile = BusinessProfile::query()->first();

        if (! $profile) {
            return [];
        }

        $blocks = [$this->localBusiness($profile)];

        $blocks[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $profile->name,
            'url' => $this->urlResolver->absoluteUrl('/'),
        ];

        return $blocks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forPage(Page $page): array
    {
        $url = $this->canonical->resolve($page);

        $blocks = [[
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page->seoMetadata?->meta_title ?: $page->title,
            'url' => $url,
            ...($page->seoMetadata?->meta_description ? ['description' => $page->seoMetadata->meta_description] : []),
            ...($page->published_at ? ['datePublished' => $page->published_at->toAtomString()] : []),
            'dateModified' => $page->updated_at->toAtomString(),
        ]];

        $blocks[] = $this->breadcrumbList($page);

        $typed = match ($page->type) {
            PageType::Service => $page->pageable instanceof Service ? $this->service($page, $page->pageable) : null,
            PageType::Article => $page->pageable instanceof Article ? $this->article($page, $page->pageable) : null,
            PageType::Project => $page->pageable instanceof Project ? $this->caseStudy($page, $page->pageable) : null,
            default => null,
        };

        if ($typed) {
            $blocks[] = $typed;
        }

        $faqs = $page->faqs()->where('is_active', true)->orderBy('sort_order')->get();
        if ($faqs->isNotEmpty() && ! in_array($page->type, [PageType::Service, PageType::Area], true)) {
            $blocks[] = $this->faqPage($faqs);
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>
     */
    private function localBusiness(BusinessProfile $profile): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $this->urlResolver->absoluteUrl('/').'#business',
            'name' => $profile->name,
            'url' => $this->urlResolver->absoluteUrl('/'),
        ];

        if ($profile->phone) {
            $data['telephone'] = $profile->phone;
        }

        if ($profile->email) {
            $data['email'] = $profile->email;
        }

        if ($profile->address || $profile->city) {
            $data['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $profile->address,
                'addressLocality' => $profile->city,
                // The business is explicitly Riyadh, Saudi Arabia by definition
                // of this project - not a guessed/derived value.
                'addressCountry' => 'SA',
            ]);
        }

        if ($profile->latitude !== null && $profile->longitude !== null) {
            $data['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $profile->latitude,
                'longitude' => (float) $profile->longitude,
            ];
        }

        if ($profile->logo) {
            $data['logo'] = $profile->logo->url();
            $data['image'] = $profile->logo->url();
        }

        $socialLinks = array_values(array_filter((array) $profile->social_links));

        if ($socialLinks !== []) {
            $data['sameAs'] = $socialLinks;
        }

        // Only what the About page itself shows: the founder is emitted
        // when the editor entered a name and kept the section visible, and
        // nothing else about the person is asserted (no awards, no dates).
        if ($profile->city) {
            // Lead with the city, then add Tier-A neighbourhoods that have
            // a published page — real destinations, not invented coverage claims.
            $tierAreas = Area::query()
                ->where('tier', AreaTier::A)
                ->whereHas('page', fn ($q) => $q->published())
                ->get(['name']);

            if ($tierAreas->isNotEmpty()) {
                $data['areaServed'] = array_merge(
                    [['@type' => 'City', 'name' => $profile->city]],
                    $tierAreas->map(fn ($a) => ['@type' => 'Place', 'name' => $a->name])->all(),
                );
            } else {
                $data['areaServed'] = ['@type' => 'City', 'name' => $profile->city];
            }
        }

        if ($hours = $this->openingHours($profile)) {
            $data['openingHoursSpecification'] = $hours;
        }

        if ($profile->hasVisibleFounder()) {
            $data['founder'] = array_filter([
                '@type' => 'Person',
                'name' => $profile->founder_name,
                'jobTitle' => $profile->founder_title,
            ]);
        }

        return $data;
    }

    /**
     * Opening hours are emitted only from entries this can read with
     * certainty: a known day label (or an "every day" label) and two
     * unambiguous 24-hour times, opening before closing. Anything freer -
     * "بعد العصر", "حسب الطلب", a 12-hour time - is shown to visitors but
     * never turned into schema, because a wrong opening time in search
     * results is worse than none.
     *
     * @return list<array<string, mixed>>
     */
    private function openingHours(BusinessProfile $profile): array
    {
        $days = [
            'الأحد' => 'Sunday', 'الاثنين' => 'Monday', 'الإثنين' => 'Monday', 'الثلاثاء' => 'Tuesday',
            'الأربعاء' => 'Wednesday', 'الخميس' => 'Thursday', 'الجمعة' => 'Friday', 'السبت' => 'Saturday',
        ];
        $everyDay = ['كل أيام الأسبوع', 'جميع أيام الأسبوع', 'يوميًا', 'يوميا', 'كل الأيام', 'جميع الأيام'];
        $specifications = [];

        foreach ((array) $profile->working_hours as $label => $value) {
            $label = trim((string) $label);
            $dayOfWeek = match (true) {
                in_array($label, $everyDay, true) => array_values(array_unique($days)),
                isset($days[$label]) => [$days[$label]],
                default => null,
            };

            if ($dayOfWeek === null || ! preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b\D+\b([01]?\d|2[0-3]):([0-5]\d)\b/u', (string) $value, $matches)) {
                continue;
            }

            [$opens, $closes] = [sprintf('%02d:%02d', $matches[1], $matches[2]), sprintf('%02d:%02d', $matches[3], $matches[4])];

            if ($opens >= $closes) {
                continue;
            }

            $specifications[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $dayOfWeek,
                'opens' => $opens,
                'closes' => $closes,
            ];
        }

        return $specifications;
    }

    /**
     * @return array<string, mixed>
     */
    private function breadcrumbList(Page $page): array
    {
        $items = $this->breadcrumbs->resolve($page);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $index) => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item->label,
                'item' => $item->url ? $this->urlResolver->absoluteUrl($item->url) : null,
            ]))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function service(Page $page, Service $service): array
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->name,
            'description' => $service->short_description,
            'url' => $this->canonical->resolve($page),
            'provider' => ['@id' => $this->urlResolver->absoluteUrl('/').'#business'],
        ]);

        // Only areas that are themselves real, reachable public
        // destinations - never a Place naming a location our own site has
        // no live page for (see the Phase 6 report: found via a test that
        // caught an unpublished Area's name leaking into this JSON-LD).
        $areas = $service->areas()->whereHas('page', fn ($query) => $query->published())->get();

        if ($areas->isNotEmpty()) {
            $data['areaServed'] = $areas->map(fn ($area) => [
                '@type' => 'Place',
                'name' => $area->name,
            ])->all();
        }

        // The same admin-entered price the page shows, expressed with
        // schema.org's own vocabulary: a single price for fixed, a
        // PriceSpecification with minPrice (and maxPrice for a range) for
        // starting-from / range, a UnitPriceSpecification for per-unit.
        // No rich result is assumed; this only keeps the markup truthful
        // and consistent with the visible page.
        if ($price = $service->publicPrice()) {
            $data['offers'] = $this->serviceOffer($price);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceOffer(PublicPrice $price): array
    {
        $currency = config('pricing.currency');

        // Each mode maps to the schema.org term that means exactly what the
        // visible label says - never a bare Offer.price for anything that
        // is not one fixed number:
        //   fixed         -> Offer.price (one real price)
        //   starting_from -> PriceSpecification.minPrice (a lower bound only)
        //   range         -> PriceSpecification.minPrice + maxPrice
        //   per_unit      -> UnitPriceSpecification.price per one unitText
        //                    (referenceQuantity = 1 of that unit)
        // No Google rich result is assumed or claimed by any of this.
        return match ($price->mode) {
            ServicePricingMode::Fixed => [
                '@type' => 'Offer',
                'price' => $price->min,
                'priceCurrency' => $currency,
            ],
            ServicePricingMode::StartingFrom => [
                '@type' => 'Offer',
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'priceCurrency' => $currency,
                    'minPrice' => $price->min,
                ],
            ],
            ServicePricingMode::Range => [
                '@type' => 'Offer',
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'priceCurrency' => $currency,
                    'minPrice' => $price->min,
                    'maxPrice' => $price->max,
                ],
            ],
            ServicePricingMode::PerUnit => [
                '@type' => 'Offer',
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'priceCurrency' => $currency,
                    'price' => $price->min,
                    'unitText' => $price->unit,
                    'referenceQuantity' => [
                        '@type' => 'QuantitativeValue',
                        'value' => 1,
                        'unitText' => $price->unit,
                    ],
                ],
            ],
            // PublicPrice never carries this mode, but the match must be total.
            ServicePricingMode::QuoteOnly => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * A project page is a case study: a documented piece of work, not an
     * editorial article and not a service listing. CreativeWork says that
     * accurately, and `about` is what ties the proof to the service it
     * supports.
     *
     * It deliberately carries only what the WebPage block does not: the
     * date the work was carried out, the service it evidences, and the
     * photographs that are the evidence. Repeating name, description and
     * publication dates here would just duplicate the block above it.
     *
     * @return array<string, mixed>
     */
    private function caseStudy(Page $page, Project $project): array
    {
        $photos = $project->media
            ->filter(fn ($media) => $media->status?->isPublishable())
            ->take(6)
            ->map(fn ($media) => array_filter([
                '@type' => 'ImageObject',
                'contentUrl' => $media->url(),
                'name' => $media->alt_text,
                'caption' => $media->caption,
            ]))
            ->values()
            ->all();

        $services = $project->services
            ->sortByDesc(fn (Service $service) => (int) ($service->pivot->is_primary ?? 0))
            ->filter(fn (Service $service) => $service->page !== null)
            ->map(fn (Service $service) => [
                '@type' => 'Service',
                'name' => $service->name,
                'url' => $this->canonical->resolve($service->page),
            ])
            ->values()
            ->all();

        // Location powers SEO only when it is verified. An unverified
        // place field is stored and shown in the panel, but never emitted
        // here - a district we cannot stand behind is a fabricated local
        // signal, and hasVerifiedLocation() is the single gate that
        // decides it.
        $location = $project->hasVerifiedLocation()
            ? array_filter([
                '@type' => 'Place',
                'name' => $project->landmark ?: ($project->neighborhood ?: $project->area?->name),
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $project->area?->name ?: $project->neighborhood,
                    'addressRegion' => $project->city ?: 'منطقة الرياض',
                    'addressCountry' => 'SA',
                ]),
            ])
            : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $page->title,
            'url' => $this->canonical->resolve($page),
            'dateCreated' => $project->completed_at?->toDateString(),
            'about' => $services,
            'contentLocation' => $location,
            'image' => $photos,
            'publisher' => ['@id' => $this->urlResolver->absoluteUrl('/').'#business'],
        ]);
    }

    private function article(Page $page, Article $article): array
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $article->excerpt,
            'url' => $this->canonical->resolve($page),
            'datePublished' => $page->published_at?->toAtomString(),
            'dateModified' => $page->updated_at->toAtomString(),
            'publisher' => ['@id' => $this->urlResolver->absoluteUrl('/').'#business'],
        ]);

        if ($article->featuredMedia) {
            $data['image'] = $article->featuredMedia->url();
        }

        if ($article->author) {
            $data['author'] = [
                '@type' => 'Person',
                'name' => $article->author->name,
            ];
        }

        return $data;
    }

    /** @param Collection<int, Faq> $faqs */
    private function faqPage(Collection $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ])->values()->all(),
        ];
    }
}
