<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Pages\ManageIntegrations;
use App\Filament\Widgets\SystemReadinessWidget;
use App\Models\AuditLog;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Analytics\AnalyticsSettings;
use App\Support\Backup\BackupSettings;
use App\Support\Mail\MailSettings;
use App\Support\Readiness\LaunchReadiness;
use App\Support\Settings\DemoValue;
use App\Support\Settings\IntegrationSettings;
use Database\Seeders\DemoIntegrationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class IntegrationsControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->profile();
    }

    private function profile(array $attributes = []): BusinessProfile
    {
        $profile = BusinessProfile::query()->oldest('id')->first() ?? new BusinessProfile;
        $profile->forceFill(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', ...$attributes])->save();

        return $profile;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email' => 'owner@vibecleanpro.com']);
        $user->assignRole('Administrator');
        $this->actingAs($user);

        return $user;
    }

    private function settings(): IntegrationSettings
    {
        return app(IntegrationSettings::class);
    }

    /**
     * A complete, working SMTP configuration.
     */
    private function configureSmtp(): void
    {
        $settings = $this->settings();
        $settings->set(IntegrationSettings::SMTP_ENABLED, true, 'bool');
        $settings->set(IntegrationSettings::SMTP_HOST, 'smtp.mailprovider.test.invalid');
        $settings->set(IntegrationSettings::SMTP_PORT, 587, 'int');
        $settings->set(IntegrationSettings::SMTP_FROM_ADDRESS, 'no-reply@vibecleanpro.com');
        $settings->putSecret(IntegrationSettings::SMTP_PASSWORD, 'super-secret-value');
        BusinessProfile::query()->update(['lead_notification_email' => 'team@vibecleanpro.com']);
        config(['mail.default' => 'smtp']);
    }

    // ---- SMTP -----------------------------------------------------------

    public function test_the_smtp_password_is_encrypted_at_rest_and_never_readable_through_settings(): void
    {
        $this->settings()->putSecret(IntegrationSettings::SMTP_PASSWORD, 'super-secret-value');

        $stored = SiteSetting::query()->where('key', IntegrationSettings::SMTP_PASSWORD)->value('value');

        $this->assertNotSame('super-secret-value', $stored);
        $this->assertSame('super-secret-value', Crypt::decryptString($stored));
        $this->assertNull($this->settings()->get(IntegrationSettings::SMTP_PASSWORD), 'secrets are not returned by the ordinary getter');
        $this->assertTrue($this->settings()->hasSecret(IntegrationSettings::SMTP_PASSWORD));
    }

    public function test_the_panel_never_renders_the_stored_password_and_keeps_it_when_left_empty(): void
    {
        $this->admin();
        $this->settings()->putSecret(IntegrationSettings::SMTP_PASSWORD, 'super-secret-value');

        $html = Livewire::test(ManageIntegrations::class)->assertOk()->html();
        $this->assertStringNotContainsString('super-secret-value', $html);

        Livewire::test(ManageIntegrations::class)
            ->fillForm([IntegrationSettings::SMTP_HOST => 'smtp.new-host.test.invalid', IntegrationSettings::SMTP_PASSWORD => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($this->settings()->hasSecret(IntegrationSettings::SMTP_PASSWORD));
        $this->assertSame('super-secret-value', $this->settings()->revealSecret(IntegrationSettings::SMTP_PASSWORD));
    }

    public function test_saving_integration_settings_is_audited_without_any_value(): void
    {
        $this->admin();

        Livewire::test(ManageIntegrations::class)
            ->fillForm([IntegrationSettings::SMTP_HOST => 'smtp.provider.test.invalid', IntegrationSettings::SMTP_PASSWORD => 'another-secret'])
            ->call('save');

        $entry = AuditLog::query()->where('action', 'settings.integrations_updated')->firstOrFail();
        $this->assertNotNull($entry->user_id);
        $this->assertStringNotContainsString('another-secret', json_encode($entry->changes ?? []) ?: '');
        $this->assertSame(0, AuditLog::query()->where('changes', 'like', '%secret%')->count());
    }

    public function test_smtp_is_not_ready_until_it_is_complete_enabled_and_has_an_inbox(): void
    {
        $mail = app(MailSettings::class);
        $this->assertSame('not_configured', $mail->readiness()['state']);

        $this->settings()->set(IntegrationSettings::SMTP_HOST, 'smtp.provider.test.invalid');
        $this->assertSame('configured', $mail->readiness()['state'], 'a host alone is never ready');

        $this->settings()->set(IntegrationSettings::SMTP_ENABLED, true, 'bool');
        $this->assertSame('error', $mail->readiness()['state'], 'enabled but incomplete');

        $this->configureSmtp();
        $this->assertSame('ready', app(MailSettings::class)->readiness()['state']);
    }

    public function test_the_environment_transport_wins_over_the_panel(): void
    {
        $this->configureSmtp();
        config(['mail.default' => 'log']);

        // Captured so the exact previous state is restored: PHPUnit sets
        // MAIL_MAILER=array for the whole suite, and deleting it would
        // leave every later test without a test transport.
        $original = ['MAIL_HOST' => getenv('MAIL_HOST'), 'MAIL_MAILER' => getenv('MAIL_MAILER')];
        putenv('MAIL_HOST=env-host.test.invalid');
        putenv('MAIL_MAILER=smtp');
        $_ENV['MAIL_HOST'] = 'env-host.test.invalid';
        $_ENV['MAIL_MAILER'] = 'smtp';

        try {
            app(MailSettings::class)->applyRuntimeConfiguration();

            $this->assertSame('log', config('mail.default'), 'panel settings never override the environment');
            $this->assertSame('not_configured', app(MailSettings::class)->readiness()['state']);
        } finally {
            // Restored even on failure: putenv() outlives the test.
            foreach ($original as $key => $value) {
                if ($value === false) {
                    putenv($key);
                    unset($_ENV[$key]);

                    continue;
                }

                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }

    public function test_the_test_message_goes_to_the_stored_inbox_without_leaving_the_machine(): void
    {
        Mail::fake();
        $this->admin();
        $this->configureSmtp();

        Livewire::test(ManageIntegrations::class)->call('sendTestMessage')->assertNotified();

        // The audit entry is written only on the success branch, so it is
        // the proof the message went out through the faked transport.
        $this->assertTrue(AuditLog::query()->where('action', 'settings.smtp_test')->exists());

        // With no inbox saved, nothing is attempted at all.
        BusinessProfile::query()->update(['lead_notification_email' => null]);
        AuditLog::query()->delete();
        Livewire::test(ManageIntegrations::class)->call('sendTestMessage')->assertNotified();
        $this->assertFalse(AuditLog::query()->where('action', 'settings.smtp_test')->exists());
    }

    // ---- Backups --------------------------------------------------------

    public function test_backup_settings_drive_the_command_and_external_backup_stays_unconfigured(): void
    {
        Storage::fake(config('backup.disk'));
        $backups = app(BackupSettings::class);

        $this->assertSame('not_configured', $backups->externalReadiness()['state']);
        $this->assertSame('not_configured', $backups->localReadiness()['state'], 'no backup set exists yet');

        $this->settings()->set(IntegrationSettings::BACKUP_KEEP, 5, 'int');
        $this->settings()->set(IntegrationSettings::BACKUP_TIME, '04:15');
        $this->assertSame(5, $backups->keep());
        $this->assertSame('04:15', $backups->dailyTime());

        // Choosing a destination without an implementation must not claim success.
        $this->settings()->set(IntegrationSettings::BACKUP_EXTERNAL_ENABLED, true, 'bool');
        $this->settings()->set(IntegrationSettings::BACKUP_EXTERNAL_PROVIDER, 's3');
        $this->assertSame('error', $backups->externalReadiness()['state']);
        $this->assertStringContainsString('لا يوجد تنفيذ', $backups->externalReadiness()['detail']);
    }

    public function test_backup_credentials_are_encrypted_and_never_exposed(): void
    {
        $this->admin();
        $this->settings()->putSecret(IntegrationSettings::BACKUP_EXTERNAL_SECRET, 'bucket-secret-key');

        $stored = SiteSetting::query()->where('key', IntegrationSettings::BACKUP_EXTERNAL_SECRET)->value('value');
        $this->assertNotSame('bucket-secret-key', $stored);
        $this->assertStringNotContainsString('bucket-secret-key', Livewire::test(ManageIntegrations::class)->html());
    }

    // ---- Search Console & GA4 -------------------------------------------

    public function test_the_verification_tag_appears_only_when_valid_and_switched_on(): void
    {
        $this->settings()->set(AnalyticsSettings::SEARCH_CONSOLE_KEY, 'AbC123_def-456ghi');
        $this->get('/')->assertOk()->assertDontSee('google-site-verification', false);

        $this->settings()->set(IntegrationSettings::SEARCH_CONSOLE_ENABLED, true, 'bool');
        $this->get('/')->assertSee('<meta name="google-site-verification" content="AbC123_def-456ghi">', false);
    }

    public function test_a_demo_verification_token_is_never_printed(): void
    {
        $this->settings()->set(AnalyticsSettings::SEARCH_CONSOLE_KEY, 'DEMO_SEARCH_CONSOLE_TOKEN');
        $this->settings()->set(IntegrationSettings::SEARCH_CONSOLE_ENABLED, true, 'bool');

        $this->get('/')->assertOk()->assertDontSee('google-site-verification', false);
        $this->assertSame('not_configured', app(LaunchReadiness::class)->searchConsole()->state);
    }

    public function test_ga4_stays_disabled_while_the_privacy_policy_is_a_draft(): void
    {
        $this->settings()->set(AnalyticsSettings::GA4_ID_KEY, 'G-ABC1234567');
        $this->settings()->set(AnalyticsSettings::GA4_ENABLED_KEY, true, 'bool');
        $privacy = Page::factory()->create(['type' => PageType::Legal, 'title' => 'privacy', 'slug' => 'privacy', 'status' => PageStatus::Draft]);

        $this->assertSame('configured_disabled', app(AnalyticsSettings::class)->ga4State());
        $this->get('/')->assertDontSee('googletagmanager.com/gtag', false);

        ContentBlock::factory()->for($privacy)->create(['type' => 'rich_text', 'data' => ['content' => '<p>سياسة معتمدة.</p>']]);
        $privacy->update(['status' => PageStatus::Published]);

        $this->assertSame('active', app(AnalyticsSettings::class)->ga4State());
        $this->get('/')->assertSee('googletagmanager.com/gtag/js?id=G-ABC1234567', false);
    }

    public function test_a_demo_measurement_id_never_activates_tracking(): void
    {
        $this->settings()->set(AnalyticsSettings::GA4_ID_KEY, 'G-DEMO000000');
        $this->settings()->set(AnalyticsSettings::GA4_ENABLED_KEY, true, 'bool');

        $this->assertSame('not_configured', app(AnalyticsSettings::class)->ga4State());
        $this->get('/')->assertOk()->assertDontSee('gtag', false);
    }

    // ---- Google Business Profile & reviews ------------------------------

    public function test_google_links_appear_only_when_real(): void
    {
        $this->profile(['google_business_profile_url' => 'https://example.invalid/google-business', 'google_review_url' => 'https://example.invalid/google-review']);

        $html = $this->get('/contact')->assertOk()->getContent();
        $this->assertStringNotContainsString('example.invalid', $html);
        $this->assertStringNotContainsString('قيّم تجربتك معنا على Google', $html);
        $this->assertSame('not_configured', app(LaunchReadiness::class)->googleReviews()->state);

        BusinessProfile::query()->update([
            'google_business_profile_url' => 'https://maps.google.com/?cid=123456',
            'google_review_url' => 'https://g.page/r/real/review',
        ]);

        $html = $this->get('/contact')->getContent();
        $this->assertStringContainsString('عرض ملفنا على Google', $html);
        $this->assertStringContainsString('https://g.page/r/real/review', $html);
        $this->assertSame('configured', app(LaunchReadiness::class)->googleReviews()->state);
    }

    public function test_the_review_cta_never_steers_the_rating(): void
    {
        $this->profile(['google_review_url' => 'https://g.page/r/real/review']);
        $html = $this->get('/contact')->getContent();

        foreach (['5 نجوم', 'خمس نجوم', 'تقييم إيجابي', 'خصم', 'هدية', 'مكافأة'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html);
        }
    }

    // ---- Commercial registration ----------------------------------------

    public function test_a_demo_registration_number_is_never_shown_even_when_display_is_on(): void
    {
        $this->profile(['commercial_registration_number' => '0000000000', 'display_commercial_registration' => true]);

        $this->get('/')->assertOk()->assertDontSee('0000000000');
        $this->assertNull(BusinessProfile::query()->first()->publicCommercialRegistration());

        BusinessProfile::query()->update(['commercial_registration_number' => '1010123456']);
        $this->get('/')->assertSee('1010123456');
    }

    // ---- Demo data safety ------------------------------------------------

    public function test_the_demo_seeder_refuses_to_run_outside_local_and_testing(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            (new DemoIntegrationSeeder)->setContainer(app())->run();
            $this->fail('the demo seeder ran in production');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('never run outside local/testing', $exception->getMessage());
            $this->assertSame(0, SiteSetting::query()->where('key', IntegrationSettings::SMTP_HOST)->count());
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_demo_values_are_recognised_and_stay_switched_off(): void
    {
        $this->seed(DemoIntegrationSeeder::class);

        $this->assertFalse(app(MailSettings::class)->isEnabled());
        $this->assertFalse(app(BackupSettings::class)->externalEnabled());
        $this->assertSame('not_configured', app(AnalyticsSettings::class)->ga4State());
        $this->assertNull(app(AnalyticsSettings::class)->searchConsoleToken());

        $profile = BusinessProfile::query()->first();
        $this->assertNull($profile->publicGoogleReviewUrl());
        $this->assertNull($profile->publicCommercialRegistration());
        $this->assertTrue(DemoValue::isDemo(DemoIntegrationSeeder::DEMO_ADMIN_EMAIL));
    }

    public function test_no_demo_value_reaches_a_public_page(): void
    {
        $this->seed(DemoIntegrationSeeder::class);

        foreach (['/', '/contact', '/quote', '/services', '/areas'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            foreach (['example.invalid', 'vibecleanpro.test', 'DEMO', 'demo-user', 'NOT-A-REAL', '0000000000'] as $needle) {
                $this->assertStringNotContainsString($needle, $html, "{$path} leaks {$needle}");
            }
        }
    }

    public function test_the_demo_admin_is_excluded_from_readiness_counts(): void
    {
        $this->seed(DemoIntegrationSeeder::class);
        $real = User::factory()->create(['email' => 'owner@vibecleanpro.com']);
        $real->assignRole('Administrator');

        $accounts = app(LaunchReadiness::class)->adminAccounts();

        $this->assertStringContainsString('1', $accounts->label, 'only the real account counts');
        $this->assertStringNotContainsString(DemoIntegrationSeeder::DEMO_ADMIN_EMAIL, $accounts->detail);
    }

    // ---- Readiness dashboard --------------------------------------------

    public function test_the_readiness_dashboard_reports_every_integration_with_a_reason(): void
    {
        $this->admin();

        $items = collect(app(LaunchReadiness::class)->all());

        $this->assertEqualsCanonicalizing(
            ['smtp', 'lead_notifications', 'admin_accounts', 'two_factor', 'backups_local', 'backups_external', 'search_console', 'ga4', 'gbp', 'google_reviews', 'commercial_registration', 'privacy', 'terms', 'about'],
            $items->pluck('key')->all(),
        );
        $this->assertTrue($items->every(fn ($item) => filled($item->detail)), 'every state explains itself');

        Livewire::test(SystemReadinessWidget::class)
            ->assertOk()
            ->assertSee('جاهزية التشغيل')
            ->assertSee('إعدادات البريد (SMTP)');
    }

    public function test_the_readiness_dashboard_never_prints_a_secret(): void
    {
        $this->admin();
        $this->configureSmtp();

        $html = Livewire::test(SystemReadinessWidget::class)->html();

        $this->assertStringNotContainsString('super-secret-value', $html);
    }

    public function test_two_factor_readiness_blocks_on_a_super_admin_without_it(): void
    {
        $superAdmin = User::factory()->create(['email' => 'boss@vibecleanpro.com']);
        $superAdmin->assignRole('Super Admin');

        $this->assertSame('error', app(LaunchReadiness::class)->twoFactor()->state);

        $superAdmin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $this->assertSame('complete', app(LaunchReadiness::class)->twoFactor()->state);
    }

    public function test_the_integrations_page_is_closed_to_roles_without_settings_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Content Manager');
        $this->actingAs($user);

        $this->assertFalse(ManageIntegrations::canAccess());
    }
}
