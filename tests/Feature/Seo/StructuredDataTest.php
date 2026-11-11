<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Service;
use App\Seo\StructuredDataGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private StructuredDataGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(StructuredDataGenerator::class);
    }

    public function test_sitewide_returns_nothing_when_there_is_no_business_profile_yet(): void
    {
        $this->assertSame([], $this->generator->sitewide());
    }

    public function test_local_business_only_includes_the_fields_that_actually_have_real_data(): void
    {
        // 'city' defaults to Riyadh at the schema level (the business's
        // fixed, real city, not an invented value), so an address block
        // still appears - but only with the fields that are actually real.
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);

        $blocks = $this->generator->sitewide();
        $localBusiness = collect($blocks)->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertNotNull($localBusiness);
        $this->assertSame('Vibe Clean Pro', $localBusiness['name']);
        $this->assertArrayNotHasKey('telephone', $localBusiness);
        $this->assertArrayNotHasKey('geo', $localBusiness);
        $this->assertArrayNotHasKey('logo', $localBusiness);
        $this->assertArrayNotHasKey('sameAs', $localBusiness);
        $this->assertArrayHasKey('address', $localBusiness);
        $this->assertArrayNotHasKey('streetAddress', $localBusiness['address'], 'No street address was ever entered - it must not be fabricated.');
    }

    public function test_business_identity_uses_e164_phone_and_only_entity_profiles_in_same_as(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'phone' => '0557100772',
            'google_business_profile_url' => 'https://maps.google.com/?cid=123',
            'social_links' => ['tiktok' => 'https://tiktok.com/@biz', 'whatsapp' => 'https://wa.me/966557100772'],
        ]);

        $business = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertSame('+966557100772', $business['telephone']);
        $this->assertSame(['https://maps.google.com/?cid=123', 'https://tiktok.com/@biz'], $business['sameAs']);
    }

    public function test_never_emits_aggregate_rating_review_or_review_count_under_any_circumstances(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'phone' => '0500000000',
            'address' => 'شارع الملك فهد',
            'city' => 'الرياض',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);
        $page = $this->createCompliantServicePage();

        $json = json_encode([...$this->generator->sitewide(), ...$this->generator->forPage($page)]);

        // Google treats a business rating itself about itself as
        // self-serving and ineligible - this project never emits it,
        // regardless of what data exists.
        $this->assertStringNotContainsString('AggregateRating', $json);
        $this->assertStringNotContainsString('"review"', $json);
        $this->assertStringNotContainsString('reviewCount', $json);
        $this->assertStringNotContainsString('ratingValue', $json);
    }

    public function test_geo_only_appears_when_both_latitude_and_longitude_are_set(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'latitude' => null, 'longitude' => null]);

        $localBusiness = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertArrayNotHasKey('geo', $localBusiness);
    }

    public function test_geo_appears_when_both_coordinates_are_real(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'latitude' => 24.7136, 'longitude' => 46.6753]);

        $localBusiness = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertArrayHasKey('geo', $localBusiness);
        $this->assertSame(24.7136, $localBusiness['geo']['latitude']);
        $this->assertSame(46.6753, $localBusiness['geo']['longitude']);
    }

    public function test_address_only_appears_when_address_or_city_is_real(): void
    {
        // 'city' is not nullable at the schema level (it defaults to the
        // business's real city), so the only way to exercise "neither is
        // real" is an explicitly blanked-out city, not a null one.
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'address' => null, 'city' => '']);

        $localBusiness = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertArrayNotHasKey('address', $localBusiness);
    }

    public function test_address_appears_when_city_is_real_even_without_a_street_address(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);

        $localBusiness = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertArrayHasKey('address', $localBusiness);
        $this->assertSame('الرياض', $localBusiness['address']['addressLocality']);
    }

    public function test_opening_hours_are_never_emitted_since_the_architecture_does_not_model_them_yet(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'working_hours' => ['sunday' => '9:00-21:00'],
        ]);

        $localBusiness = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertArrayNotHasKey('openingHoursSpecification', $localBusiness);
        $this->assertArrayNotHasKey('openingHours', $localBusiness);
    }

    public function test_logo_and_image_only_appear_when_a_real_logo_media_is_set(): void
    {
        $profile = BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $localBusinessWithoutLogo = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));
        $this->assertArrayNotHasKey('logo', $localBusinessWithoutLogo);

        $media = Media::factory()->create();
        $profile->update(['logo_media_id' => $media->id]);

        $localBusinessWithLogo = collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));
        $this->assertArrayHasKey('logo', $localBusinessWithLogo);
        $this->assertSame($media->url(), $localBusinessWithLogo['logo']);
    }

    public function test_webpage_block_omits_description_and_date_published_when_they_do_not_exist(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['meta_description' => null]);
        $page->published_at = null;
        $page->save();

        $webPage = collect($this->generator->forPage($page->fresh(['seoMetadata', 'pageable'])))->firstWhere('@type', 'WebPage');

        $this->assertArrayNotHasKey('description', $webPage);
        $this->assertArrayNotHasKey('datePublished', $webPage);
    }

    public function test_a_service_page_produces_a_service_block_with_area_served_from_real_attached_areas(): void
    {
        $page = $this->createCompliantServicePage();

        // areaServed must only ever name areas that are themselves real,
        // reachable public pages - give the fixture's attached Area one.
        $areaPage = Page::factory()->create(['type' => PageType::Area, 'slug' => 'area-served-fixture']);
        $page->pageable->areas->first()->page()->save($areaPage);
        ContentBlock::factory()->for($areaPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $areaPage->update(['status' => PageStatus::Published]);

        $service = collect($this->generator->forPage($page))->firstWhere('@type', 'Service');

        $this->assertNotNull($service);
        $this->assertNotEmpty($service['areaServed']);
        $this->assertArrayHasKey('name', $service['areaServed'][0]);
    }

    public function test_area_served_never_names_an_area_with_no_published_page(): void
    {
        $page = $this->createCompliantServicePage();
        // The fixture's own attached Area has no Page at all.

        $service = collect($this->generator->forPage($page))->firstWhere('@type', 'Service');

        $this->assertNotNull($service);
        $this->assertArrayNotHasKey('areaServed', $service);
    }

    public function test_an_article_is_linked_to_its_webpage_in_one_graph(): void
    {
        $article = Article::factory()->create();
        $page = $this->makeBarePage([
            'type' => PageType::Article,
            'slug' => 'linked-guide',
            'pageable_type' => $article->getMorphClass(),
            'pageable_id' => $article->id,
        ]);

        $blocks = collect($this->generator->forPage($page->fresh()))->keyBy('@type');
        $url = rtrim(config('app.url'), '/').'/blog/linked-guide';

        $this->assertSame($url.'#webpage', $blocks['WebPage']['@id']);
        $this->assertSame($url.'#article', $blocks['Article']['@id']);
        $this->assertSame(['@id' => $url.'#webpage'], $blocks['Article']['mainEntityOfPage']);
        $this->assertSame('ar', $blocks['Article']['inLanguage']);
    }

    public function test_an_area_page_produces_no_service_or_article_typed_block(): void
    {
        $page = $this->createCompliantAreaPage();

        $types = collect($this->generator->forPage($page))->pluck('@type');

        $this->assertFalse($types->contains('Service'));
        $this->assertFalse($types->contains('Article'));
    }

    public function test_breadcrumb_list_json_ld_matches_the_breadcrumb_resolver(): void
    {
        $page = $this->createCompliantServicePage();

        $breadcrumbList = collect($this->generator->forPage($page))->firstWhere('@type', 'BreadcrumbList');

        $this->assertCount(3, $breadcrumbList['itemListElement']);
        $this->assertSame(1, $breadcrumbList['itemListElement'][0]['position']);
        $this->assertSame('الرئيسية', $breadcrumbList['itemListElement'][0]['name']);
        $this->assertSame(rtrim(config('app.url'), '/').'/services', $breadcrumbList['itemListElement'][1]['item']);
        $this->assertArrayNotHasKey('item', $breadcrumbList['itemListElement'][2], 'The current page itself must not link to itself in the breadcrumb trail.');
    }

    public function test_faq_page_schema_mirrors_the_active_faqs_of_an_area_page(): void
    {
        // Since commit 7d29934 area pages emit FAQPage. No Google rich result
        // is expected (that feature is removed); the markup only mirrors what readers see.
        $page = $this->createCompliantAreaPage();
        Faq::factory()->for($page)->create(['question' => 'ما هي خدماتكم؟', 'answer' => 'نقدم خدمات التنظيف المنزلي.', 'sort_order' => 1]);
        Faq::factory()->for($page)->create(['question' => 'هل تعملون في الرياض؟', 'answer' => 'نعم، نغطي جميع أحياء الرياض.', 'sort_order' => 2]);

        $faqPage = collect($this->generator->forPage($page->fresh()))->firstWhere('@type', 'FAQPage');

        $this->assertSame(['ما هي خدماتكم؟', 'هل تعملون في الرياض؟'], collect($faqPage['mainEntity'])->pluck('name')->all());
    }

    public function test_faq_page_schema_is_absent_when_a_page_has_no_faqs(): void
    {
        $page = $this->createCompliantAreaPage();

        $types = collect($this->generator->forPage($page))->pluck('@type');

        $this->assertFalse($types->contains('FAQPage'));
    }

    public function test_faq_page_schema_excludes_inactive_faqs_for_non_area_non_service_page(): void
    {
        // Other page types (e.g. Landing) still emit FAQPage with only active FAQs.
        $page = Page::factory()->create(['type' => PageType::Landing, 'status' => PageStatus::Published, 'published_at' => now()->subDay()]);
        $page->seoMetadata()->create(['meta_title' => 'Test', 'robots_index' => true, 'robots_follow' => true]);
        Faq::factory()->for($page)->create(['question' => 'سؤال نشط؟', 'answer' => 'جواب نشط.', 'sort_order' => 1, 'is_active' => true]);
        Faq::factory()->for($page)->create(['question' => 'سؤال معطل؟', 'answer' => 'جواب معطل.', 'sort_order' => 2, 'is_active' => false]);

        $faqPage = collect($this->generator->forPage($page->fresh()))->firstWhere('@type', 'FAQPage');

        $this->assertNotNull($faqPage);
        $this->assertCount(1, $faqPage['mainEntity']);
        $this->assertSame('سؤال نشط؟', $faqPage['mainEntity'][0]['name']);
    }

    public function test_an_area_page_emits_each_offered_service_as_a_service_anchored_to_that_area(): void
    {
        $area = Area::factory()->create(['name' => 'حي الملقا']);
        $service = $this->publishedService('deep-cleaning-service', 'تنظيف عميق');
        $area->services()->attach($service);

        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => 'al-malqa-schema']);
        $area->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $serviceBlock = collect($this->generator->forPage($page->fresh(['pageable'])))->firstWhere('@type', 'Service');

        $this->assertNotNull($serviceBlock);
        $this->assertSame('تنظيف عميق', $serviceBlock['name']);
        $this->assertSame('حي الملقا', $serviceBlock['areaServed']['name']);
        $this->assertSame(url('/#business'), $serviceBlock['provider']['@id']);
    }

    public function test_an_area_page_never_anchors_a_service_whose_page_is_not_published(): void
    {
        $area = Area::factory()->create();
        $area->services()->attach(Service::factory()->create()); // no published page

        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => 'area-unpublished-service']);
        $area->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $types = collect($this->generator->forPage($page->fresh(['pageable'])))->pluck('@type');

        $this->assertFalse($types->contains('Service'));
    }

    public function test_an_offer_page_emits_an_offer_block_without_inventing_a_price(): void
    {
        $offer = Offer::factory()->create(['title' => 'باقة المنزل المتكامل', 'offer_price' => null]);
        $service = $this->publishedService('home-cleaning-service', 'تنظيف منازل');
        $offer->services()->attach($service);

        $page = Page::factory()->create(['type' => PageType::Offer, 'slug' => 'complete-home-schema']);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->seoMetadata()->create(['meta_description' => 'باقة تنظيف شاملة.', 'robots_index' => true, 'robots_follow' => true]);
        $page->update(['status' => PageStatus::Published]);

        $offerBlock = collect($this->generator->forPage($page->fresh(['pageable', 'seoMetadata'])))->firstWhere('@type', 'Offer');

        $this->assertNotNull($offerBlock);
        $this->assertSame('باقة المنزل المتكامل', $offerBlock['name']);
        $this->assertArrayNotHasKey('price', $offerBlock, 'a quote-based offer must not carry an invented price');
        $this->assertSame('تنظيف منازل', $offerBlock['itemOffered'][0]['name']);
        $this->assertSame(url('/#business'), $offerBlock['seller']['@id']);
    }

    public function test_an_offer_price_appears_only_when_the_editor_entered_one(): void
    {
        $offer = Offer::factory()->create(['title' => 'عرض بسعر ثابت', 'offer_price' => 499]);

        $page = Page::factory()->create(['type' => PageType::Offer, 'slug' => 'priced-offer-schema']);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $offerBlock = collect($this->generator->forPage($page->fresh(['pageable', 'seoMetadata'])))->firstWhere('@type', 'Offer');

        $this->assertSame(499.0, $offerBlock['price']);
        $this->assertSame(config('pricing.currency'), $offerBlock['priceCurrency']);
    }

    public function test_the_website_node_carries_the_arabic_brand_and_links_to_the_business(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو']);

        $website = collect($this->generator->sitewide())->firstWhere('@type', 'WebSite');

        $this->assertSame('فايب كلين برو', $website['name']);
        $this->assertSame('Vibe Clean Pro', $website['alternateName']);
        $this->assertSame('ar', $website['inLanguage']);
        $this->assertStringEndsWith('/#website', $website['@id']);
        $this->assertStringEndsWith('/#business', $website['publisher']['@id']);
    }

    public function test_every_webpage_is_part_of_the_website(): void
    {
        $service = $this->publishedService('graph-service', 'خدمة');

        $webPage = collect($this->generator->forPage($service->page))->firstWhere('@type', 'WebPage');

        $this->assertStringEndsWith('/#website', $webPage['isPartOf']['@id']);
    }

    public function test_the_commercial_registration_is_an_identifier_only_when_shown_publicly(): void
    {
        $profile = BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'commercial_registration_number' => '7056323897']);
        $business = fn () => collect($this->generator->sitewide())->first(fn (array $block) => in_array('LocalBusiness', (array) $block['@type'], true));

        $this->assertArrayNotHasKey('identifier', $business());

        $profile->update(['display_commercial_registration' => true]);

        $this->assertSame('7056323897', $business()['identifier']['value']);
    }

    public function test_the_homepage_marks_up_the_sitewide_faqs_it_renders(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        Faq::query()->create(['question' => 'هل تعملون يوم الجمعة؟', 'answer' => 'نعم بموعد مسبق.', 'is_active' => true, 'sort_order' => 1]);
        Faq::query()->create(['question' => 'سؤال مخفي', 'answer' => 'لا يظهر.', 'is_active' => false, 'sort_order' => 2]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('هل تعملون يوم الجمعة؟');
        $response->assertDontSee('سؤال مخفي');
    }

    private function publishedService(string $slug, string $name): Service
    {
        $service = Service::factory()->create(['name' => $name]);
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => $slug]);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        return $service->fresh();
    }
}
