<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\AreaGroup;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Every content page says who prepared it and when it last changed. Only
 * service pages name the technical reviewer, matching the reviewedBy schema.
 */
class PageBylineTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_an_area_page_shows_the_brand_and_last_update_without_claiming_a_review(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'name_ar' => 'فايب كلين برو',
            'content_reviewer_name' => 'اسم-المراجعة',
        ]);
        $page = $this->createCompliantAreaPage(slug: 'al-malaz');

        $response = $this->get('/areas/al-malaz');

        $response->assertOk();
        $response->assertSee('فريق فايب كلين برو');
        $response->assertSee('آخر تحديث: <time datetime="'.$page->updated_at->toDateString().'">', false);
        $response->assertDontSee('مراجعة المحتوى الفني');
        $response->assertDontSee('reviewedBy', false);
    }

    public function test_a_side_of_riyadh_landing_page_shows_the_brand_and_last_update(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو']);
        $group = AreaGroup::factory()->create(['name' => 'شمال الرياض', 'slug' => 'north-riyadh']);
        $page = Page::factory()->create(['type' => PageType::Landing, 'slug' => 'north-riyadh', 'published_at' => now()->subDay()]);
        $page->pageable()->associate($group);
        $page->status = PageStatus::Published;
        $page->saveQuietly();
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'أحياء شمال الرياض.']]);

        $response = $this->get('/areas/north-riyadh');

        $response->assertOk();
        $response->assertSee('فريق فايب كلين برو');
        $response->assertSee('آخر تحديث');
    }

    public function test_a_service_page_without_a_reviewer_still_shows_the_brand_and_last_update(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو']);
        $this->createCompliantServicePage(slug: 'unreviewed-service');

        $response = $this->get('/services/unreviewed-service');

        $response->assertOk();
        $response->assertSee('فريق فايب كلين برو');
        $response->assertSee('آخر تحديث');
        $response->assertDontSee('مراجعة المحتوى الفني');
    }
}
