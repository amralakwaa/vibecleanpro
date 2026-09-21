<?php

namespace Tests\Feature;

use App\Enums\LocationConfidence;
use App\Enums\LocationStatus;
use App\Models\Area;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectLocationDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovers_location_from_project_text_as_medium_confidence()
    {
        $area = Area::factory()->create(['name' => 'الياسمين']);
        $project = Project::factory()->create([
            'title' => 'تنظيف فيلا في حي الياسمين',
            'location_status' => 'draft',
            'area_id' => null,
        ]);

        $this->artisan('projects:location-discover', ['--apply-candidates' => true])
            ->assertSuccessful();

        $project->refresh();
        $this->assertEquals($area->id, $project->area_id);
        $this->assertEquals(LocationStatus::PendingReview, $project->location_status);
        $this->assertEquals(LocationConfidence::ClientProvided, $project->location_confidence);
    }

    public function test_discovers_location_from_media_as_strong_confidence()
    {
        $area = Area::factory()->create(['name' => 'العقيق']);
        $project = Project::factory()->create(['location_status' => 'draft']);

        $media = Media::factory()->create(['original_filename' => 'cleaning-العقيق-villa.webp']);

        DB::table('project_media')->insert([
            'project_id' => $project->id,
            'media_id' => $media->id,
            'stage' => 'during',
        ]);

        $this->artisan('projects:location-discover', ['--apply-candidates' => true])
            ->assertSuccessful();

        $project->refresh();
        $this->assertEquals($area->id, $project->area_id);
        $this->assertEquals(LocationStatus::PendingReview, $project->location_status);
        $this->assertEquals(LocationConfidence::MediaEvidence, $project->location_confidence);
    }

    public function test_ignores_shared_media_for_location_evidence()
    {
        $area = Area::factory()->create(['name' => 'النرجس']);

        $project1 = Project::factory()->create(['location_status' => 'draft']);
        $project2 = Project::factory()->create(['location_status' => 'draft']);

        $media = Media::factory()->create(['alt_text' => 'عمل في النرجس']);

        // Share media between two projects
        DB::table('project_media')->insert([
            ['project_id' => $project1->id, 'media_id' => $media->id, 'stage' => 'after'],
            ['project_id' => $project2->id, 'media_id' => $media->id, 'stage' => 'after'],
        ]);

        $this->artisan('projects:location-discover', ['--apply-candidates' => true])
            ->assertSuccessful();

        $project1->refresh();
        $project2->refresh();

        // Should ignore the shared media
        $this->assertNull($project1->area_id);
        $this->assertEquals(LocationStatus::Draft, $project1->location_status);

        $this->assertNull($project2->area_id);
        $this->assertEquals(LocationStatus::Draft, $project2->location_status);
    }

    public function test_conflict_results_in_no_update()
    {
        $area1 = Area::factory()->create(['name' => 'الملقا']);
        $area2 = Area::factory()->create(['name' => 'الصحافة']);

        $project = Project::factory()->create([
            'title' => 'تنظيف الملقا والصحافة',
            'location_status' => 'draft',
        ]);

        $this->artisan('projects:location-discover', ['--apply-candidates' => true])
            ->assertSuccessful();

        $project->refresh();

        // Conflict means we don't apply automatically
        $this->assertNull($project->area_id);
        $this->assertEquals(LocationStatus::Draft, $project->location_status);
    }

    public function test_read_only_mode_does_not_modify_database()
    {
        $area = Area::factory()->create(['name' => 'الياسمين']);
        $project = Project::factory()->create([
            'title' => 'تنظيف الياسمين',
            'location_status' => 'draft',
        ]);

        $this->artisan('projects:location-discover')
            ->assertSuccessful();

        $project->refresh();
        $this->assertNull($project->area_id);
        $this->assertEquals(LocationStatus::Draft, $project->location_status);
    }
}
