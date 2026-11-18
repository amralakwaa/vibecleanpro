<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * One public address per page: a capitalised slug 301s to its lowercase
 * canonical, while deeper case-significant paths are left alone.
 */
class CanonicalPathRedirectTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_capitalised_service_slug_redirects_to_the_lowercase_url(): void
    {
        $this->createCompliantServicePage(slug: 'home-cleaning');

        $this->get('/services/Home-Cleaning?utm=1')->assertRedirect(rtrim(config('app.url'), '/').'/services/home-cleaning?utm=1')->assertStatus(301);
        $this->get('/services/home-cleaning')->assertOk();
    }

    public function test_paths_deeper_than_two_segments_keep_their_case(): void
    {
        $response = $this->get('/trust/verify/VCP-CCR-001');

        $this->assertNotSame(301, $response->getStatusCode());
    }

    public function test_the_contact_page_carries_the_business_node_it_refers_to(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو', 'city' => 'الرياض']);

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"ContactPage"', $html);
        $this->assertMatchesRegularExpression('/"@id":"[^"]+#business","name":"Vibe Clean Pro"/', $html);
    }
}
