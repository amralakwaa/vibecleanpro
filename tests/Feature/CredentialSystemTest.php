<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\CredentialType;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Credential;
use App\Models\Page;
use Database\Seeders\CredentialSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CredentialSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_internal_standards_public_and_the_external_roadmap_hidden(): void
    {
        $this->seed(CredentialSeeder::class);

        $this->assertSame(10, Credential::query()->internalStandards()->count());
        $this->assertSame(10, Credential::query()->public()->count(), 'only the 10 internal standards are public');

        // Every external roadmap row (ISO + Saudi licences) is planned/pending
        // and never public.
        $this->assertSame(0, Credential::query()->public()
            ->whereIn('status', [CredentialStatus::Planned->value, CredentialStatus::Pending->value])
            ->count());
        $this->assertGreaterThanOrEqual(4, Credential::query()
            ->where('status', CredentialStatus::Planned->value)->count());
    }

    public function test_scope_public_excludes_every_non_public_status(): void
    {
        foreach ([CredentialStatus::Pending, CredentialStatus::Planned, CredentialStatus::Expired, CredentialStatus::Suspended, CredentialStatus::Hidden] as $status) {
            Credential::query()->create([
                'name_ar' => 'اختبار', 'slug' => 'test-'.$status->value,
                'credential_type' => CredentialType::Certification, 'status' => $status,
                'is_public' => true,
            ]);
        }

        $this->assertSame(0, Credential::query()->public()->count(), 'no non-public status may leak even with is_public on');
    }

    public function test_an_internal_standard_is_not_public_until_both_status_and_flag_allow_it(): void
    {
        $credential = Credential::query()->create([
            'name_ar' => 'معيار', 'slug' => 'std', 'document_code' => 'VCP-TST-001',
            'credential_type' => CredentialType::InternalStandard, 'status' => CredentialStatus::Active,
            'is_internal' => true, 'is_public' => false,
        ]);

        $this->assertFalse($credential->isPubliclyVisible(), 'is_public off keeps it hidden');

        $credential->update(['is_public' => true]);
        $this->assertTrue($credential->fresh()->isPubliclyVisible());
    }

    public function test_the_verify_route_shows_a_public_standard_and_hides_the_roadmap(): void
    {
        $this->seed(CredentialSeeder::class);

        $this->get('/trust/verify/VCP-QMS-001')
            ->assertOk()
            ->assertSee('معيار إدارة الجودة')
            ->assertSee('معيار تشغيلي داخلي');

        // A planned external credential has no public document and must 404.
        $this->get('/trust/verify/ISO-9001')->assertNotFound();
        $this->get('/trust/verify/DOES-NOT-EXIST')->assertNotFound();
    }

    public function test_a_hidden_standard_cannot_be_verified(): void
    {
        Credential::query()->create([
            'name_ar' => 'مخفي', 'slug' => 'hidden-std', 'document_code' => 'VCP-HID-001',
            'credential_type' => CredentialType::InternalStandard, 'status' => CredentialStatus::Active,
            'is_internal' => true, 'is_public' => false,
        ]);

        $this->get('/trust/verify/VCP-HID-001')->assertNotFound();
    }

    public function test_document_codes_are_unique(): void
    {
        Credential::query()->create([
            'name_ar' => 'أول', 'slug' => 'a', 'document_code' => 'VCP-DUP-001',
            'credential_type' => CredentialType::InternalStandard, 'status' => CredentialStatus::Active,
        ]);

        $this->expectException(QueryException::class);

        Credential::query()->create([
            'name_ar' => 'ثانٍ', 'slug' => 'b', 'document_code' => 'VCP-DUP-001',
            'credential_type' => CredentialType::InternalStandard, 'status' => CredentialStatus::Active,
        ]);
    }

    public function test_the_trust_hub_lists_the_internal_standards_with_verify_links(): void
    {
        $this->seed(CredentialSeeder::class);

        $html = Blade::render(
            '<x-public.trust-hub :policies="[]" :titles="$titles" />',
            ['titles' => collect()],
        );

        $this->assertStringContainsString('معايير فايب كلين برو الداخلية', $html);
        $this->assertStringContainsString('/trust/verify/VCP-QMS-001', $html);
        // The admin-only roadmap never appears in a public view.
        $this->assertStringNotContainsString('ISO 9001', $html);
    }

    public function test_expiring_soon_detection(): void
    {
        $soon = Credential::query()->create([
            'name_ar' => 'قريب', 'slug' => 'soon', 'credential_type' => CredentialType::License,
            'status' => CredentialStatus::Verified, 'expires_at' => now()->addDays(20),
        ]);
        $far = Credential::query()->create([
            'name_ar' => 'بعيد', 'slug' => 'far', 'credential_type' => CredentialType::License,
            'status' => CredentialStatus::Verified, 'expires_at' => now()->addMonths(6),
        ]);

        $this->assertTrue($soon->isExpiringSoon());
        $this->assertFalse($far->isExpiringSoon());
    }

    public function test_running_the_seeder_twice_creates_no_duplicates(): void
    {
        $this->seed(CredentialSeeder::class);
        $count = Credential::query()->count();

        $this->seed(CredentialSeeder::class);

        $this->assertSame($count, Credential::query()->count());
    }

    /**
     * The Trust hub page needs to exist and be published for the /trust route
     * to render the hub (which lists the standards).
     */
    private function seedTrustHubPage(): void
    {
        Page::factory()->create([
            'type' => PageType::Trust,
            'slug' => 'trust',
            'title' => 'مركز الثقة',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }
}
