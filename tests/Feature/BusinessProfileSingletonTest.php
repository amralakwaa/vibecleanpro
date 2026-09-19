<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageBusinessProfile;
use App\Models\BusinessProfile;
use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessProfileSingletonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $this->actingAs($user);
    }

    private function profileWithId(int $id): BusinessProfile
    {
        $profile = (new BusinessProfile)->forceFill(['id' => $id, 'name' => 'Vibe Clean Pro', 'phone' => '+966534999194', 'city' => 'الرياض']);
        $profile->save();

        return $profile;
    }

    public function test_it_edits_the_existing_profile_whatever_its_id(): void
    {
        $profile = $this->profileWithId(7);

        Livewire::test(ManageBusinessProfile::class)
            ->assertFormSet(['name' => 'Vibe Clean Pro'])
            ->fillForm(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', 'email' => 'info@vibecleanpro.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, BusinessProfile::query()->count());
        $this->assertSame(7, BusinessProfile::query()->value('id'));
        $this->assertSame('info@vibecleanpro.com', $profile->fresh()->email);
    }

    public function test_a_fresh_database_gets_exactly_one_profile(): void
    {
        $this->assertSame(0, BusinessProfile::query()->count());

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', 'email' => 'info@vibecleanpro.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'Vibe Clean Pro', 'city' => 'الرياض'])
            ->call('save');

        $this->assertSame(1, BusinessProfile::query()->count());
    }

    public function test_reopening_the_screen_shows_the_saved_values(): void
    {
        $this->profileWithId(4);

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', 'service_area' => 'مدينة الرياض', 'working_hours' => ['كل أيام الأسبوع' => 'من 08:00 إلى 14:00']])
            ->call('save');

        Livewire::test(ManageBusinessProfile::class)->assertFormSet([
            'service_area' => 'مدينة الرياض',
            'working_hours' => ['كل أيام الأسبوع' => 'من 08:00 إلى 14:00'],
        ]);
    }

    public function test_duplicate_profiles_stop_saving_instead_of_guessing(): void
    {
        $first = $this->profileWithId(3);
        $second = $this->profileWithId(9);

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'اسم جديد', 'city' => 'الرياض'])
            ->call('save')
            ->assertNotified();

        $this->assertSame('Vibe Clean Pro', $first->fresh()->name, 'the older row is untouched');
        $this->assertSame('Vibe Clean Pro', $second->fresh()->name, 'the newer row is untouched');
        $this->assertSame(2, BusinessProfile::query()->count(), 'nothing is merged or deleted automatically');
    }

    public function test_the_seeder_fills_the_confirmed_values_and_leaves_the_notification_inbox_alone(): void
    {
        $this->profileWithId(5);

        $this->seed(CompanyProfileSeeder::class);

        $profile = BusinessProfile::query()->sole();
        $this->assertSame('info@vibecleanpro.com', $profile->email);
        $this->assertSame('مدينة الرياض', $profile->service_area);
        $this->assertSame(['كل أيام الأسبوع' => 'من 08:00 إلى 14:00'], $profile->working_hours);
        $this->assertNull($profile->lead_notification_email, 'the internal inbox is the owner\'s decision');
    }

    public function test_the_seeder_never_overwrites_a_value_the_owner_changed(): void
    {
        $this->profileWithId(2)->update(['email' => 'hello@vibecleanpro.com', 'service_area' => 'الرياض وضواحيها']);

        $this->seed(CompanyProfileSeeder::class);

        $profile = BusinessProfile::query()->sole();
        $this->assertSame('hello@vibecleanpro.com', $profile->email);
        $this->assertSame('الرياض وضواحيها', $profile->service_area);
    }
}
