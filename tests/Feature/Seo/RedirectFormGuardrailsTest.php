<?php

namespace Tests\Feature\Seo;

use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Models\Redirect;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The runtime RedirectResolver trusts whatever is in the redirects table
 * (see RedirectResolverTest) - the safety checks against redirect chains
 * and unsafe open-redirect destinations live at the one place new redirect
 * rows are actually written by a human: this admin form.
 */
class RedirectFormGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);
    }

    public function test_creating_a_redirect_whose_destination_is_already_used_as_another_redirects_source_is_rejected(): void
    {
        Redirect::factory()->create(['from_path' => '/b', 'to_path' => '/c']);

        Livewire::test(CreateRedirect::class)
            ->fillForm(['from_path' => '/a', 'to_path' => '/b', 'type' => 301])
            ->call('create')
            ->assertHasFormErrors(['to_path']);

        $this->assertDatabaseMissing('redirects', ['from_path' => '/a']);
    }

    public function test_creating_a_redirect_to_an_invalid_full_url_destination_is_rejected(): void
    {
        Livewire::test(CreateRedirect::class)
            ->fillForm(['from_path' => '/a', 'to_path' => 'javascript:alert(1)', 'type' => 301])
            ->call('create')
            ->assertHasFormErrors(['to_path']);

        $this->assertDatabaseMissing('redirects', ['from_path' => '/a']);
    }

    public function test_creating_a_redirect_to_a_valid_internal_path_or_external_https_url_succeeds(): void
    {
        Livewire::test(CreateRedirect::class)
            ->fillForm(['from_path' => '/a', 'to_path' => '/b', 'type' => 301])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('redirects', ['from_path' => '/a', 'to_path' => '/b']);
    }
}
