<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Seo\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /contact, /offers and /areas are controller-rendered, so their editorial
 * content comes from a published Landing page with the same slug.
 */
class IndexCompanionTest extends TestCase
{
    use RefreshDatabase;

    private function companion(string $slug, PageStatus $status = PageStatus::Published): Page
    {
        $page = Page::factory()->create(['type' => PageType::Landing, 'slug' => $slug, 'title' => "مرافق {$slug}"]);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => "<h2>دليل {$slug} المحرر</h2><p>نص يديره المحرر.</p>"], 'position' => 0]);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة'], 'position' => 1]);
        $page->faqs()->create(['question' => "سؤال عن {$slug}؟", 'answer' => 'إجابة واضحة.', 'is_active' => true, 'sort_order' => 1]);
        SeoMetadata::factory()->for($page)->create(['meta_title' => "عنوان {$slug} من اللوحة", 'meta_description' => "وصف {$slug} من اللوحة"]);
        $page->update(['status' => $status]);

        return $page;
    }

    public function test_each_index_renders_its_published_companion_content_faqs_and_seo(): void
    {
        foreach (['contact', 'offers', 'areas'] as $slug) {
            $this->companion($slug);

            $html = $this->get("/{$slug}")->assertOk()->getContent();

            $this->assertStringContainsString("دليل {$slug} المحرر", $html, $slug);
            $this->assertStringContainsString("سؤال عن {$slug}؟", $html, $slug);
            $this->assertStringContainsString('"@type":"FAQPage"', $html, $slug);
            $this->assertStringContainsString("<title>عنوان {$slug} من اللوحة</title>", $html, $slug);
            $this->assertStringContainsString('<link rel="canonical" href="'.url("/{$slug}").'">', $html, $slug);
        }
    }

    public function test_a_draft_companion_changes_nothing(): void
    {
        $this->companion('contact', PageStatus::Draft);

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringNotContainsString('دليل contact المحرر', $html);
        $this->assertStringNotContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('تواصل معنا', $html);
    }

    public function test_the_companion_never_gets_a_second_url_or_a_second_sitemap_entry(): void
    {
        $this->companion('contact');

        $locs = app(SitemapGenerator::class)->entries()->pluck('loc');

        $this->assertSame(1, $locs->filter(fn ($loc) => $loc === url('/contact'))->count());
    }
}
