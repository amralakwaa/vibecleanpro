<?php

namespace Tests\Feature;

use App\Enums\AreaTier;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
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

    public function test_a_served_area_without_a_published_page_is_listed_but_never_linked(): void
    {
        $group = AreaGroup::factory()->create();
        $area = Area::factory()->create(['area_group_id' => $group->id, 'name' => 'حي-بدون-صفحة-منشورة', 'slug' => 'no-page-area']);
        $draft = Page::factory()->create(['type' => PageType::Area, 'slug' => 'no-page-area', 'status' => PageStatus::Draft]);
        $area->page()->save($draft);

        $html = $this->get('/areas')->getContent();

        $this->assertStringContainsString('حي-بدون-صفحة-منشورة', $html);
        $this->assertStringNotContainsString('/areas/no-page-area', $html);
    }

    public function test_tier_c_areas_are_outside_the_coverage_list(): void
    {
        $group = AreaGroup::factory()->create();
        Area::factory()->create(['area_group_id' => $group->id, 'name' => 'حي-خارج-التغطية', 'tier' => AreaTier::C]);

        $this->get('/areas')->assertOk()->assertDontSee('حي-خارج-التغطية');
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
        $response->assertSee('دليل المناطق قيد الإعداد');
        $response->assertDontSee('منشورة');
    }

    public function test_group_names_are_headings_and_never_links(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'مجموعة-عنوان-فقط']);
        $this->publishedArea('heading-only-area', $group);

        $html = $this->get('/areas')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<h2[^>]*>مجموعة-عنوان-فقط<\/h2>/u', $html);
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*>\s*مجموعة-عنوان-فقط/u', $html);
    }

    public function test_ungrouped_areas_sit_under_a_neutral_bucket_only_beside_real_groups(): void
    {
        $this->publishedArea('lonely-ungrouped');

        $alone = $this->get('/areas')->assertOk()->getContent();
        $this->assertStringContainsString('lonely-ungrouped', $alone);
        $this->assertStringNotContainsString('مناطق أخرى', $alone);

        $group = AreaGroup::factory()->create(['name' => 'مجموعة-حقيقية', 'sort_order' => 0]);
        $this->publishedArea('grouped-area', $group);

        $mixed = $this->get('/areas')->assertOk()->getContent();
        $this->assertStringContainsString('مناطق أخرى', $mixed);
        $this->assertLessThan(mb_strpos($mixed, 'مناطق أخرى'), mb_strpos($mixed, 'مجموعة-حقيقية'));
        $this->assertLessThan(mb_strpos($mixed, 'lonely-ungrouped'), mb_strpos($mixed, 'مناطق أخرى'));
    }

    public function test_service_counts_are_real_and_absent_when_zero(): void
    {
        $group = AreaGroup::factory()->create();
        $two = $this->publishedArea('two-services-area', $group);
        $two->services()->attach(Service::factory()->count(2)->create());
        $this->publishedArea('zero-services-area', $group);

        $html = $this->get('/areas')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'خدمتان'));
        $this->assertStringNotContainsString('خدمة واحدة', $html);
        $this->assertStringNotContainsString('0 خدمات', $html);
    }

    public function test_the_index_triggers_no_lazy_loading(): void
    {
        $group = AreaGroup::factory()->create();
        $this->publishedArea('lazy-grouped', $group)->services()->attach(Service::factory()->create());
        $this->publishedArea('lazy-ungrouped');

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/areas')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    private function publishedArea(string $slug, ?AreaGroup $group = null): Area
    {
        $area = Area::factory()->create(['name' => $slug, 'area_group_id' => $group?->id]);
        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => $slug]);
        $area->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        return $area;
    }
}
