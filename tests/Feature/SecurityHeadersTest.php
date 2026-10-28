<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_carry_the_baseline_security_headers(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);

        $response = $this->get('https://localhost/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_hsts_is_not_sent_over_plain_http_outside_production(): void
    {
        $this->get('http://localhost/robots.txt')->assertHeaderMissing('Strict-Transport-Security');
    }
}
