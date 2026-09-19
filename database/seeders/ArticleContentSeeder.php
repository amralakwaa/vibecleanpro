<?php

namespace Database\Seeders;

use App\Console\Commands\ImportMediaLibrary;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Loads the launch articles from database/seeders/content/articles.php as
 * DRAFTS. Publishing goes through the Publishing Gate like any page.
 *
 * Idempotent: an article is matched by its page slug and never touched
 * again once it exists, so editor changes are kept.
 */
class ArticleContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = require database_path('seeders/content/articles.php');

        $categories = collect($content['categories'])->mapWithKeys(fn (array $category) => [
            $category['key'] => ArticleCategory::query()->firstOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'sort_order' => $category['sort_order']])->id,
        ]);

        $servicesBySlug = Page::query()->where('type', PageType::Service)->with('pageable')->get()
            ->filter(fn (Page $page) => $page->pageable instanceof Service)
            ->mapWithKeys(fn (Page $page) => [$page->slug => $page->pageable->id]);

        foreach ($content['articles'] as $data) {
            if (Page::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $article = Article::query()->create([
                'article_category_id' => $categories[$data['category']],
                'featured_media_id' => Media::query()->where('path', ImportMediaLibrary::DIRECTORY.'/'.$data['featured_media_file'])->value('id'),
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
            ]);

            $article->services()->sync($servicesBySlug->only($data['services'])->values()->all());

            $page = new Page(['type' => PageType::Article, 'title' => $data['title'], 'slug' => $data['slug'], 'status' => PageStatus::Draft]);
            $article->page()->save($page);

            $page->seoMetadata()->create([
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'robots_index' => true,
                'robots_follow' => true,
            ]);

            $page->contentBlocks()->create(['type' => 'rich_text', 'position' => 1, 'is_active' => true, 'data' => ['content' => trim($data['body'])]]);
            $page->contentBlocks()->create(['type' => 'cta', 'position' => 2, 'is_active' => true, 'data' => [
                'heading' => $data['cta']['heading'],
                'button_label' => $data['cta']['label'],
                'button_url' => url($data['cta']['url']),
            ]]);
            $page->contentBlocks()->create(['type' => 'faq', 'position' => 3, 'is_active' => true, 'data' => []]);

            foreach ($data['faqs'] as $position => [$question, $answer]) {
                $page->faqs()->create(['question' => $question, 'answer' => $answer, 'sort_order' => $position + 1, 'is_active' => true]);
            }
        }
    }
}
