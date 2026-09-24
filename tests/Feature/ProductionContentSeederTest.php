<?php

namespace Tests\Feature;

use App\Enums\AreaTier;
use App\Enums\MediaStatus;
use App\Enums\PageStatus;
use App\Enums\ServiceCapability;
use App\Models\Area;
use App\Models\InternalLink;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use Database\Seeders\ProductionContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionContentSeederTest extends TestCase
{
    use RefreshDatabase;

    private function libraryMedia(string $file, bool $private = false): Media
    {
        $media = Media::factory()->create(['path' => 'media/vibe/'.$file, 'original_filename' => $file]);

        if ($private) {
            $media->update(['status' => MediaStatus::Private]);
        }

        return $media;
    }

    public function test_it_loads_every_service_area_and_project_as_drafts(): void
    {
        $this->seed(ProductionContentSeeder::class);

        $this->assertSame(19, Service::query()->count());
        $this->assertSame(15, Area::query()->count());
        $this->assertSame(47, Project::query()->count());
        $this->assertSame(0, Page::query()->where('status', '!=', PageStatus::Draft->value)->count());
        $this->assertSame(0, Project::query()->whereNotNull('owner_confirmed_at')->count());
        // The owner confirmed on 2026-09-20 that the company can perform
        // every listed service, so none ships awaiting confirmation.
        $this->assertSame(19, Service::query()->where('capability_status', ServiceCapability::Available->value)->count());
        $this->assertTrue(Page::query()->where('slug', 'privacy')->exists());
    }

    public function test_tiers_decide_the_page_and_its_content(): void
    {
        $this->seed(ProductionContentSeeder::class);

        // Every approved area now ships with authored content: the JSON
        // carries its blocks, FAQs and meta, and the seeder writes them as-is.
        // Tier B pages start indexable — actual indexing is gated by page
        // status (Draft) and the PublishingGate, not the tier itself.
        $tierB = Area::query()->where('slug', 'hittin')->first();
        $this->assertSame(AreaTier::B, $tierB->tier);
        $this->assertTrue($tierB->page->seoMetadata->robots_index);
        $this->assertSame(7, $tierB->page->contentBlocks()->count());
        $this->assertSame(3, $tierB->page->faqs()->count());

        // A Tier A area applies its custom meta alongside its blocks.
        $tierA = Area::query()->where('slug', 'al-olaya')->first();
        $this->assertTrue($tierA->page->seoMetadata->robots_index);
        $this->assertSame(7, $tierA->page->contentBlocks()->count());
        $this->assertSame(3, $tierA->page->faqs()->count());
        $this->assertStringContainsString('العليا', (string) $tierA->page->seoMetadata->meta_description);

        // al-muhammadiyah, the last area to be built out, now carries its
        // own authored content rather than a generated placeholder.
        $muhammadiyah = Area::query()->where('slug', 'al-muhammadiyah')->first();
        $this->assertSame(7, $muhammadiyah->page->contentBlocks()->count());
        $this->assertSame(3, $muhammadiyah->page->faqs()->count());

        // All 15 approved areas are present; no extra areas seeded.
        $this->assertSame(15, Area::query()->count());
    }

    public function test_areas_only_list_services_confirmed_as_available(): void
    {
        $this->seed(ProductionContentSeeder::class);

        $attached = Area::query()->with('services')->get()->flatMap->services;

        $this->assertNotEmpty($attached);
        $this->assertTrue($attached->every(fn (Service $service) => $service->capability_status === ServiceCapability::Available));
    }

    public function test_written_pages_resolve_library_media_and_skip_private_photos(): void
    {
        $featured = $this->libraryMedia('vibe-clean-pro-deep-cleaning-villa-stage-001.webp');
        $inline = $this->libraryMedia('vibe-clean-pro-commercial-post-construction-cleaning-riyadh-001.webp');
        $held = $this->libraryMedia('vibe-clean-pro-balcony-floor-cleaning-stage-001.webp', private: true);

        $this->seed(ProductionContentSeeder::class);

        $villa = Page::query()->where('slug', 'villa-cleaning')->first();
        $this->assertSame($featured->id, $villa->pageable->featured_media_id);
        $this->assertSame((string) $inline->id, $villa->contentBlocks()->where('type', 'image')->first()->data['media_id']);
        $this->assertSame(10, $villa->faqs()->count());
        $this->assertGreaterThan(0, InternalLink::query()->where('from_page_id', $villa->id)->count());

        $this->assertFalse(Project::query()->where('source_ref', 'CAND-20260507-MODERN-VILLA')->first()->media()->whereKey($held->id)->exists());
    }

    public function test_running_twice_changes_nothing_and_keeps_editor_changes(): void
    {
        $this->seed(ProductionContentSeeder::class);

        $villa = Page::query()->where('slug', 'villa-cleaning')->first();
        $villa->update(['title' => 'عنوان عدّله المحرر']);
        $counts = [Service::query()->count(), Area::query()->count(), Project::query()->count(), Page::query()->count()];

        $this->seed(ProductionContentSeeder::class);

        $this->assertSame($counts, [Service::query()->count(), Area::query()->count(), Project::query()->count(), Page::query()->count()]);
        $this->assertSame('عنوان عدّله المحرر', $villa->fresh()->title);
        $this->assertSame(10, $villa->faqs()->count());
    }
}
