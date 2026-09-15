<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Every price the public sees is an admin-entered number rendered by one
 * formatter; nothing is guessed, no placeholder is printed when there
 * is nothing to say, and an offer's "instead of" price exists only when
 * it is literally the covered service's own public price.
 */
class ServicePricingTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_quote_only_service_shows_no_price_and_no_placeholder(): void
    {
        $service = $this->service('quote-only-svc', ['pricing_mode' => 'quote_only']);

        $html = $this->get('/services/quote-only-svc')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-price', $html);
        $this->assertDoesNotMatchRegularExpression('/ر\.س|السعر حسب الطلب|يبدأ من/u', $this->mainOf($html));
        $this->assertStringNotContainsString('"offers"', $html);
    }

    public function test_each_pricing_mode_renders_its_own_honest_label_on_the_detail_page(): void
    {
        $cases = [
            ['starting-svc', ['pricing_mode' => 'starting_from', 'price_min' => 299], 'يبدأ من 299 ر.س'],
            ['fixed-svc', ['pricing_mode' => 'fixed', 'price_min' => 399], '399 ر.س'],
            ['range-svc', ['pricing_mode' => 'range', 'price_min' => 300, 'price_max' => 500], 'من 300 إلى 500 ر.س'],
            ['unit-svc', ['pricing_mode' => 'per_unit', 'price_min' => 20, 'price_unit' => 'متر'], '20 ر.س / متر'],
            ['decimal-svc', ['pricing_mode' => 'fixed', 'price_min' => 1499.5], '1,499.50 ر.س'],
        ];

        foreach ($cases as [$slug, $attributes, $expected]) {
            $this->service($slug, $attributes);
            $html = $this->get('/services/'.$slug)->assertOk()->getContent();
            $this->assertStringContainsString($expected, $html, $slug);
            $this->assertSame(1, substr_count($this->mainOf($html), $expected), $slug.' price stated once');
        }
    }

    public function test_the_price_note_travels_with_the_price_and_never_alone(): void
    {
        $this->service('noted-svc', ['pricing_mode' => 'starting_from', 'price_min' => 299, 'price_note' => 'ملاحظة-السعر-من-الإدارة']);
        $this->assertStringContainsString('ملاحظة-السعر-من-الإدارة', $this->get('/services/noted-svc')->getContent());

        $this->service('noted-quote-svc', ['pricing_mode' => 'quote_only', 'price_note' => 'ملاحظة-بلا-سعر']);
        $this->assertStringNotContainsString('ملاحظة-بلا-سعر', $this->get('/services/noted-quote-svc')->getContent());
    }

    public function test_a_hidden_or_incomplete_price_renders_nothing(): void
    {
        $this->service('hidden-svc', ['pricing_mode' => 'fixed', 'price_min' => 399, 'show_price' => false]);
        $this->service('half-range-svc', ['pricing_mode' => 'range', 'price_min' => 300, 'price_max' => null]);
        $this->service('unitless-svc', ['pricing_mode' => 'per_unit', 'price_min' => 20, 'price_unit' => null]);

        foreach (['hidden-svc', 'half-range-svc', 'unitless-svc'] as $slug) {
            $html = $this->get('/services/'.$slug)->assertOk()->getContent();
            $this->assertStringNotContainsString('data-price', $html, $slug);
            $this->assertDoesNotMatchRegularExpression('/\d+ ر\.س/u', $this->mainOf($html), $slug);
        }
    }

    public function test_changing_the_price_in_the_admin_record_changes_every_public_surface(): void
    {
        $service = $this->service('surfaced-svc', ['pricing_mode' => 'starting_from', 'price_min' => 299, 'is_featured' => true]);

        $this->assertStringContainsString('يبدأ من 299 ر.س', $this->get('/services')->getContent());
        $this->assertStringContainsString('يبدأ من 299 ر.س', $this->get('/')->getContent());

        $service->update(['price_min' => 349]);

        $this->assertStringContainsString('يبدأ من 349 ر.س', $this->get('/services/surfaced-svc')->getContent());
        $this->assertStringContainsString('يبدأ من 349 ر.س', $this->get('/services')->getContent());
        $this->assertStringNotContainsString('299 ر.س', $this->get('/')->getContent());

        $service->update(['show_price' => false]);
        $this->assertStringNotContainsString('ر.س', $this->mainOf($this->get('/services')->getContent()));
    }

    /**
     * The JSON-LD must mean exactly what the visible label means, mode by
     * mode: one fixed price is Offer.price; "starting from" is only a
     * lower bound; a range is min+max; per-unit is a unit price per one
     * named unit - and nothing at all for quote-only or hidden prices.
     * No Product type, no Google rich-result assumption.
     */
    public function test_the_service_schema_says_no_more_than_the_visible_price_for_every_mode(): void
    {
        $this->service('schema-quote', ['pricing_mode' => 'quote_only']);
        $this->service('schema-hidden', ['pricing_mode' => 'fixed', 'price_min' => 399, 'show_price' => false]);
        $this->service('schema-fixed', ['pricing_mode' => 'fixed', 'price_min' => 399]);
        $this->service('schema-starting', ['pricing_mode' => 'starting_from', 'price_min' => 299]);
        $this->service('schema-range', ['pricing_mode' => 'range', 'price_min' => 300, 'price_max' => 500]);
        $this->service('schema-unit', ['pricing_mode' => 'per_unit', 'price_min' => 20, 'price_unit' => 'متر']);

        foreach (['schema-quote', 'schema-hidden'] as $slug) {
            $service = $this->serviceBlock($slug);
            $this->assertArrayNotHasKey('offers', $service, $slug);
        }

        $this->assertEquals(
            ['@type' => 'Offer', 'price' => 399, 'priceCurrency' => 'SAR'],
            $this->serviceBlock('schema-fixed')['offers'],
        );

        $starting = $this->serviceBlock('schema-starting')['offers'];
        $this->assertEquals(['@type' => 'Offer', 'priceSpecification' => ['@type' => 'PriceSpecification', 'priceCurrency' => 'SAR', 'minPrice' => 299]], $starting);
        $this->assertArrayNotHasKey('price', $starting, 'a lower bound must never read as the final price');

        $range = $this->serviceBlock('schema-range')['offers'];
        $this->assertEquals(['@type' => 'Offer', 'priceSpecification' => ['@type' => 'PriceSpecification', 'priceCurrency' => 'SAR', 'minPrice' => 300, 'maxPrice' => 500]], $range);
        $this->assertArrayNotHasKey('price', $range);

        $unit = $this->serviceBlock('schema-unit')['offers'];
        $this->assertEquals([
            '@type' => 'Offer',
            'priceSpecification' => [
                '@type' => 'UnitPriceSpecification',
                'priceCurrency' => 'SAR',
                'price' => 20,
                'unitText' => 'متر',
                'referenceQuantity' => ['@type' => 'QuantitativeValue', 'value' => 1, 'unitText' => 'متر'],
            ],
        ], $unit);
        $this->assertArrayNotHasKey('price', $unit, 'a unit price is never the whole-service price');

        // The typed block stays a Service - never a Product.
        $this->assertStringNotContainsString('"@type":"Product"', $this->get('/services/schema-fixed')->getContent());
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceBlock(string $slug): array
    {
        $html = $this->get('/services/'.$slug)->assertOk()->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        foreach ($m[1] as $json) {
            $block = json_decode($json, true);
            if (($block['@type'] ?? null) === 'Service') {
                return $block;
            }
        }

        $this->fail('No Service JSON-LD block on /services/'.$slug);
    }

    public function test_an_offer_price_is_a_typed_number_and_the_before_price_is_never_derived(): void
    {
        $service = $this->service('offer-base-svc', ['pricing_mode' => 'fixed', 'price_min' => 399]);

        // Real before price: one covered service, fixed public price above the offer.
        $honest = $this->offer('honest-offer', 299, [$service]);
        $html = $this->get('/offers/honest-offer')->assertOk()->getContent();
        $this->assertStringContainsString('data-offer-price', $html);
        $this->assertStringContainsString('299 ر.س', $html);
        $this->assertMatchesRegularExpression('/بدلًا من <s[^>]*>399 ر\.س<\/s>/u', $html);

        // Label only, no number: nothing numeric is invented from "20%".
        $textOnly = $this->offer('label-only-offer', null, [$service], 'خصم 20%');
        $page = $this->get('/offers/label-only-offer')->getContent();
        $this->assertStringNotContainsString('data-offer-price', $page);
        $this->assertStringNotContainsString('بدلًا من', $page);

        // Two covered services: no single base price, so no "instead of".
        $other = $this->service('offer-other-svc', ['pricing_mode' => 'fixed', 'price_min' => 899]);
        $this->offer('two-services-offer', 299, [$service, $other]);
        $two = $this->get('/offers/two-services-offer')->getContent();
        $this->assertStringContainsString('299 ر.س', $two);
        $this->assertStringNotContainsString('بدلًا من', $two);

        // Offer price not below the service price: no strike-through.
        $this->offer('not-cheaper-offer', 450, [$service]);
        $this->assertStringNotContainsString('بدلًا من', $this->get('/offers/not-cheaper-offer')->getContent());

        // Service price hidden from the public: no public "before" either.
        $service->update(['show_price' => false]);
        $this->assertStringNotContainsString('بدلًا من', $this->get('/offers/honest-offer')->getContent());
    }

    public function test_the_offer_price_appears_on_the_offers_index_and_on_the_service_page(): void
    {
        $service = $this->service('listed-offer-svc', ['pricing_mode' => 'starting_from', 'price_min' => 500]);
        $this->offer('listed-offer', 349, [$service]);

        $this->assertStringContainsString('349 ر.س', $this->get('/offers')->assertOk()->getContent());
        $this->assertStringContainsString('349 ر.س', $this->get('/services/listed-offer-svc')->assertOk()->getContent());
    }

    public function test_priced_pages_trigger_no_lazy_loading(): void
    {
        $service = $this->service('lazy-priced-svc', ['pricing_mode' => 'range', 'price_min' => 300, 'price_max' => 500]);
        $this->offer('lazy-priced-offer', 250, [$service]);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            foreach (['/services/lazy-priced-svc', '/offers/lazy-priced-offer', '/offers', '/services', '/'] as $url) {
                $this->get($url)->assertOk();
            }
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    private function service(string $slug, array $attributes): Service
    {
        $service = $this->createCompliantServicePage(slug: $slug)->pageable;
        $service->update([...$attributes, 'name' => 'خدمة '.$slug]);

        return $service->fresh();
    }

    /**
     * @param  array<int, Service>  $services
     */
    private function offer(string $slug, ?float $price, array $services, ?string $label = null): Offer
    {
        $offer = Offer::factory()->create(['title' => 'عرض '.$slug, 'discount_label' => $label, 'offer_price' => $price, 'is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);
        $offer->services()->attach(collect($services)->pluck('id'));
        $page = Page::factory()->create(['type' => PageType::Offer, 'title' => 'عرض '.$slug, 'slug' => $slug, 'status' => PageStatus::Draft]);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>شروط.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        return $offer;
    }

    /** The page body only - the layout's own JSON-LD would otherwise match. */
    private function mainOf(string $html): string
    {
        preg_match('/<main[\s\S]*<\/main>/u', $html, $m);

        return $m[0] ?? $html;
    }
}
