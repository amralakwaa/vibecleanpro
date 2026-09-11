<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class ServicesIndexTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_only_services_with_a_published_page_are_listed(): void
    {
        $this->createCompliantServicePage(slug: 'listed-service');

        // A Service with no Page at all, and one whose Page is a Draft -
        // neither should ever appear.
        Service::factory()->create(['name' => 'pageless-service']);
        $draftPage = $this->createCompliantServicePage(slug: 'draft-service', status: PageStatus::Draft);

        $html = $this->get('/services')->getContent();

        $this->assertStringContainsString('listed-service', $html);
        $this->assertStringNotContainsString('pageless-service', $html);
        $this->assertStringNotContainsString($draftPage->pageable->name, $html);
    }

    public function test_a_review_status_service_is_not_listed(): void
    {
        $this->createCompliantServicePage(slug: 'review-service', status: PageStatus::Review);

        $this->get('/services')->assertDontSee('review-service');
    }

    public function test_the_index_shows_an_empty_state_when_no_services_are_published(): void
    {
        $response = $this->get('/services');

        $response->assertOk();
        $response->assertSee('لا توجد خدمات منشورة حاليًا');
    }

    public function test_the_index_page_returns_200_and_declares_arabic_rtl(): void
    {
        $this->createCompliantServicePage();

        $response = $this->get('/services');

        $response->assertOk();
        $this->assertMatchesRegularExpression('#<html[^>]*lang="ar"[^>]*dir="rtl"#', $response->getContent());
    }

    public function test_the_index_never_invents_a_price(): void
    {
        $this->createCompliantServicePage();

        $html = $this->get('/services')->getContent();

        $this->assertStringNotContainsString('ريال', $html);
    }
}
