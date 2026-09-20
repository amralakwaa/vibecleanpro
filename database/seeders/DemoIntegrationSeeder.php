<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Support\Analytics\AnalyticsSettings;
use App\Support\Backup\ExternalBackupDestinationRegistry;
use App\Support\Settings\IntegrationSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Placeholder integration values for local development, so the panel and
 * the readiness dashboard can be exercised without real credentials.
 *
 * Three safeguards keep this out of production:
 *   1. It refuses to run outside local/testing.
 *   2. Every value is switched OFF (no transport, no tracking, nothing
 *      published), so even seeded it changes nothing a visitor sees.
 *   3. Every value uses a reserved, unroutable name (.invalid / .test) or
 *      an obvious placeholder shape, which DemoValue recognises - so the
 *      public site refuses to present any of it as fact.
 *
 * It is deliberately NOT referenced by DatabaseSeeder or any production
 * seeder, and no password is hard-coded: the demo admin gets a random one,
 * printed once to the console.
 */
class DemoIntegrationSeeder extends Seeder
{
    public const DEMO_ADMIN_EMAIL = 'demo-admin@vibecleanpro.test';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoIntegrationSeeder must never run outside local/testing: it writes placeholder credentials.');
        }

        $settings = app(IntegrationSettings::class);

        // SMTP: complete but switched off, and pointed at a reserved
        // domain that cannot resolve, so nothing can be sent by accident.
        $settings->set(IntegrationSettings::SMTP_ENABLED, false, 'bool');
        $settings->set(IntegrationSettings::SMTP_HOST, 'smtp.example.invalid');
        $settings->set(IntegrationSettings::SMTP_PORT, 587, 'int');
        $settings->set(IntegrationSettings::SMTP_ENCRYPTION, 'tls');
        $settings->set(IntegrationSettings::SMTP_USERNAME, 'demo-user');
        $settings->putSecret(IntegrationSettings::SMTP_PASSWORD, 'DEMO-ONLY-NOT-A-REAL-SECRET');
        $settings->set(IntegrationSettings::SMTP_FROM_ADDRESS, 'no-reply@example.invalid');
        $settings->set(IntegrationSettings::SMTP_FROM_NAME, 'Vibe Clean Pro Demo');

        // Backups: local only, no off-site destination.
        $settings->set(IntegrationSettings::BACKUP_LOCAL_ENABLED, true, 'bool');
        $settings->set(IntegrationSettings::BACKUP_TIME, '02:30');
        $settings->set(IntegrationSettings::BACKUP_KEEP, 14, 'int');
        $settings->set(IntegrationSettings::BACKUP_INCLUDE_DATABASE, true, 'bool');
        $settings->set(IntegrationSettings::BACKUP_INCLUDE_MEDIA, true, 'bool');
        $settings->set(IntegrationSettings::BACKUP_EXTERNAL_ENABLED, false, 'bool');
        $settings->set(IntegrationSettings::BACKUP_EXTERNAL_PROVIDER, ExternalBackupDestinationRegistry::LOCAL_ONLY);
        $settings->set(IntegrationSettings::BACKUP_EXTERNAL_DESTINATION, null);

        // Google: stored, never switched on.
        $settings->set(AnalyticsSettings::SEARCH_CONSOLE_KEY, 'DEMO_SEARCH_CONSOLE_TOKEN');
        $settings->set(IntegrationSettings::SEARCH_CONSOLE_ENABLED, false, 'bool');
        $settings->set(AnalyticsSettings::GA4_ID_KEY, 'G-DEMO000000');
        $settings->set(AnalyticsSettings::GA4_ENABLED_KEY, false, 'bool');

        $this->demoBusinessProfile();
        $this->demoAdmin();
    }

    private function demoBusinessProfile(): void
    {
        $profile = BusinessProfile::query()->oldest('id')->first();

        if (! $profile) {
            return;
        }

        // Filled only where the owner has not entered anything real, and
        // every value is recognisably a placeholder.
        $profile->forceFill([
            'google_business_profile_url' => $profile->google_business_profile_url ?: 'https://example.invalid/google-business',
            'google_review_url' => $profile->google_review_url ?: 'https://example.invalid/google-review',
            'google_maps_place_id' => $profile->google_maps_place_id ?: 'DEMO-PLACE-ID',
            'commercial_registration_number' => $profile->commercial_registration_number ?: '0000000000',
            'display_commercial_registration' => false,
        ])->save();
    }

    private function demoAdmin(): void
    {
        if (User::query()->where('email', self::DEMO_ADMIN_EMAIL)->exists()) {
            return;
        }

        $password = Str::password(20);
        $user = User::query()->create([
            'name' => 'Vibe Clean Pro Demo Admin',
            'email' => self::DEMO_ADMIN_EMAIL,
            'password' => $password,
        ]);
        $user->assignRole('Administrator');

        // Printed once, never stored in the repository.
        $this->command?->warn('Demo admin created: '.self::DEMO_ADMIN_EMAIL);
        $this->command?->warn("Password (shown once, local only): {$password}");
    }
}
