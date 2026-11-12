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

/**
 * Search Console showed Google following /blog?page=1 (a duplicate of /blog)
 * and any ?page=N answered 200 with a self-canonical, even past the end.
 */
class PaginationSeoTest extends TestCase
{
    use RefreshDatabase;

    private function publishArticles(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $article = Article::factory()->create(['title' => "مقال {$i}"]);
            $page = Page::factory()->create(['type' => PageType::Article, 'slug' => "article-{$i}"]);
            $article->page()->save($page);
            ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى.']]);
            SeoMetadata::factory()->for($page)->create();
            $page->update(['status' => PageStatus::Published]);
        }
    }

    public function test_a_page_past_the_last_one_is_a_404_not_an_empty_listing(): void
    {
        // One featured + 9 per page: 11 articles make exactly two pages.
        $this->publishArticles(11);

        $this->get('/blog?page=2')->assertOk();
        $this->get('/blog?page=3')->assertNotFound();
        $this->get('/blog?page=99')->assertNotFound();
        $this->get('/projects?page=5')->assertNotFound();
    }

    public function test_the_link_back_to_page_one_is_the_bare_index_url(): void
    {
        $this->publishArticles(11);

        $html = $this->get('/blog?page=2')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.url('/blog').'"', $html);
        $this->assertStringNotContainsString('page=1"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/blog?page=2').'">', $html);
    }
}
