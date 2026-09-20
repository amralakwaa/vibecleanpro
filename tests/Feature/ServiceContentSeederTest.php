<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\ContentBlock;
use App\Models\InternalLink;
use App\Models\Media;
use App\Models\Page;
use Database\Seeders\ProductionContentSeeder;
use Database\Seeders\ServiceContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceContentSeederTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $slug): Page
    {
        return Page::query()->where('slug', $slug)->firstOrFail();
    }

    public function test_it_writes_the_wave_one_pages_without_publishing_them(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(ServiceContentSeeder::class);

        foreach (['ac-cleaning', 'water-tank-cleaning', 'disinfection', 'sofa-cleaning', 'carpet-cleaning'] as $slug) {
            $page = $this->page($slug);

            $this->assertGreaterThan(0, $page->contentBlocks()->count(), "{$slug} has no content");
            $this->assertGreaterThan(0, $page->faqs()->count(), "{$slug} has no FAQs");
            $this->assertNotNull($page->seoMetadata?->meta_description);
            $this->assertSame(PageStatus::Draft, $page->status, 'the seeder never publishes - site:launch does');
        }
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(ServiceContentSeeder::class);

        $before = [ContentBlock::query()->count(), InternalLink::query()->count()];

        $this->seed(ServiceContentSeeder::class);

        $this->assertSame($before, [ContentBlock::query()->count(), InternalLink::query()->count()]);
    }

    public function test_it_leaves_a_page_an_editor_has_already_written(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $page = $this->page('sofa-cleaning');
        $page->contentBlocks()->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص كتبه المحرر.</p>'], 'position' => 1, 'is_active' => true]);

        $this->seed(ServiceContentSeeder::class);

        $this->assertSame(1, $page->contentBlocks()->count());
        $this->assertSame('<p>نص كتبه المحرر.</p>', $page->contentBlocks()->first()->data['content']);
    }

    public function test_an_image_block_is_dropped_when_its_photo_is_not_approved(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(ServiceContentSeeder::class);

        // No media library imported here, so the image blocks in the Wave 1
        // definitions have nothing to point at - and none may be written
        // with an empty media_id or a stand-in photo.
        foreach (['ac-cleaning', 'carpet-cleaning'] as $slug) {
            $this->assertSame(0, $this->page($slug)->contentBlocks()->where('type', 'image')->count());
        }
    }

    public function test_an_image_block_is_written_when_its_photo_is_approved(): void
    {
        Media::factory()->create([
            'path' => 'media/vibe/vibe-clean-pro-office-carpet-cleaning-riyadh.webp',
            'original_filename' => 'vibe-clean-pro-office-carpet-cleaning-riyadh.webp',
        ]);

        $this->seed(ProductionContentSeeder::class);
        $this->seed(ServiceContentSeeder::class);

        $this->assertSame(1, $this->page('carpet-cleaning')->contentBlocks()->where('type', 'image')->count());
    }

    public function test_it_records_only_the_service_links_the_body_really_contains(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(ServiceContentSeeder::class);

        $from = $this->page('home-cleaning');

        // Every service the body links to is recorded (a pair the approved
        // architecture already planned keeps its own context).
        foreach (['ac-cleaning', 'water-tank-cleaning', 'sofa-cleaning', 'carpet-cleaning', 'disinfection'] as $slug) {
            $this->assertDatabaseHas('internal_links', [
                'from_page_id' => $from->id,
                'to_page_id' => $this->page($slug)->id,
            ]);
        }

        // And at least one row exists that only the written body justifies.
        $this->assertGreaterThan(0, InternalLink::query()->where('context', 'body_link')->count());

        // water-tank-cleaning's body links to no other service, so it must
        // not have acquired outgoing body links.
        $this->assertSame(0, InternalLink::query()
            ->where('from_page_id', $this->page('water-tank-cleaning')->id)
            ->where('context', 'body_link')
            ->count());
    }

    public function test_appended_sections_are_added_once_and_only_to_written_pages(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(ServiceContentSeeder::class);

        $blocks = $this->page('office-cleaning')->contentBlocks()->get()
            ->filter(fn (ContentBlock $block) => ($block->data['section'] ?? null) === 'complementary-services');

        $this->assertCount(1, $blocks);
    }
}
