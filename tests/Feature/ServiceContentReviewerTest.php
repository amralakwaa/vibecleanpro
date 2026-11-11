<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class ServiceContentReviewerTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_service_page_names_its_content_reviewer_in_the_page_and_its_structured_data(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'content_reviewer_name' => 'اسم-المراجعة',
            'content_reviewer_title' => 'صفة-المراجعة',
        ]);
        $this->createCompliantServicePage(slug: 'reviewed-service');

        $response = $this->get('/services/reviewed-service');

        $response->assertOk();
        $response->assertSee('مراجعة المحتوى الفني');
        $response->assertSee('صفة-المراجعة اسم-المراجعة');
        $response->assertSee('"reviewedBy":{"@type":"Person","name":"اسم-المراجعة","jobTitle":"صفة-المراجعة"}', false);
        $response->assertSee('"lastReviewed"', false);
    }

    public function test_the_reviewer_line_and_data_are_absent_while_no_reviewer_is_set(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $this->createCompliantServicePage(slug: 'unreviewed-service');

        $response = $this->get('/services/unreviewed-service');

        $response->assertOk();
        $response->assertDontSee('مراجعة المحتوى الفني');
        $response->assertDontSee('reviewedBy', false);
    }
}
