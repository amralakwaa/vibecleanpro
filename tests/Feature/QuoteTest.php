<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_quote_form_renders(): void
    {
        $this->get('/quote')->assertOk();
    }

    public function test_a_valid_submission_creates_a_lead_with_the_correct_source(): void
    {
        $response = $this->post('/quote', [
            'name' => 'أحمد',
            'phone' => '0500000000',
            'message' => 'أحتاج تنظيف سجاد.',
        ]);

        $response->assertRedirect(route('public.quote'));
        $this->assertDatabaseHas('leads', [
            'name' => 'أحمد',
            'phone' => '0500000000',
            'source' => 'quote_form',
        ]);
    }

    public function test_the_service_and_area_relation_is_stored_when_provided(): void
    {
        $service = Service::factory()->create();
        $area = Area::factory()->create();

        $this->post('/quote', [
            'name' => 'سارة',
            'phone' => '0511111111',
            'service_id' => $service->id,
            'area_id' => $area->id,
        ]);

        $this->assertDatabaseHas('leads', [
            'name' => 'سارة',
            'service_id' => $service->id,
            'area_id' => $area->id,
        ]);
    }

    public function test_an_invalid_submission_is_rejected_and_creates_no_lead(): void
    {
        $response = $this->post('/quote', ['name' => '', 'phone' => '']);

        $response->assertSessionHasErrors(['name', 'phone']);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_a_service_id_that_does_not_exist_is_rejected(): void
    {
        $response = $this->post('/quote', ['name' => 'خالد', 'phone' => '0522222222', 'service_id' => 999999]);

        $response->assertSessionHasErrors('service_id');
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_utm_parameters_captured_earlier_in_the_session_are_preserved_on_submission(): void
    {
        // Simulates a visitor landing on a UTM-tagged page, then
        // navigating to /quote before submitting - see
        // CaptureLeadAttribution.
        $this->get('/?utm_source=google&utm_medium=cpc&utm_campaign=summer');

        $this->post('/quote', ['name' => 'منى', 'phone' => '0533333333']);

        $this->assertDatabaseHas('leads', [
            'name' => 'منى',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer',
            'landing_page' => '/',
        ]);
    }

    public function test_a_filled_honeypot_field_silently_pretends_success_without_creating_a_lead(): void
    {
        $response = $this->post('/quote', [
            'name' => 'bot',
            'phone' => '0544444444',
            'website_url' => 'http://spam.example',
        ]);

        $response->assertRedirect(route('public.quote'));
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_the_success_state_is_shown_after_a_valid_submission_with_whatsapp_and_call_ctas(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000', 'phone' => '+966500000000']);

        $this->post('/quote', ['name' => 'فهد', 'phone' => '0555555555']);
        $response = $this->get('/quote');

        $response->assertOk();
        $response->assertSee('تم استلام طلبك بنجاح');
        $response->assertSee('wa.me', false);
    }

    public function test_mass_assignment_cannot_set_a_leads_status_or_id_from_the_public_form(): void
    {
        $this->post('/quote', [
            'name' => 'مهاجم',
            'phone' => '0566666666',
            'status' => 'won',
            'id' => 999,
        ]);

        $lead = Lead::query()->where('name', 'مهاجم')->firstOrFail();

        $this->assertSame('new', $lead->status->value);
        $this->assertNotSame(999, $lead->id);
    }

    public function test_the_submit_route_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/quote', ['name' => "user-{$i}", 'phone' => '0500000000'])->assertRedirect();
        }

        $this->post('/quote', ['name' => 'one-too-many', 'phone' => '0500000000'])
            ->assertStatus(429);
    }
}
