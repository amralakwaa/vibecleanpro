<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Models\Area;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentGraphConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_slug_must_be_unique(): void
    {
        Page::factory()->create(['slug' => 'about-us']);

        $this->expectException(QueryException::class);

        Page::factory()->create(['slug' => 'about-us']);
    }

    public function test_seo_metadata_is_one_to_one_with_its_page(): void
    {
        $page = Page::factory()->create();
        SeoMetadata::factory()->for($page)->create();

        $this->expectException(QueryException::class);

        SeoMetadata::factory()->for($page)->create();
    }

    public function test_service_area_pivot_rejects_duplicate_pairs(): void
    {
        $service = Service::factory()->create();
        $area = Area::factory()->create();

        $service->areas()->attach($area->id);

        $this->expectException(QueryException::class);

        $service->areas()->attach($area->id);
    }

    public function test_service_and_area_many_to_many_relationship_is_navigable_both_ways(): void
    {
        $service = Service::factory()->create();
        $area = Area::factory()->create();

        $service->areas()->attach($area->id);

        $this->assertTrue($service->areas->contains($area));
        $this->assertTrue($area->services->contains($service));
    }

    public function test_deleting_a_service_category_detaches_it_from_services_without_deleting_them(): void
    {
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->for($category, 'category')->create();

        $category->delete();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'service_category_id' => null]);
    }

    public function test_soft_deleting_a_service_keeps_the_lead_and_its_reference(): void
    {
        $service = Service::factory()->create();
        $lead = Lead::factory()->for($service)->create();

        $service->delete();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'service_id' => $service->id]);
    }

    public function test_force_deleting_a_service_nulls_it_on_leads_but_keeps_the_lead(): void
    {
        $service = Service::factory()->create();
        $lead = Lead::factory()->for($service)->create();

        $service->forceDelete();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'service_id' => null]);
    }

    public function test_project_media_pivot_tracks_stage_and_can_be_filtered(): void
    {
        $project = Project::factory()->create();
        $before = Media::factory()->create();
        $after = Media::factory()->create();

        $project->media()->attach($before->id, ['stage' => MediaStage::Before->value, 'sort_order' => 0]);
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);

        $beforeMedia = $project->mediaByStage(MediaStage::Before)->get();

        $this->assertCount(1, $beforeMedia);
        $this->assertTrue($beforeMedia->first()->is($before));
    }
}
