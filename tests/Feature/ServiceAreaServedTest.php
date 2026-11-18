<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * A service with no district pages linked (facade materials, warehouses,
 * factories) still tells search engines where it is offered: the city.
 */
class ServiceAreaServedTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_service_without_linked_areas_is_served_across_the_city(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);
        $page = $this->createCompliantServicePage(slug: 'factory-cleaning');
        $page->pageable->areas()->detach();

        $response = $this->get('/services/factory-cleaning');

        $response->assertOk();
        $response->assertSee('"areaServed":{"@type":"City","name":"الرياض"}', false);
    }
}
