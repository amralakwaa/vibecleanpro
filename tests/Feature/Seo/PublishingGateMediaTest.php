<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Seo\PublishingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class PublishingGateMediaTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private function mediaCheck($page): ?object
    {
        return collect(app(PublishingGate::class)->evaluate($page->fresh())->checks)->firstWhere('key', 'media_publishable');
    }

    public function test_verified_images_pass(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);

        $this->assertSame('pass', $this->mediaCheck($page)->severity->value);
        $this->assertTrue(app(PublishingGate::class)->evaluate($page->fresh())->canPublish());
    }

    public function test_a_privacy_held_main_image_blocks_publishing(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->pageable->update(['featured_media_id' => Media::factory()->private()->create()->id]);

        $this->assertSame('error', $this->mediaCheck($page)->severity->value);
        $this->assertFalse(app(PublishingGate::class)->evaluate($page->fresh())->canPublish());
    }

    public function test_an_unreviewed_image_inside_a_content_block_blocks_publishing(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $pending = Media::factory()->pending()->create();
        ContentBlock::factory()->for($page)->create(['type' => 'gallery', 'data' => ['media_ids' => [(string) $pending->id]], 'position' => 99]);

        $check = $this->mediaCheck($page);
        $this->assertSame('error', $check->severity->value);
        $this->assertStringContainsString($pending->original_filename, $check->message);
    }

    public function test_a_placeholder_can_never_be_the_main_image(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->pageable->update(['featured_media_id' => Media::factory()->placeholder()->create()->id]);

        $this->assertStringContainsString('Placeholder', $this->mediaCheck($page)->message);
    }

    public function test_a_page_that_tries_to_publish_with_a_private_image_is_returned_to_draft(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->pageable->update(['featured_media_id' => Media::factory()->private()->create()->id]);

        $page->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
    }
}
