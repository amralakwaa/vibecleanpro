<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * A case study's CreativeWork is the page's main entity and points at the
 * Service nodes the service pages already declare, instead of floating
 * beside the WebPage with anonymous service stubs.
 */
class ProjectSchemaGraphTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_project_is_the_main_entity_and_references_its_service_node(): void
    {
        $service = $this->createCompliantServicePage(slug: 'facade-cleaning');
        $project = Project::factory()->create(['owner_confirmed_at' => now()]);
        $project->services()->attach($service->pageable_id);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'radisson-facade', 'title' => 'تنظيف واجهة فندق', 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نطاق العمل.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        $html = $this->get('/projects/radisson-facade')->assertOk()->getContent();
        $projectId = url('/projects/radisson-facade').'#project';

        $this->assertStringContainsString('"mainEntity":{"@id":"'.$projectId.'"}', $html);
        $this->assertStringContainsString('"@type":"CreativeWork","@id":"'.$projectId.'"', $html);
        $this->assertStringContainsString('"@id":"'.url('/services/facade-cleaning').'#service"', $html);
        $this->assertStringContainsString('"creator":{"@id":', $html);
    }
}
