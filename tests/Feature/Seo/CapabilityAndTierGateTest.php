<?php

namespace Tests\Feature\Seo;

use App\Enums\AreaTier;
use App\Enums\PageStatus;
use App\Enums\ServiceCapability;
use App\Seo\PublishingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class CapabilityAndTierGateTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private function check($page, string $key): ?object
    {
        return collect(app(PublishingGate::class)->evaluate($page->fresh())->checks)->firstWhere('key', $key);
    }

    public function test_a_service_awaiting_capability_confirmation_cannot_be_published(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->pageable->update(['capability_status' => ServiceCapability::NeedsConfirmation]);

        $page->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
        $this->assertSame('error', $this->check($page, 'service_capability')->severity->value);
    }

    public function test_an_available_service_passes(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);

        $this->assertSame('pass', $this->check($page, 'service_capability')->severity->value);
    }

    public function test_moving_an_area_to_tier_b_makes_its_page_noindex(): void
    {
        $page = $this->createCompliantAreaPage(status: PageStatus::Draft);
        $this->assertNotFalse($page->fresh()->seoMetadata?->robots_index);

        $page->pageable->update(['tier' => AreaTier::B]);

        $this->assertFalse($page->fresh()->seoMetadata->robots_index);
        $this->assertSame('pass', $this->check($page, 'area_tier')->severity->value);
    }

    public function test_a_tier_b_page_cannot_be_switched_back_to_indexable_from_any_save_path(): void
    {
        $page = $this->createCompliantAreaPage(status: PageStatus::Draft);
        $page->pageable->update(['tier' => AreaTier::B]);

        $page->seoMetadata()->updateOrCreate([], ['robots_index' => true]);

        $this->assertFalse($page->fresh()->seoMetadata->robots_index);
    }

    public function test_the_gate_still_rejects_an_indexable_tier_b_page_written_around_the_models(): void
    {
        $page = $this->createCompliantAreaPage(status: PageStatus::Draft);
        $page->pageable->update(['tier' => AreaTier::B]);
        DB::table('seo_metadata')->where('page_id', $page->id)->update(['robots_index' => true]);

        $this->assertSame('error', $this->check($page, 'area_tier')->severity->value);
    }

    public function test_a_tier_c_area_is_never_published(): void
    {
        $page = $this->createCompliantAreaPage(status: PageStatus::Draft);
        $page->pageable->update(['tier' => AreaTier::C]);

        $page->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
    }

    public function test_promoting_an_area_to_tier_a_records_when(): void
    {
        $page = $this->createCompliantAreaPage(status: PageStatus::Draft);
        $area = $page->pageable;
        $area->update(['tier' => AreaTier::B]);
        $this->assertNull($area->fresh()->promoted_at);

        $area->update(['tier' => AreaTier::A, 'promotion_reason' => 'أول مشروع منشور في الحي']);

        $this->assertNotNull($area->fresh()->promoted_at);
    }
}
