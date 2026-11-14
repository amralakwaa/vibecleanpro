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
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * No logo is uploaded, so pages without an explicit OG image used to be
 * shared with no picture. The chain is now: SEO OG image -> the page's own
 * photo -> the site default (homepage hero) -> logo.
 */
class ShareImageTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

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

    public function test_an_area_shares_its_own_project_photo_instead_of_a_stock_og_override(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'al-taawun');
        $stock = Media::factory()->create(['path' => 'media/library/apartment-cleaning.jpg']);
        $page->seoMetadata->update(['og_image_media_id' => $stock->id]);
        $project = $page->pageable->projects()->first();
        $after = Media::factory()->create(['path' => 'media/vibe/office-after.webp']);
        $project->media()->attach($after->id, ['stage' => 'after', 'sort_order' => 0]);
        $projectPage = Page::factory()->create(['type' => PageType::Project, 'slug' => 'office-job']);
        $project->page()->save($projectPage);
        ContentBlock::factory()->for($projectPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $projectPage->update(['status' => PageStatus::Published]);

        $this->assertSame($after->url(), $this->ogImage('/areas/al-taawun'));
    }

    public function test_an_area_falls_back_to_a_supporting_projects_first_photo(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'al-ghadir');
        $stock = Media::factory()->create(['path' => 'media/library/villa-cleaning.jpg']);
        $page->seoMetadata->update(['og_image_media_id' => $stock->id]);
        $project = Project::factory()->create();
        $photo = Media::factory()->create(['path' => 'media/vibe/during.webp']);
        $project->media()->attach($photo->id, ['stage' => 'during', 'sort_order' => 0]);
        $projectPage = Page::factory()->create(['type' => PageType::Project, 'slug' => 'support-job']);
        $project->page()->save($projectPage);
        ContentBlock::factory()->for($projectPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $projectPage->update(['status' => PageStatus::Published]);
        $page->pageable->supportingProjects()->attach($project->id, ['support_type' => 'supporting', 'sort_order' => 0, 'is_active' => true]);

        $this->assertSame($photo->url(), $this->ogImage('/areas/al-ghadir'));
    }

    public function test_a_stock_og_override_stays_when_the_page_has_no_real_photo(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'al-malaz');
        $stock = Media::factory()->create(['path' => 'media/library/villa-cleaning.jpg']);
        $page->seoMetadata->update(['og_image_media_id' => $stock->id]);

        $this->assertSame($stock->url(), $this->ogImage('/areas/al-malaz'));
    }

    public function test_a_real_photo_chosen_by_the_editor_always_wins(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'al-olaya');
        $chosen = Media::factory()->create(['path' => 'media/vibe/chosen.webp']);
        $page->seoMetadata->update(['og_image_media_id' => $chosen->id]);

        $this->assertSame($chosen->url(), $this->ogImage('/areas/al-olaya'));
    }

    public function test_listing_pages_each_share_a_different_real_project_photo(): void
    {
        $photos = [];
        foreach (range(1, 3) as $i) {
            $project = Project::factory()->create(['is_featured' => false, 'completed_at' => now()->subDays($i)]);
            $after = Media::factory()->create(['path' => "media/projects/after-{$i}.jpg"]);
            $project->media()->attach($after->id, ['stage' => 'after', 'sort_order' => 0]);
            $page = Page::factory()->create(['type' => PageType::Project, 'slug' => "job-{$i}"]);
            $project->page()->save($page);
            ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
            $page->update(['status' => PageStatus::Published]);
            $photos[] = $after->url();
        }

        $this->assertSame($photos[0], $this->ogImage('/'));
        $this->assertSame($photos[1], $this->ogImage('/projects'));
        $this->assertSame($photos[2], $this->ogImage('/services'));
    }

    public function test_without_any_image_no_og_image_tag_is_emitted(): void
    {
        $this->assertNull($this->ogImage('/contact'));
    }
}
