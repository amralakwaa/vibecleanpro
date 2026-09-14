<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedArticle(string $slug, string $title): Article
    {
        $article = Article::factory()->create(['title' => $title]);
        $page = Page::factory()->create(['type' => PageType::Article, 'slug' => $slug, 'title' => $title]);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $article;
    }

    public function test_only_published_articles_are_listed(): void
    {
        $this->createPublishedArticle('published-article', 'مقال منشور');
        Article::factory()->create(['title' => 'مقال غير منشور']);

        $html = $this->get('/blog')->getContent();

        $this->assertStringContainsString('مقال منشور', $html);
        $this->assertStringNotContainsString('مقال غير منشور', $html);
    }

    public function test_the_newest_article_is_pulled_out_as_featured_and_not_duplicated_below(): void
    {
        $this->createPublishedArticle('featured-article', 'مقال فريد للاختبار');

        $html = $this->get('/blog')->getContent();

        $this->assertSame(1, substr_count($html, 'مقال فريد للاختبار'));
    }

    public function test_the_index_shows_an_empty_state_when_no_articles_are_published(): void
    {
        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('لا توجد مقالات منشورة حاليًا');
    }

    public function test_newest_first_ordering_is_preserved_across_the_featured_spot_and_the_rows(): void
    {
        $this->travel(-3)->days();
        $this->createPublishedArticle('oldest', 'المقال الأقدم');
        $this->travelBack();
        $this->travel(-2)->days();
        $this->createPublishedArticle('middle', 'المقال الأوسط');
        $this->travelBack();
        $this->createPublishedArticle('newest', 'المقال الأحدث');

        $html = $this->get('/blog')->getContent();

        // Newest is featured (first), then the rows continue newest-first.
        $this->assertLessThan(mb_strpos($html, 'المقال الأوسط'), mb_strpos($html, 'المقال الأحدث'));
        $this->assertLessThan(mb_strpos($html, 'المقال الأقدم'), mb_strpos($html, 'المقال الأوسط'));
    }

    public function test_the_category_label_renders_as_organising_metadata_not_a_link(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'تصنيف-اختباري-مميز']);
        $article = $this->createPublishedArticle('categorised', 'مقال مصنف');
        $article->update(['article_category_id' => $category->id]);

        $html = $this->get('/blog')->getContent();

        $this->assertStringContainsString('تصنيف-اختباري-مميز', $html);
        // No category route exists, so the label must never become a link.
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*>[^<]*تصنيف-اختباري-مميز/u', $html);
    }

    public function test_pagination_is_preserved_beyond_nine_rows(): void
    {
        // 1 featured + 9 rows on page one, the 11th lands on page two.
        foreach (range(1, 11) as $i) {
            $this->travel(-$i)->minutes();
            $this->createPublishedArticle("paged-$i", "مقال مرقّم $i");
            $this->travelBack();
        }

        $pageOne = $this->get('/blog')->assertOk()->getContent();
        $pageTwo = $this->get('/blog?page=2')->assertOk()->getContent();

        $this->assertStringContainsString('مقال مرقّم 11', $pageTwo);
        $this->assertStringNotContainsString('مقال مرقّم 11', $pageOne);
        $this->assertStringContainsString('page=2', $pageOne);
    }

    public function test_the_index_triggers_no_lazy_loading(): void
    {
        $category = ArticleCategory::factory()->create();
        foreach (range(1, 4) as $i) {
            $this->createPublishedArticle("lazy-$i", "مقال $i")->update(['article_category_id' => $category->id]);
        }

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/blog')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }
}
