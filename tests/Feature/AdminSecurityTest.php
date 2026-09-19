<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\AccountSecurity;
use App\Filament\Pages\Auth\Login;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
    }

    public function test_two_factor_secrets_are_encrypted_at_rest_and_hidden_from_serialisation(): void
    {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $user->saveAppAuthenticationRecoveryCodes(['code-one', 'code-two']);

        $raw = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame('JBSWY3DPEHPK3PXP', $raw->app_authentication_secret);
        $this->assertStringNotContainsString('code-one', (string) $raw->app_authentication_recovery_codes);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->fresh()->getAppAuthenticationSecret());
        $this->assertArrayNotHasKey('app_authentication_secret', $user->fresh()->toArray());
    }

    public function test_the_account_page_offers_two_factor_and_no_self_service_account_changes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Content Manager');
        $this->actingAs($user);

        Livewire::test(AccountSecurity::class)
            ->assertOk()
            ->assertSeeHtml('setUpAppAuthentication')
            ->assertDontSee('كلمة المرور الحالية');
    }

    public function test_two_factor_is_optional_so_no_current_admin_is_locked_out(): void
    {
        $this->assertFalse(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired());
        $this->assertTrue(Filament::getPanel('admin')->hasMultiFactorAuthentication());
    }

    public function test_a_successful_and_a_failed_login_are_audited_without_the_password(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'password' => 'correct-password']);

        Livewire::test(Login::class)->fillForm(['email' => 'admin@example.test', 'password' => 'wrong-password'])->call('authenticate');
        Livewire::test(Login::class)->fillForm(['email' => 'admin@example.test', 'password' => 'correct-password'])->call('authenticate');

        $failed = AuditLog::query()->where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame('admin@example.test', $failed->changes['attempted_email']);
        $this->assertTrue(AuditLog::query()->where('action', 'auth.login')->where('user_id', $user->id)->exists());
        $this->assertSame(0, AuditLog::query()->where('changes', 'like', '%password%')->count());
    }

    public function test_the_login_is_rate_limited_and_the_limit_is_audited(): void
    {
        foreach (range(1, 6) as $attempt) {
            Livewire::test(Login::class)->fillForm(['email' => 'intruder@example.test', 'password' => 'guess-'.$attempt])->call('authenticate');
        }

        $this->assertSame(5, AuditLog::query()->where('action', 'auth.login_failed')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'auth.login_rate_limited')->count());
    }
}
