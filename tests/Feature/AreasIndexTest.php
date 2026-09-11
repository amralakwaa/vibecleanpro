<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\ContentBlock;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class AreasIndexTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_areas_are_grouped_under_their_real_area_group(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'شمال الرياض']);
        $area = Area::factory()->create(['area_group_id' => $group->id, 'name' => 'حي الملقا']);
        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => 'al-malqa-group-test']);
        $area->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $html = $this->get('/areas')->getContent();

        $this->assertStringContainsString('شمال الرياض', $html);
        $this->assertStringContainsString('حي الملقا', $html);
    }

    public function test_an_area_with_no_published_page_is_never_linked(): void
    {
        $group = AreaGroup::factory()->create();
        Area::factory()->create(['area_group_id' => $group->id, 'name' => 'حي-بدون-صفحة-منشورة']);

        $html = $this->get('/areas')->getContent();

        $this->assertStringNotContainsString('حي-بدون-صفحة-منشورة', $html);
    }

    public function test_an_area_group_with_no_published_areas_is_not_rendered_as_an_empty_heading(): void
    {
        AreaGroup::factory()->create(['name' => 'مجموعة-فارغة-تمامًا']);

        $html = $this->get('/areas')->getContent();

        $this->assertStringNotContainsString('مجموعة-فارغة-تمامًا', $html);
    }

    public function test_the_index_shows_an_empty_state_when_no_areas_are_published(): void
    {
        $response = $this->get('/areas');

        $response->assertOk();
        $response->assertSee('لا توجد مناطق منشورة حاليًا');
    }
}
