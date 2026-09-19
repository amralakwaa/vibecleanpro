<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\Page;
use App\Seo\PublishingGate;
use Database\Seeders\ArticleContentSeeder;
use Database\Seeders\ProductionContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleContentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionContentSeeder::class);
    }

    public function test_it_loads_the_ten_launch_articles_as_drafts_linked_to_services(): void
    {
        $this->seed(ArticleContentSeeder::class);

        $this->assertSame(10, Article::query()->count());
        $this->assertSame(10, Page::query()->where('type', PageType::Article)->where('status', PageStatus::Draft)->count());

        $prices = Page::query()->where('slug', 'cleaning-prices-riyadh')->first();
        $this->assertSame(4, $prices->pageable->services()->count());
        $this->assertSame(3, $prices->faqs()->count());
        $this->assertSame(['rich_text', 'cta', 'faq'], $prices->contentBlocks()->orderBy('position')->pluck('type')->all());
    }

    public function test_article_bodies_carry_no_riyal_prices_or_superlative_claims(): void
    {
        $content = require database_path('seeders/content/articles.php');

        foreach ($content['articles'] as $article) {
            $text = $article['title'].' '.$article['body'].' '.collect($article['faqs'])->flatten()->implode(' ');
            $this->assertDoesNotMatchRegularExpression('/\d+\s*(ر\.س|ريال|SAR)/u', $text, $article['slug']);
            $this->assertStringNotContainsString('نحن الأفضل', $text, $article['slug']);
        }
    }

    public function test_internal_links_point_only_to_published_services_the_quote_form_or_other_articles(): void
    {
        $content = require database_path('seeders/content/articles.php');
        $articleSlugs = collect($content['articles'])->pluck('slug');
        $allowedServices = ['villa-cleaning', 'office-cleaning', 'facade-cleaning', 'post-construction-cleaning'];

        foreach ($content['articles'] as $article) {
            preg_match_all('/href="([^"]+)"/', $article['body'], $matches);

            foreach ($matches[1] as $href) {
                $isAllowed = $href === '/quote'
                    || in_array(str_replace('/services/', '', $href), $allowedServices, true)
                    || $articleSlugs->contains(str_replace('/blog/', '', $href));
                $this->assertTrue($isAllowed, "{$article['slug']} links to {$href}");
            }
        }
    }

    public function test_running_twice_keeps_editor_changes(): void
    {
        $this->seed(ArticleContentSeeder::class);
        Page::query()->where('slug', 'villa-cleaning-checklist')->update(['title' => 'عنوان المحرر']);

        $this->seed(ArticleContentSeeder::class);

        $this->assertSame(10, Article::query()->count());
        $this->assertSame('عنوان المحرر', Page::query()->where('slug', 'villa-cleaning-checklist')->value('title'));
    }

    public function test_an_article_passes_the_publishing_gate(): void
    {
        $this->seed(ArticleContentSeeder::class);

        $result = app(PublishingGate::class)->evaluate(Page::query()->where('slug', 'courtyard-interlock-cleaning')->first());

        $this->assertTrue($result->canPublish(), $result->errors()->pluck('message')->implode(' | '));
    }
}
