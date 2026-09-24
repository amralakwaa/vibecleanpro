<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Page;
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
        $localBusiness = collect($blocks)->firstWhere('@type', 'LocalBusiness');

        $this->assertNotNull($localBusiness);
        $this->assertSame('Vibe Clean Pro', $localBusiness['name']);
        $this->assertArrayNotHasKey('telephone', $localBusiness);
        $this->assertArrayNotHasKey('geo', $localBusiness);
        $this->assertArrayNotHasKey('logo', $localBusiness);
        $this->assertArrayNotHasKey('sameAs', $localBusiness);
        $this->assertArrayHasKey('address', $localBusiness);
        $this->assertArrayNotHasKey('streetAddress', $localBusiness['address'], 'No street address was ever entered - it must not be fabricated.');
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

        $localBusiness = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');

        $this->assertArrayNotHasKey('geo', $localBusiness);
    }

    public function test_geo_appears_when_both_coordinates_are_real(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'latitude' => 24.7136, 'longitude' => 46.6753]);

        $localBusiness = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');

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

        $localBusiness = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');

        $this->assertArrayNotHasKey('address', $localBusiness);
    }

    public function test_address_appears_when_city_is_real_even_without_a_street_address(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);

        $localBusiness = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');

        $this->assertArrayHasKey('address', $localBusiness);
        $this->assertSame('الرياض', $localBusiness['address']['addressLocality']);
    }

    public function test_opening_hours_are_never_emitted_since_the_architecture_does_not_model_them_yet(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'working_hours' => ['sunday' => '9:00-21:00'],
        ]);

        $localBusiness = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');

        $this->assertArrayNotHasKey('openingHoursSpecification', $localBusiness);
        $this->assertArrayNotHasKey('openingHours', $localBusiness);
    }

    public function test_logo_and_image_only_appear_when_a_real_logo_media_is_set(): void
    {
        $profile = BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $localBusinessWithoutLogo = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');
        $this->assertArrayNotHasKey('logo', $localBusinessWithoutLogo);

        $media = Media::factory()->create();
        $profile->update(['logo_media_id' => $media->id]);

        $localBusinessWithLogo = collect($this->generator->sitewide())->firstWhere('@type', 'LocalBusiness');
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

        $this->assertCount(2, $breadcrumbList['itemListElement']);
        $this->assertSame(1, $breadcrumbList['itemListElement'][0]['position']);
        $this->assertSame('الرئيسية', $breadcrumbList['itemListElement'][0]['name']);
        $this->assertArrayNotHasKey('item', $breadcrumbList['itemListElement'][1], 'The current page itself must not link to itself in the breadcrumb trail.');
    }

    public function test_faq_page_schema_is_absent_for_area_pages_with_faqs(): void
    {
        // Area pages show FAQs to users but do NOT emit FAQPage JSON-LD:
        // Google limits FAQ rich results to authoritative sites; area pages are excluded.
        $page = $this->createCompliantAreaPage();
        Faq::factory()->for($page)->create(['question' => 'ما هي خدماتكم؟', 'answer' => 'نقدم خدمات التنظيف المنزلي.', 'sort_order' => 1]);
        Faq::factory()->for($page)->create(['question' => 'هل تعملون في الرياض؟', 'answer' => 'نعم، نغطي جميع أحياء الرياض.', 'sort_order' => 2]);

        $types = collect($this->generator->forPage($page->fresh()))->pluck('@type');

        $this->assertFalse($types->contains('FAQPage'));
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
}
