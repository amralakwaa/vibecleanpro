<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Seo\PublishingGate;
use App\Seo\StructuredDataGenerator;
use Database\Seeders\TrustPagesDraftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new BusinessProfile)->forceFill([
            'name' => 'Vibe Clean Pro',
            'phone' => '+966534999194',
            'whatsapp_number' => '966534999194',
            'city' => 'الرياض',
            'email' => 'info@vibecleanpro.com',
            'service_area' => 'مدينة الرياض',
            'working_hours' => ['كل أيام الأسبوع' => 'من 08:00 إلى 14:00'],
        ])->save();
    }

    private function draftPage(string $slug, PageType $type): Page
    {
        return Page::factory()->create(['type' => $type, 'title' => $slug, 'slug' => $slug, 'status' => PageStatus::Draft]);
    }

    /**
     * Seeded content minus the owner markers - what the page becomes once
     * the owner and legal counsel have answered.
     */
    private function publish(string $slug, PageType $type): Page
    {
        $page = $this->draftPage($slug, $type);
        $this->seed(TrustPagesDraftSeeder::class);

        $page->contentBlocks()->get()
            ->filter(fn (ContentBlock $block) => str_contains(json_encode($block->data, JSON_UNESCAPED_UNICODE) ?: '', PublishingGate::OWNER_INPUT_MARKER))
            ->each(fn (ContentBlock $block) => $block->delete());

        $page->refresh()->update(['status' => PageStatus::Published, 'published_at' => now()]);

        return $page->fresh();
    }

    private function assertSeoBasics(TestResponse $response, string $path): void
    {
        $html = $response->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), "{$path}: exactly one H1");
        $this->assertMatchesRegularExpression('/<title>.{5,}<\/title>/u', $html, "{$path}: a real title");
        $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]{20,}"/u', $html, "{$path}: a meta description");
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="'.preg_quote(rtrim(url($path), '/'), '#').'/?"#', $html, "{$path}: self-canonical");
        $this->assertMatchesRegularExpression('/<meta name="robots" content="[^"]+"/u', $html, "{$path}: robots directive");
        $this->assertStringContainsString('property="og:title"', $html, "{$path}: Open Graph");
    }

    // ---- Legal pages ----------------------------------------------------

    public function test_privacy_and_terms_are_drafts_that_return_404_and_are_not_linked(): void
    {
        $this->draftPage('privacy', PageType::Legal);
        $this->draftPage('terms', PageType::Legal);
        $this->seed(TrustPagesDraftSeeder::class);

        foreach (['privacy', 'terms'] as $slug) {
            $this->get('/'.$slug)->assertNotFound();
            $this->assertSame(PageStatus::Draft, Page::query()->where('slug', $slug)->value('status'));
        }

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('سياسة الخصوصية', $home);
        $this->assertStringNotContainsString('الشروط والأحكام', $home);
        $this->assertStringNotContainsString(url('/privacy'), $home);
    }

    public function test_the_about_page_is_written_in_full_and_asks_the_owner_nothing(): void
    {
        $this->draftPage('about', PageType::About);
        $this->seed(TrustPagesDraftSeeder::class);

        $page = Page::query()->where('slug', 'about')->first();
        $gate = app(PublishingGate::class)->evaluate($page);

        $this->assertTrue($gate->canPublish());
        $this->assertNotContains('owner_input', $gate->errors()->pluck('key')->all());
    }

    public function test_the_privacy_policy_is_written_in_full_and_asks_the_owner_nothing(): void
    {
        $this->draftPage('privacy', PageType::Legal);
        $this->seed(TrustPagesDraftSeeder::class);

        $page = Page::query()->where('slug', 'privacy')->first();
        $gate = app(PublishingGate::class)->evaluate($page);

        $this->assertTrue($gate->canPublish());
        $this->assertNotContains('owner_input', $gate->errors()->pluck('key')->all());

        $html = $page->contentBlocks()->get()->map(fn ($block) => $block->data['content'] ?? '')->implode(' ');
        foreach (['ما الذي نجمعه', 'ملفات الارتباط', 'مدة الاحتفاظ', 'حقوقك', 'بيانات الأطفال'] as $section) {
            $this->assertStringContainsString($section, $html);
        }
    }

    public function test_the_terms_page_is_written_in_full_and_asks_the_owner_nothing(): void
    {
        $this->draftPage('terms', PageType::Legal);
        $this->seed(TrustPagesDraftSeeder::class);

        $page = Page::query()->where('slug', 'terms')->first();
        $gate = app(PublishingGate::class)->evaluate($page);

        $this->assertNotContains('owner_input', $gate->errors()->pluck('key')->all());
        $this->assertTrue($gate->canPublish());

        // The six policy clauses the page shipped without.
        $html = $page->contentBlocks()->get()->map(fn ($block) => $block->data['content'] ?? '')->implode(' ');
        foreach (['الإلغاء وإعادة الجدولة', 'الدفع', 'التزامات العميل', 'الضمان وإعادة التنفيذ', 'المسؤولية والأضرار', 'الشكاوى', 'النظام الواجب التطبيق'] as $clause) {
            $this->assertStringContainsString($clause, $html);
        }
    }

    public function test_the_footer_links_each_legal_page_once_it_is_published(): void
    {
        $this->publish('privacy', PageType::Legal);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString(url('/privacy'), $html);
        $this->assertStringContainsString('سياسة الخصوصية', $html);
        $this->assertStringNotContainsString(url('/terms'), $html, 'terms is still a draft');
    }

    public function test_a_published_legal_page_is_indexable_calm_and_in_the_sitemap(): void
    {
        $this->publish('privacy', PageType::Legal);

        $this->assertSeoBasics($this->get('/privacy'), '/privacy');
        $html = $this->get('/privacy')->getContent();
        $this->assertStringContainsString('info@vibecleanpro.com', $html);
        $this->assertStringNotContainsString(PublishingGate::OWNER_INPUT_MARKER, $html);
        $this->assertStringNotContainsString('متوافق مع', $html, 'no compliance claim');
        $this->assertStringContainsString('<loc>'.url('/privacy').'</loc>', $this->get('/sitemap.xml')->getContent());
    }

    public function test_drafts_never_reach_the_sitemap(): void
    {
        $this->draftPage('privacy', PageType::Legal);
        $this->draftPage('terms', PageType::Legal);
        $this->draftPage('about', PageType::About);
        $this->seed(TrustPagesDraftSeeder::class);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['/privacy', '/terms', '/about'] as $path) {
            $this->assertStringNotContainsString(url($path).'</loc>', $xml);
        }

        preg_match_all('/<loc>(.*?)<\/loc>/', $xml, $matches);
        $this->assertSame(array_unique($matches[1]), $matches[1], 'no duplicate URLs');
    }

    // ---- About ----------------------------------------------------------

    public function test_about_renders_its_written_content_and_the_profile_facts(): void
    {
        $about = $this->publish('about', PageType::About);

        $this->assertSeoBasics($this->get('/about'), '/about');
        $html = $this->get('/about')->getContent();
        $this->assertStringNotContainsString(PublishingGate::OWNER_INPUT_MARKER, $html);
        $this->assertStringContainsString('كيف نعمل', $html);
        $this->assertStringContainsString('من 08:00 إلى 14:00', $html, 'hours come from the business profile');

        // Re-seeding a fresh draft writes the same finished page - no
        // owner prompt comes back, and the page may publish again.
        $about->contentBlocks()->delete();
        $about->update(['status' => PageStatus::Draft]);
        $this->seed(TrustPagesDraftSeeder::class);

        $this->assertStringNotContainsString(PublishingGate::OWNER_INPUT_MARKER, json_encode($about->fresh()->contentBlocks->pluck('data'), JSON_UNESCAPED_UNICODE));
        $this->assertTrue(app(PublishingGate::class)->evaluate($about->fresh())->canPublish());
    }

    public function test_about_makes_no_unprovable_claim(): void
    {
        $this->publish('about', PageType::About);
        $html = $this->get('/about')->getContent();

        foreach (['سنوات خبرة', 'عميل راضٍ', 'الأفضل', 'مرخص', 'شهادة', 'ضمان', 'تأسست'] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "about must not claim: {$claim}");
        }
    }

    // ---- Contact & quote ------------------------------------------------

    public function test_contact_shows_every_confirmed_channel_and_no_invented_address(): void
    {
        $this->assertSeoBasics($this->get('/contact'), '/contact');
        $html = $this->get('/contact')->getContent();

        $this->assertStringContainsString('tel:+966534999194', $html);
        $this->assertStringContainsString('https://wa.me/966534999194', $html);
        $this->assertStringContainsString('mailto:info@vibecleanpro.com', $html);
        $this->assertStringContainsString('من 08:00 إلى 14:00', $html);
        $this->assertStringContainsString('نقدم خدماتنا داخل مدينة الرياض.', $html);
        $this->assertStringNotContainsString('العنوان</dt>', $html, 'no address row without a stored address');
        $this->assertStringNotContainsString('maps.google', $html, 'no fabricated map');
        $this->assertStringContainsString('<form', $html);
    }

    public function test_the_contact_form_still_validates(): void
    {
        $this->post('/contact', ['name' => '', 'phone' => ''])->assertSessionHasErrors(['name', 'phone']);
        $this->post('/contact', ['name' => 'نورة', 'phone' => '0500000000'])->assertSessionHasNoErrors();
    }

    public function test_quote_states_what_the_customer_should_send_and_no_price(): void
    {
        $this->assertSeoBasics($this->get('/quote'), '/quote');
        $html = $this->get('/quote')->getContent();

        $this->assertStringContainsString('المساحة التقريبية', $html);
        $this->assertStringContainsString('عدد الأدوار', $html);
        $this->assertStringContainsString('واتساب', $html);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*(ر\.س|ريال)/u', $html, 'no invented price');
        $this->assertStringNotContainsString(url('/privacy'), $html, 'no dead privacy link while it is a draft');
    }

    // ---- Index pages ----------------------------------------------------

    public function test_every_index_page_has_a_single_h1_and_complete_seo(): void
    {
        foreach (['/', '/services', '/projects', '/areas', '/blog', '/offers', '/contact', '/quote'] as $path) {
            $this->assertSeoBasics($this->get($path), $path);
        }
    }

    // ---- Structured data ------------------------------------------------

    public function test_the_business_schema_states_only_confirmed_facts(): void
    {
        $business = collect(app(StructuredDataGenerator::class)->sitewide())->firstWhere('@type', 'LocalBusiness');

        $this->assertSame('+966534999194', $business['telephone']);
        $this->assertSame('info@vibecleanpro.com', $business['email']);
        $this->assertSame(['@type' => 'City', 'name' => 'الرياض'], $business['areaServed']);
        $this->assertSame([[
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'opens' => '08:00',
            'closes' => '14:00',
        ]], $business['openingHoursSpecification']);

        $this->assertArrayNotHasKey('streetAddress', $business['address'], 'no invented street');
        $this->assertArrayNotHasKey('geo', $business);
        $this->assertArrayNotHasKey('aggregateRating', $business);
        $this->assertArrayNotHasKey('award', $business);
        $this->assertArrayNotHasKey('foundingDate', $business);
    }

    public function test_unreadable_opening_hours_are_shown_but_never_turned_into_schema(): void
    {
        BusinessProfile::query()->first()->update(['working_hours' => ['حسب الطلب' => 'اتصل بنا']]);

        $business = collect(app(StructuredDataGenerator::class)->sitewide())->firstWhere('@type', 'LocalBusiness');

        $this->assertArrayNotHasKey('openingHoursSpecification', $business);
        $this->assertStringContainsString('اتصل بنا', $this->get('/contact')->getContent());
    }

    public function test_a_visible_faq_does_not_add_faqpage_markup(): void
    {
        $page = $this->createCompliantServicePage('villa-cleaning');
        $page->faqs()->create(['question' => 'كم يستغرق التنظيف؟', 'answer' => 'حسب المساحة.', 'sort_order' => 1, 'is_active' => true]);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => [], 'position' => 9]);

        $html = $this->get('/services/villa-cleaning')->assertOk()->getContent();

        $this->assertStringContainsString('كم يستغرق التنظيف؟', $html);
        $this->assertStringNotContainsString('FAQPage', $html);
        $this->assertStringNotContainsString('aggregateRating', $html);
    }
}
