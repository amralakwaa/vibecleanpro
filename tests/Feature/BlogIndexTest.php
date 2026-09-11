<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SeoMetadata;
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
}
