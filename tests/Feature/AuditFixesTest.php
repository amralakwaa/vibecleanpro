<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * The Global Audit's shipped defects, each pinned so it cannot return:
 * the overlay header that floated above the hero, the editor blocks
 * that still rendered in the legacy design, the hero block that rendered
 * nothing, the static robots.txt that shadowed the real one, and the
 * unnamed footer social links.
 */
class AuditFixesTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_overlay_header_pulls_the_homepage_hero_up_behind_it(): void
    {
        // A published project with an "after" photo switches the homepage to the Evidence Hero + overlay header.
        $project = Project::factory()->create(['title' => 'مشروع-بطل']);
        $project->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'hero-project']);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $home = $this->get('/')->assertOk()->getContent();
        preg_match('/<header[\s\S]*?\sclass="([^"]*)"/', $home, $m);
        $this->assertStringContainsString('-mb-16 md:-mb-20', $m[1], 'overlay header must overlap the hero');

        // A regular page keeps a solid header with no negative margin.
        $services = $this->get('/services')->getContent();
        preg_match('/<header[\s\S]*?\sclass="([^"]*)"/', $services, $m);
        $this->assertStringNotContainsString('-mb-16', $m[1]);
        $this->assertStringContainsString('bg-white/95', $m[1]);
    }

    public function test_the_hero_block_renders_its_heading_and_action_instead_of_nothing(): void
    {
        $page = $this->standalone('landing-hero', [
            ['type' => 'hero', 'data' => ['heading' => 'عنوان-كتلة-البطل', 'subheading' => 'وصف-كتلة-البطل', 'cta_label' => 'زر-البطل', 'cta_url' => 'https://example.test/go']],
        ]);

        $html = $this->get('/landing-hero')->assertOk()->getContent();

        $this->assertStringContainsString('عنوان-كتلة-البطل', $html);
        $this->assertStringContainsString('وصف-كتلة-البطل', $html);
        $this->assertStringContainsString('href="https://example.test/go"', $html);
        $this->assertStringContainsString('زر-البطل', $html);
    }

    public function test_the_cta_block_renders_the_system_band_with_a_real_quote_action(): void
    {
        $this->standalone('landing-cta', [['type' => 'cta', 'data' => ['heading' => 'عنوان-الدعوة', 'button_url' => 'https://wa.me/966500000000']]]);

        $html = $this->get('/landing-cta')->assertOk()->getContent();

        $this->assertStringContainsString('عنوان-الدعوة', $html);
        $this->assertStringContainsString('href="'.route('public.quote').'"', $html);
        $this->assertStringContainsString('https://wa.me/966500000000', $html);
        $this->assertStringNotContainsString('rounded-3xl', $html);
        $this->assertDoesNotMatchRegularExpression('/<h2[^>]*font-bold/', $html);
    }

    public function test_inclusions_and_price_factor_blocks_render_only_editor_items(): void
    {
        $this->standalone('landing-scope', [
            ['type' => 'inclusions', 'data' => ['heading' => 'ماذا-تشمل', 'included' => [['item' => 'بند-مشمول'], ['item' => '']], 'excluded' => [['item' => 'بند-غير-مشمول']]]],
            ['type' => 'price_factors', 'data' => ['heading' => 'ما-يحدد-السعر', 'items' => [['title' => 'المساحة', 'description' => 'كلما زادت المساحة زاد الوقت.'], ['title' => '']], 'note' => 'ملاحظة-العوامل']],
        ]);

        $html = $this->get('/landing-scope')->assertOk()->getContent();

        foreach (['ماذا-تشمل', 'بند-مشمول', 'ما تشمله الخدمة', 'بند-غير-مشمول', 'ما لا تشمله', 'ما-يحدد-السعر', 'المساحة', 'كلما زادت المساحة زاد الوقت.', 'ملاحظة-العوامل'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        // Blank items are dropped; no prices are invented in a factors block.
        $this->assertDoesNotMatchRegularExpression('/\d+ ر\.س/u', $html);

        // An inclusions block with nothing in it renders nothing at all.
        $this->standalone('landing-empty-scope', [['type' => 'inclusions', 'data' => ['heading' => 'عنوان-فارغ', 'included' => [], 'excluded' => []]], ['type' => 'rich_text', 'data' => ['content' => '<p>نص.</p>']]]);
        $this->assertStringNotContainsString('عنوان-فارغ', $this->get('/landing-empty-scope')->getContent());
    }

    public function test_robots_txt_comes_from_the_controller_with_the_sitemap_and_admin_rules(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'), 'a static robots.txt would shadow the route');

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
    }

    public function test_footer_social_links_are_named_after_their_platform(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'social_links' => ['Instagram' => 'https://instagram.com/example', 'TikTok' => 'https://tiktok.com/@example', 'empty' => '']]);

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<a href="https:\/\/instagram\.com\/example"[^>]*>\s*Instagram\s*<\/a>/u', $html);
        $this->assertMatchesRegularExpression('/<a href="https:\/\/tiktok\.com\/@example"[^>]*>\s*TikTok\s*<\/a>/u', $html);
        preg_match('/<a href="https:\/\/instagram\.com\/example"[^>]*class="([^"]*)"/', $html, $m);
        $this->assertStringContainsString('min-h-11', $m[1]);
        $this->assertStringNotContainsString('href=""', $html);
    }

    public function test_the_package_block_prints_admin_prices_without_a_computed_percentage(): void
    {
        $this->standalone('landing-packages', [['type' => 'packages', 'data' => ['heading' => 'الباقات', 'items' => [
            ['name' => 'باقة-أساسية', 'variant' => 'شقة غرفتين', 'price' => 675, 'previous_price' => 879, 'included_items' => "بند أ\nبند ب"],
            ['name' => 'باقة-بلا-خصم', 'price' => 900, 'previous_price' => null],
        ]]]]);

        $html = $this->get('/landing-packages')->assertOk()->getContent();

        $this->assertStringContainsString('باقة-أساسية', $html);
        $this->assertStringContainsString('675 ر.س', $html);
        $this->assertMatchesRegularExpression('/<s[^>]*>879 ر\.س<\/s>/u', $html);
        $this->assertStringNotContainsString('خصم 23%', $html);
        $this->assertSame(1, preg_match_all('/<s[\s>]/u', $html), 'only the real previous price is struck through');
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function standalone(string $slug, array $blocks): Page
    {
        $page = Page::factory()->create(['type' => PageType::Landing, 'slug' => $slug, 'title' => 'صفحة '.$slug]);
        foreach ($blocks as $i => $block) {
            ContentBlock::factory()->for($page)->create([...$block, 'position' => $i]);
        }
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $page;
    }
}
