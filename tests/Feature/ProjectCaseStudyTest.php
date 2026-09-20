<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCaseStudyTest extends TestCase
{
    use RefreshDatabase;

    private function publishedProject(array $attributes = []): Project
    {
        $project = Project::factory()->create(['title' => 'تنظيف مكاتب — الرياض', ...$attributes]);
        $project->services()->attach(Service::factory()->create(['name' => 'تنظيف المكاتب والشركات'])->id);
        $project->media()->attach(Media::factory()->create()->id, ['stage' => 'after', 'sort_order' => 1]);

        $page = Page::factory()->create(['type' => PageType::Project, 'title' => $project->title, 'slug' => 'office-cleaning-case-01', 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>أعمال نفّذها الفريق.</p>'], 'position' => 1]);
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        return $project->fresh();
    }

    public function test_a_project_without_a_case_study_shows_no_empty_sections(): void
    {
        $this->publishedProject();

        $html = $this->get('/projects/office-cleaning-case-01')->assertOk()->getContent();

        // Targets the case-study section itself: the words "النتيجة" and
        // "قبل" also label the evidence gallery, which is a different part
        // of the page and must keep working.
        $this->assertStringNotContainsString('project-case-study', $html);
        $this->assertStringNotContainsString('تفاصيل التنفيذ', $html);
        $this->assertStringNotContainsString('غير متوفر', $html, 'an empty field is silence, never a placeholder');
        $this->assertStringNotContainsString('لا يوجد', $html);
    }

    public function test_each_case_study_section_appears_only_when_it_holds_something_real(): void
    {
        $project = $this->publishedProject(['challenge' => 'المكتب يحتاج تنظيفًا دون تعطيل الدوام.']);

        $html = $this->get('/projects/office-cleaning-case-01')->getContent();
        $this->assertStringContainsString('تفاصيل التنفيذ', $html);
        $this->assertStringContainsString('المكتب يحتاج تنظيفًا دون تعطيل الدوام.', $html);
        $this->assertStringNotContainsString('حالة الموقع قبل التنفيذ', $html, 'unwritten sections stay hidden');
        $this->assertStringNotContainsString('خطوات التنفيذ', $html);

        $project->update([
            'site_condition' => 'غبار متراكم على القواطع الزجاجية.',
            'execution_steps' => [['title' => 'معاينة', 'description' => 'تحديد النطاق مع المسؤول.'], ['title' => 'التنفيذ خارج الدوام']],
            'outcome' => 'سُلّم المكتب جاهزًا قبل بداية الدوام.',
        ]);

        $html = $this->get('/projects/office-cleaning-case-01')->getContent();
        foreach (['حالة الموقع قبل التنفيذ', 'غبار متراكم', 'خطوات التنفيذ', 'معاينة', 'التنفيذ خارج الدوام', 'النتيجة', 'سُلّم المكتب جاهزًا'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    public function test_a_step_without_a_title_is_skipped_rather_than_rendered_blank(): void
    {
        $this->publishedProject(['execution_steps' => [['title' => 'معاينة', 'description' => 'وصف'], ['title' => '', 'description' => 'يتيم']]]);

        $html = $this->get('/projects/office-cleaning-case-01')->getContent();

        $this->assertStringContainsString('معاينة', $html);
        $this->assertStringNotContainsString('يتيم', $html);
    }

    public function test_an_unconfirmed_project_still_cannot_be_published_whatever_its_case_study(): void
    {
        $project = Project::factory()->unconfirmed()->create(['challenge' => 'مشكلة', 'outcome' => 'نتيجة']);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'unconfirmed-case', 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص</p>'], 'position' => 1]);

        $page->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
    }

    public function test_the_case_study_fields_are_editable_from_the_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $this->actingAs($user);
        $project = $this->publishedProject();

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertOk()
            ->assertFormFieldExists('challenge')
            ->assertFormFieldExists('site_condition')
            ->assertFormFieldExists('execution_steps')
            ->assertFormFieldExists('outcome')
            ->fillForm(['outcome' => 'نتيجة كتبها المالك'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('نتيجة كتبها المالك', $project->fresh()->outcome);
    }

    public function test_no_real_project_carries_invented_case_study_text(): void
    {
        // Guards the rule that these fields are written by a human only:
        // no seeder or command may fill them.
        $this->artisan('db:seed', ['--class' => 'ProductionContentSeeder', '--no-interaction' => true]);

        $this->assertSame(0, Project::query()->get()->filter(fn (Project $project) => $project->hasCaseStudy())->count());
    }
}
