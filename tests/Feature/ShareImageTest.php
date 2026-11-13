<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * No logo is uploaded, so pages without an explicit OG image used to be
 * shared with no picture. The chain is now: SEO OG image -> the page's own
 * photo -> the site default (homepage hero) -> logo.
 */
class ShareImageTest extends TestCase
{
    use RefreshDatabase;

    private function ogImage(string $url): ?string
    {
        preg_match('/<meta property="og:image" content="([^"]+)"/', $this->get($url)->assertOk()->getContent(), $m);

        return $m[1] ?? null;
    }

    public function test_a_service_without_an_og_image_is_shared_with_its_featured_photo(): void
    {
        $photo = Media::factory()->create(['path' => 'media/library/villa.jpg']);
        $service = Service::factory()->create(['featured_media_id' => $photo->id]);
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'villa']);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create(['og_image_media_id' => null]);
        $page->update(['status' => PageStatus::Published]);

        $this->assertSame($photo->url(), $this->ogImage('/services/villa'));
    }

    public function test_controller_pages_fall_back_to_the_homepage_hero(): void
    {
        $hero = Media::factory()->create(['path' => 'media/library/home-hero.jpg']);
        SiteSetting::query()->updateOrCreate(['key' => SiteSetting::HOME_HERO_MEDIA_ID], ['value' => (string) $hero->id, 'type' => 'media']);
        SiteSetting::forgetMap();

        foreach (['/', '/contact', '/areas', '/offers'] as $url) {
            $this->assertSame($hero->url(), $this->ogImage($url), $url);
        }
        $this->get('/contact')->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }

    public function test_a_real_project_photo_is_shared_before_the_library_hero(): void
    {
        $hero = Media::factory()->create(['path' => 'media/library/home-hero.jpg']);
        SiteSetting::query()->updateOrCreate(['key' => SiteSetting::HOME_HERO_MEDIA_ID], ['value' => (string) $hero->id, 'type' => 'media']);
        SiteSetting::forgetMap();

        $project = Project::factory()->create(['is_featured' => true]);
        $after = Media::factory()->create(['path' => 'media/projects/after.jpg']);
        $project->media()->attach($after->id, ['stage' => 'after', 'sort_order' => 0]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'real-job']);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $this->assertSame($after->url(), $this->ogImage('/areas'));
        $this->assertSame($after->url(), $this->ogImage('/projects/real-job'));
    }

    public function test_without_any_image_no_og_image_tag_is_emitted(): void
    {
        $this->assertNull($this->ogImage('/contact'));
    }
}
