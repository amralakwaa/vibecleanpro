<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageBusinessProfile;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\BusinessProfile;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The whole point of the identity CMS: an admin changes the founder, the
 * story and the team from the panel and the public page follows - with
 * no developer in the loop. These tests drive the real Filament screens.
 */
class CompanyIdentityAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_super_admin_edits_identity_and_founder_from_the_business_profile_screen(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'));

        Livewire::test(ManageBusinessProfile::class)
            ->assertOk()
            ->assertSee(['هوية الشركة', 'المؤسس', 'الفريق', 'قصة التأسيس', 'إظهار قسم المؤسس في صفحة من نحن'])
            ->fillForm([
                'name' => 'Vibe Clean Pro',
                'city' => 'الرياض',
                'tagline' => 'وصف-من-اللوحة',
                'identity_statement' => 'هوية-من-اللوحة',
                'story' => '<p>قصة-من-اللوحة</p>',
                'values' => [['title' => 'مبدأ-من-اللوحة', 'description' => 'شرح']],
                'founder_name' => 'مؤسس-من-اللوحة',
                'founder_title' => 'صفة-من-اللوحة',
                'show_founder' => true,
                'show_team' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $profile = BusinessProfile::query()->firstOrFail();
        $this->assertSame('مؤسس-من-اللوحة', $profile->founder_name);
        $this->assertSame('هوية-من-اللوحة', $profile->identity_statement);
        $this->assertSame('مبدأ-من-اللوحة', $profile->values[0]['title']);
        $this->assertFalse($profile->show_team);
        $this->assertTrue($profile->show_founder);
    }

    public function test_a_content_manager_creates_edits_hides_and_reorders_team_members(): void
    {
        $this->actingAs($this->userWithRole('Content Manager'));

        Livewire::test(ListTeamMembers::class)->assertOk();

        Livewire::test(CreateTeamMember::class)
            ->fillForm(['name' => 'عضو-من-اللوحة', 'role_title' => 'مشرف', 'bio' => 'نبذة', 'sort_order' => 5, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $member = TeamMember::query()->where('name', 'عضو-من-اللوحة')->firstOrFail();
        $this->assertSame(5, $member->sort_order);
        $this->assertTrue($member->is_active);

        Livewire::test(EditTeamMember::class, ['record' => $member->getKey()])
            ->fillForm(['is_active' => false, 'sort_order' => 1])
            ->call('save')
            ->assertHasNoFormErrors();

        $member->refresh();
        $this->assertFalse($member->is_active);
        $this->assertSame(1, $member->sort_order);
        // Hidden, not deleted.
        $this->assertSame(1, TeamMember::query()->count());
    }

    public function test_a_team_member_needs_a_name(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'));

        Livewire::test(CreateTeamMember::class)
            ->fillForm(['name' => '', 'role_title' => 'بلا اسم'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        $this->assertSame(0, TeamMember::query()->count());
    }

    public function test_the_team_resource_is_gated_by_its_own_permission(): void
    {
        $this->actingAs($this->userWithRole('Viewer'));

        Livewire::test(CreateTeamMember::class)->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
