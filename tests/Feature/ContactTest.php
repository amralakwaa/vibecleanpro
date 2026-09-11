<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_renders(): void
    {
        $this->get('/contact')->assertOk();
    }

    public function test_only_real_business_profile_fields_are_shown_never_invented_ones(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('+966500000000');
        // No email/whatsapp/address were set - none of that markup exists.
        $response->assertDontSee('mailto:');
        $response->assertDontSee('wa.me', false);
    }

    public function test_a_valid_submission_creates_a_lead_with_the_contact_source(): void
    {
        $response = $this->post('/contact', [
            'name' => 'عبدالله',
            'phone' => '0500000001',
            'message' => 'استفسار عام.',
        ]);

        $response->assertRedirect(route('public.contact'));
        $this->assertDatabaseHas('leads', [
            'name' => 'عبدالله',
            'source' => 'contact_form',
        ]);
    }

    public function test_an_invalid_submission_is_rejected(): void
    {
        $response = $this->post('/contact', ['name' => '']);

        $response->assertSessionHasErrors(['name', 'phone']);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_a_filled_honeypot_creates_no_lead(): void
    {
        $this->post('/contact', ['name' => 'bot', 'phone' => '0500000002', 'website_url' => 'spam']);

        $this->assertSame(0, Lead::query()->count());
    }
}
