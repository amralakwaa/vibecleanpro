<?php

namespace Tests\Feature;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Models\Media;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function reviewablePhoto(): Media
    {
        return Media::factory()->pending()->create(['alt_text' => 'واجهة زجاجية بعد التنظيف', 'verified_description' => 'واجهة مبنى تجاري بعد الغسيل']);
    }

    public function test_a_content_manager_cannot_approve_a_photo(): void
    {
        $media = $this->reviewablePhoto();
        $this->actingAsRole('Content Manager');

        $this->expectException(ValidationException::class);
        $media->update(['privacy_status' => MediaPrivacyStatus::Cleared, 'status' => MediaStatus::Ready]);
    }

    public function test_a_media_manager_can_clear_and_approve_a_photo(): void
    {
        $media = $this->reviewablePhoto();
        $this->actingAsRole('Media Manager');

        $media->update(['privacy_status' => MediaPrivacyStatus::Cleared, 'status' => MediaStatus::Ready]);

        $this->assertSame(MediaStatus::Ready, $media->fresh()->status);
    }

    public function test_the_media_screens_render_with_verification_fields(): void
    {
        $this->actingAsRole('Media Manager');
        $media = $this->reviewablePhoto();

        Livewire::test(ListMedia::class)->assertOk()->assertCanSeeTableRecords([$media]);
        Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()])
            ->assertOk()
            ->assertFormFieldExists('status')
            ->assertFormFieldIsEnabled('status');
    }

    public function test_approval_fields_are_locked_for_a_content_manager(): void
    {
        $this->actingAsRole('Content Manager');
        $media = $this->reviewablePhoto();

        Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()])->assertFormFieldIsDisabled('status');
    }

    public function test_only_confirm_project_holders_can_confirm_a_project(): void
    {
        $project = Project::factory()->unconfirmed()->create();

        $this->actingAsRole('Project Manager');
        try {
            $project->update(['owner_confirmed_at' => now()]);
            $this->fail('A project manager confirmed a project on the owner\'s behalf.');
        } catch (ValidationException) {
            $this->assertNull($project->fresh()->owner_confirmed_at);
        }

        $this->actingAsRole('Administrator');
        $project->refresh()->update(['owner_confirmed_at' => now()]);
        $this->assertNotNull($project->fresh()->owner_confirmed_at);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])->assertOk();
    }

    public function test_a_testimonial_needs_a_source_and_consent_before_approval(): void
    {
        $this->actingAsRole('Administrator');
        $testimonial = Testimonial::factory()->unapproved()->create(['source' => null, 'consent_confirmed' => false]);

        $this->expectException(ValidationException::class);
        $testimonial->update(['approved_at' => now()]);
    }

    public function test_editing_an_approved_testimonial_withdraws_its_approval(): void
    {
        $testimonial = Testimonial::factory()->create();

        $testimonial->update(['content' => 'نص مختلف عمّا قاله العميل']);

        $this->assertNull($testimonial->fresh()->approved_at);
    }

    public function test_unapproved_testimonials_never_reach_the_homepage(): void
    {
        Testimonial::factory()->create(['author_name' => 'عميل معتمد']);
        Testimonial::factory()->unapproved()->create(['author_name' => 'عميل غير معتمد']);

        $this->get('/')->assertOk()->assertSee('عميل معتمد')->assertDontSee('عميل غير معتمد');
    }

    public function test_the_testimonial_screen_renders_for_an_administrator(): void
    {
        $this->actingAsRole('Administrator');

        Livewire::test(EditTestimonial::class, ['record' => Testimonial::factory()->create()->getRouteKey()])->assertOk();
    }
}
