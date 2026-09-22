<?php

namespace Tests\Feature\Seo;

use App\Enums\AreaTier;
use App\Enums\PageStatus;
use App\Enums\ServiceCapability;
use App\Seo\PublishingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_a_tier_b_service_area_can_be_indexable_if_compliant(): void
    {
        $page = $this->createCompliantAreaPage(status: PageStatus::Draft);
        $page->pageable->update(['tier' => AreaTier::B]);
        $page->seoMetadata()->updateOrCreate([], ['robots_index' => true]);

        // Should pass the gate check for area_tier now that Tier B can be indexable.
        $this->assertSame('pass', $this->check($page, 'area_tier')->severity->value);
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
