<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_route_serves_markdown_with_the_brand_header(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو', 'city' => 'الرياض']);

        $response = $this->get('/llms.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('# فايب كلين برو', false);
        $response->assertSee('## روابط رئيسية', false);
    }

    public function test_a_published_indexable_page_is_listed_with_an_absolute_url(): void
    {
        $this->createCompliantServicePage(slug: 'deep-cleaning');

        $body = $this->get('/llms.txt')->getContent();

        $this->assertStringContainsString(rtrim(config('app.url'), '/').'/services/deep-cleaning', $body);
    }

    public function test_a_draft_or_noindexed_page_is_never_offered_to_an_llm(): void
    {
        $this->makeBarePage(['status' => PageStatus::Draft, 'slug' => 'draft-service']);
        $noindex = $this->createCompliantServicePage(slug: 'hidden-service');
        $noindex->seoMetadata->update(['robots_index' => false]);

        $body = $this->get('/llms.txt')->getContent();

        $this->assertStringNotContainsString('draft-service', $body);
        $this->assertStringNotContainsString('hidden-service', $body);
    }

    public function test_it_never_lists_a_page_whose_canonical_points_elsewhere(): void
    {
        $this->createCompliantServicePage(slug: 'deep-cleaning');
        $variant = $this->createCompliantServicePage(slug: 'duplicate-variant');
        $variant->seoMetadata->update([
            'canonical_url' => rtrim(config('app.url'), '/').'/services/deep-cleaning',
        ]);

        $body = $this->get('/llms.txt')->getContent();

        $this->assertStringNotContainsString('duplicate-variant', $body);
    }
}
