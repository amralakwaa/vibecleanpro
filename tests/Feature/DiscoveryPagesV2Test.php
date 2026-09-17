<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\AreaGroup;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AssertsNoEmptyState;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Phase 4 contracts for the discovery pages: the blog legend names only
 * real categories with real counts and never links them, the article keeps
 * its reading column and derives its reading time from the real body, the
 * services index leads with the featured service once and prices through
 * PublicPrice only, and the areas index groups real areas with real counts.
 */
class DiscoveryPagesV2Test extends TestCase
{
    use AssertsNoEmptyState, BuildsSeoFixtures, RefreshDatabase;

    public function test_the_blog_legend_lists_only_categories_with_published_articles_and_never_links_them(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $used = ArticleCategory::factory()->create(['name' => 'تصنيف-مستخدم']);
        ArticleCategory::factory()->create(['name' => 'تصنيف-فارغ']);
        $this->publishedArticle('legend-a', ['article_category_id' => $used->id]);
        $this->publishedArticle('legend-b', ['article_category_id' => $used->id]);
        $draft = Article::factory()->create(['article_category_id' => $used->id]);
        $draft->page()->save(Page::factory()->create(['type' => PageType::Article, 'slug' => 'legend-draft', 'status' => PageStatus::Draft]));

        $html = $this->get('/blog')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/تصنيف-مستخدم<\/span>\s*<span[^>]*>مقالان</u', $html, 'the count is the published count only');
        $this->assertStringNotContainsString('تصنيف-فارغ', $html);
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*>[^<]*تصنيف-مستخدم/u', $html);
        $this->assertNoCustomerFacingEmptyState($html);
    }

    public function test_the_article_keeps_its_reading_column_and_derives_reading_time_from_the_real_body_only(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        $short = $this->publishedArticle('short-read');
        $long = $this->publishedArticle('long-read', [], str_repeat('كلمة ', 620));

        $shortHtml = $this->get('/blog/short-read')->assertOk()->getContent();
        $longHtml = $this->get('/blog/long-read')->assertOk()->getContent();

        $this->assertStringNotContainsString('قراءة', $shortHtml, 'a stub gets no reading time');
        $this->assertStringContainsString('حوالي 4 دقائق قراءة', $longHtml, 'stated as an estimate, never as precision');
        $this->assertStringContainsString('prose prose-reading', $longHtml);
        $this->assertMatchesRegularExpression('/<div class="[^"]*max-w-3xl[^"]*">\s*<div class="prose prose-reading">/u', $longHtml, 'the body stays in the narrow reading column');
        $this->assertStringNotContainsString('surface-atmos', $longHtml, 'no navy selling band on a reading page');
        $this->assertSame(1, substr_count($longHtml, '<h1'));
    }

    public function test_the_services_index_leads_with_the_featured_tile_once_and_prices_only_through_public_price(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $featured = $this->createCompliantServicePage(slug: 'idx-featured')->pageable;
        $featured->update(['name' => 'خدمة-البلاطة', 'is_featured' => true, 'pricing_mode' => 'starting_from', 'price_min' => 700, 'sort_order' => 5]);
        $hidden = $this->createCompliantServicePage(slug: 'idx-hidden')->pageable;
        $hidden->update(['name' => 'خدمة-سعر-مخفي', 'pricing_mode' => 'fixed', 'price_min' => 999, 'show_price' => false, 'sort_order' => 1]);
        $quote = $this->createCompliantServicePage(slug: 'idx-quote')->pageable;
        $quote->update(['name' => 'خدمة-عرض-فقط', 'pricing_mode' => 'quote_only', 'sort_order' => 2]);

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertLessThan(mb_strpos($html, 'خدمة-سعر-مخفي'), mb_strpos($html, 'خدمة-البلاطة'));
        $this->assertMatchesRegularExpression('/tile-scrim[\s\S]{0,900}خدمة-البلاطة[\s\S]{0,700}يبدأ من 700 ر\.س/u', $html, 'the featured tile carries its public price');
        $this->assertSame(1, substr_count($html, 'خدمة مميزة'));
        $this->assertStringNotContainsString('999', $html, 'a hidden price never leaks');
        $this->assertMatchesRegularExpression('/خدمة-عرض-فقط[\s\S]{0,700}تفاصيل الخدمة<\/span>/u', $html);
        $this->assertStringNotContainsString('الأكثر طلبًا', $html);
    }

    public function test_a_quote_only_service_shows_no_price_and_no_placeholder_in_the_price_slot_anywhere(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        $priced = $this->createCompliantServicePage(slug: 'slot-priced')->pageable;
        $priced->update(['name' => 'خدمة-بسعر', 'is_featured' => true, 'pricing_mode' => 'fixed', 'price_min' => 320]);
        $quote = $this->createCompliantServicePage(slug: 'slot-quote')->pageable;
        $quote->update(['name' => 'خدمة-بلا-سعر-معلن', 'pricing_mode' => 'quote_only']);

        foreach (['/', '/services', '/services/slot-quote'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            preg_match('/<main[\s\S]*<\/main>/u', $html, $main);
            // Isolate the quote-only service's own markup: its hero on the
            // detail page, its own tile/row on the listing pages.
            $scope = $path === '/services/slot-quote'
                ? mb_substr($main[0], 0, mb_strpos($main[0], '</section>'))
                : mb_substr($main[0], mb_strpos($main[0], 'خدمة-بلا-سعر-معلن'), 1200);

            $this->assertDoesNotMatchRegularExpression('/\d+\s*ر\.س|يبدأ من|السعر حسب الطلب|اطلب عرض سعر<\/span>|data-price/u', $scope, $path.': no number and no placeholder in the price slot');
        }

        $this->assertStringContainsString('320 ر.س', $this->get('/services')->getContent(), 'a real public price still renders');
    }

    public function test_the_areas_index_groups_real_areas_with_real_counts_and_a_legend_of_groups(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        $north = AreaGroup::factory()->create(['name' => 'مجموعة-شمال', 'sort_order' => 1]);
        $east = AreaGroup::factory()->create(['name' => 'مجموعة-شرق', 'sort_order' => 2]);
        AreaGroup::factory()->create(['name' => 'مجموعة-فارغة', 'sort_order' => 3]);
        foreach (['n1', 'n2', 'n3'] as $slug) {
            $this->createCompliantAreaPage(slug: 'idx-'.$slug)->pageable->update(['area_group_id' => $north->id]);
        }
        $this->createCompliantAreaPage(slug: 'idx-e1')->pageable->update(['area_group_id' => $east->id]);
        $this->createCompliantAreaPage(slug: 'idx-draft', status: PageStatus::Draft)->pageable->update(['area_group_id' => $east->id]);

        $html = $this->get('/areas')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/href="#areas-group-0"[\s\S]{0,300}مجموعة-شمال<\/span>\s*<span[^>]*>3 أحياء/u', $html);
        $this->assertMatchesRegularExpression('/href="#areas-group-1"[\s\S]{0,300}مجموعة-شرق<\/span>\s*<span[^>]*>حي واحد/u', $html, 'a draft area is never counted');
        $this->assertStringNotContainsString('مجموعة-فارغة', $html);
        $this->assertStringNotContainsString('0 خدمات', $html);
        $this->assertStringNotContainsString('<img', mb_substr($html, mb_strpos($html, '<main')), 'no picture pretends to be a neighbourhood');
        $this->assertNoCustomerFacingEmptyState($html);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedArticle(string $slug, array $attributes = [], string $body = '<p>مقدمة قصيرة.</p>'): Article
    {
        $article = Article::factory()->create($attributes);
        $page = Page::factory()->create(['type' => PageType::Article, 'slug' => $slug, 'title' => 'مقال '.$slug, 'status' => PageStatus::Draft]);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => $body]]);
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        return $article;
    }
}
