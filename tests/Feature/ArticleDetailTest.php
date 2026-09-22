<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AssertsNoEmptyState;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Article Detail is a reading page. These tests pin what makes it one:
 * a single H1 from the CMS, dated and attributed only from real data,
 * contextual links that appear only when a relation actually exists,
 * a FAQ that renders once and before the closing prompt, and no lazy
 * loading anywhere on the page.
 */
class ArticleDetailTest extends TestCase
{
    use AssertsNoEmptyState, BuildsSeoFixtures, RefreshDatabase;

    public function test_a_published_article_renders_with_one_h1_from_the_cms_title(): void
    {
        $this->publishedArticle('reading-h1', title: 'عنوان المقال كما كتبه المحرر');

        $content = $this->get('/blog/reading-h1')->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, '<h1'));
        $this->assertStringContainsString('عنوان المقال كما كتبه المحرر', $content);
    }

    public function test_an_unpublished_article_is_not_reachable(): void
    {
        $article = Article::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Article, 'slug' => 'draft-only', 'status' => PageStatus::Draft]);
        $article->page()->save($page);

        $this->get('/blog/draft-only')->assertNotFound();
    }

    public function test_author_and_date_render_only_from_real_data(): void
    {
        $author = User::factory()->create(['name' => 'كاتب حقيقي']);
        $article = $this->publishedArticle('with-meta', author: $author);
        $article->page->update(['published_at' => '2026-05-10 08:00:00']);

        $content = $this->get('/blog/with-meta')->assertOk()->getContent();

        $this->assertStringContainsString('كاتب حقيقي', $content);
        $this->assertStringContainsString('datetime="2026-05-10"', $content);

        // No author, no invented byline.
        $this->publishedArticle('no-meta');
        $bare = $this->get('/blog/no-meta')->assertOk()->getContent();
        $this->assertStringNotContainsString('كاتب حقيقي', $bare);
    }

    public function test_service_and_area_links_appear_only_when_actually_related(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'linked-service');
        $areaPage = $this->createCompliantAreaPage(slug: 'linked-area', title: 'منطقة مرتبطة بالمقال');

        $article = $this->publishedArticle('with-links');
        $article->services()->attach($servicePage->pageable);
        $article->areas()->attach($areaPage->pageable);

        $content = $this->get('/blog/with-links')->assertOk()->getContent();

        $this->assertStringContainsString('خدمات ذات صلة', $content);
        $this->assertStringContainsString('href="'.route('public.service', 'linked-service').'"', $content);
        $this->assertStringContainsString('مناطق ذات صلة', $content);
        $this->assertStringContainsString('href="'.route('public.area', 'linked-area').'"', $content);

        // An article with no relations shows neither list - and no
        // customer-facing empty state either.
        $this->publishedArticle('no-links');
        $bare = $this->get('/blog/no-links')->assertOk()->getContent();
        $this->assertStringNotContainsString('خدمات ذات صلة', $bare);
        $this->assertStringNotContainsString('مناطق ذات صلة', $bare);
        $this->assertNoCustomerFacingEmptyState($bare);
    }

    public function test_the_faq_renders_once_and_before_the_closing_prompt(): void
    {
        $article = $this->publishedArticle('with-faq');
        ContentBlock::factory()->for($article->page)->create(['type' => 'faq', 'position' => 1, 'data' => ['heading' => 'أسئلة عن هذا الموضوع']]);
        Faq::factory()->for($article->page)->create(['question' => 'سؤال المقال؟', 'answer' => 'إجابة المقال.']);

        $content = $this->get('/blog/with-faq')->assertOk()->getContent();
        $body = $this->stripScripts($content);

        $this->assertSame(1, substr_count($body, 'أسئلة عن هذا الموضوع'));
        $this->assertSame(1, substr_count($body, 'سؤال المقال؟'));
        $this->assertStringContainsString('إجابة المقال.', $content);
        $this->assertLessThan(
            mb_strpos($content, 'تفضّل أن يقوم بذلك فريق متخصص'),
            mb_strpos($content, 'أسئلة عن هذا الموضوع'),
        );
    }

    public function test_the_body_renders_in_reading_mode(): void
    {
        $article = $this->publishedArticle('reading-mode');
        ContentBlock::factory()->for($article->page)->create(['type' => 'rich_text', 'position' => 1, 'data' => ['content' => '<h2>عنوان فرعي</h2><p>فقرة.</p>']]);

        $content = $this->get('/blog/reading-mode')->assertOk()->getContent();

        $this->assertStringContainsString('prose prose-reading', $content);
        $this->assertStringContainsString('<h2>عنوان فرعي</h2>', $content);
    }

    public function test_the_page_triggers_no_lazy_loading(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'lazy-svc');
        $areaPage = $this->createCompliantAreaPage(slug: 'lazy-area');
        $category = ArticleCategory::factory()->create();
        $author = User::factory()->create();

        $article = $this->publishedArticle('lazy-article', author: $author, category: $category);
        $article->services()->attach($servicePage->pageable);
        $article->areas()->attach($areaPage->pageable);
        $this->publishedArticle('lazy-sibling', category: $category);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/blog/lazy-article')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    private function publishedArticle(string $slug, ?string $title = null, ?User $author = null, ?ArticleCategory $category = null): Article
    {
        $article = Article::factory()->create(array_filter([
            'title' => $title,
            'author_id' => $author?->id,
            'article_category_id' => $category?->id,
        ]));

        $page = Page::factory()->create([
            'type' => PageType::Article,
            'title' => $title ?? $article->title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص المقال.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        return $article->fresh(['page']);
    }
}
