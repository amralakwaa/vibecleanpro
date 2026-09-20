<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateServiceCovers;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use App\Seo\PublishingGate;
use Database\Seeders\ProductionContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * No service page ships without an image. A service with no approved
 * photo gets a drawn cover - an Illustration, which may carry a page but
 * can never stand as proof of work.
 */
class ServiceCoversTest extends TestCase
{
    use RefreshDatabase;

    private function service(string $slug): Service
    {
        return Service::query()->whereHas('page', fn ($query) => $query->where('slug', $slug))->firstOrFail();
    }

    public function test_every_service_without_a_photo_gets_a_cover(): void
    {
        $this->seed(ProductionContentSeeder::class);

        $this->artisan('media:service-covers')->assertSuccessful();

        foreach (array_keys(GenerateServiceCovers::COVERS) as $slug) {
            $service = $this->service($slug);

            $this->assertNotNull($service->featured_media_id, "{$slug} still has no image");
            $this->assertTrue(Storage::disk('public')->exists($service->featuredMedia->path));
        }
    }

    public function test_a_cover_is_an_illustration_that_never_counts_as_evidence(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->artisan('media:service-covers');

        $cover = $this->service('pool-cleaning')->featuredMedia;

        $this->assertSame(MediaType::Illustration, $cover->media_type);
        $this->assertFalse($cover->media_type->isEvidence());
        $this->assertSame(MediaStatus::Ready, $cover->status);
        $this->assertNotEmpty($cover->alt_text);
        $this->assertStringContainsString('ليس صورة فوتوغرافية', (string) $cover->verified_description);
        $this->assertSame(GenerateServiceCovers::WIDTH, $cover->width);
    }

    public function test_a_cover_can_never_be_attached_to_a_project_as_proof(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->artisan('media:service-covers');
        $cover = $this->service('pool-cleaning')->featuredMedia;

        $selectable = Media::query()->excludingLibraryStock()->pluck('id');

        $this->assertNotContains($cover->id, $selectable, 'a drawing must stay out of the project picker');
    }

    public function test_it_never_replaces_a_photograph_an_editor_chose(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $photo = Media::factory()->create();
        $this->service('pool-cleaning')->update(['featured_media_id' => $photo->id]);

        $this->artisan('media:service-covers');

        $this->assertSame($photo->id, $this->service('pool-cleaning')->fresh()->featured_media_id);
    }

    public function test_running_it_twice_adds_nothing(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->artisan('media:service-covers');
        $before = Media::query()->count();

        $this->artisan('media:service-covers');

        $this->assertSame($before, Media::query()->count());
    }

    public function test_a_page_publishes_behind_a_cover_but_never_behind_a_placeholder(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->artisan('media:service-covers');

        $page = Page::query()->where('slug', 'pool-cleaning')->firstOrFail();
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص الخدمة.</p>'], 'position' => 1]);

        $this->assertTrue(app(PublishingGate::class)->evaluate($page->fresh())->canPublish());

        // The project's older rule still holds: a temporary placeholder
        // hero blocks publishing, and only the new Illustration type is
        // allowed through.
        $placeholder = Media::factory()->placeholder()->create();
        $page->pageable->update(['featured_media_id' => $placeholder->id]);

        $this->assertFalse(app(PublishingGate::class)->evaluate($page->fresh())->canPublish());
    }

    public function test_the_cover_is_rendered_on_the_public_page(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->artisan('media:service-covers');

        $page = Page::query()->where('slug', 'pool-cleaning')->firstOrFail();
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص الخدمة.</p>'], 'position' => 1]);
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        $html = $this->get('/services/pool-cleaning')->assertOk()->getContent();

        $this->assertStringContainsString('media/covers/pool-cleaning-cover.webp', $html);
        $this->assertStringContainsString($this->service('pool-cleaning')->featuredMedia->alt_text, $html);
    }

    public function test_an_editor_can_swap_the_cover_for_a_real_photo(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->artisan('media:service-covers');
        $service = $this->service('pool-cleaning');
        $photo = Media::factory()->create(['alt_text' => 'صورة حقيقية من موقع العمل']);

        // What the panel does: point the service at another media row.
        $service->update(['featured_media_id' => $photo->id]);

        $page = Page::query()->where('slug', 'pool-cleaning')->firstOrFail();
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص الخدمة.</p>'], 'position' => 1]);
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        $html = $this->get('/services/pool-cleaning')->assertOk()->getContent();

        $this->assertStringContainsString($photo->path, $html);
        $this->assertStringNotContainsString('media/covers/pool-cleaning-cover.webp', $html, 'the cover is gone once a photo replaces it');
    }

    public function test_the_page_type_and_slug_of_every_covered_service_exist(): void
    {
        $this->seed(ProductionContentSeeder::class);

        foreach (array_keys(GenerateServiceCovers::COVERS) as $slug) {
            $this->assertDatabaseHas('pages', ['slug' => $slug, 'type' => PageType::Service->value]);
        }
    }
}
