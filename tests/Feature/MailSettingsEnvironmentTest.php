<?php

namespace Tests\Feature;

use App\Support\Mail\MailSettings;
use Tests\TestCase;

/**
 * environmentControlsMail() decides whether the panel offers SMTP settings
 * at all, so it has to answer the same way whether or not config:cache has
 * run. It used to read env() directly, which returns null the moment the
 * config is cached - meaning the one environment where it matters,
 * production, was also the one where it would silently answer false.
 *
 * These tests pin the behaviour against config, which survives caching.
 */
class MailSettingsEnvironmentTest extends TestCase
{
    private function settings(): MailSettings
    {
        return app(MailSettings::class);
    }

    public function test_the_environment_owns_mail_when_it_names_a_host_and_a_real_transport(): void
    {
        config(['mail.environment_host' => 'smtp.example.com', 'mail.environment_mailer' => 'smtp']);

        $this->assertTrue($this->settings()->environmentControlsMail());
    }

    public function test_an_unset_host_means_the_environment_does_not_own_mail(): void
    {
        // The smtp mailer defaults MAIL_HOST to 127.0.0.1, so reading that
        // key instead of the raw one would make "nobody set a host" look
        // like a configured server.
        config(['mail.environment_host' => null, 'mail.environment_mailer' => 'smtp']);

        $this->assertFalse($this->settings()->environmentControlsMail());
        $this->assertSame('127.0.0.1', config('mail.mailers.smtp.host'), 'the scaffold default still exists, which is why the raw key is needed');
    }

    /**
     * Laravel's scaffold ships MAIL_MAILER=log with a placeholder host, and
     * that must not count as the server handling mail - it would leave the
     * panel unable to configure anything.
     */
    public function test_a_placeholder_transport_never_counts(): void
    {
        foreach (['log', 'array', ''] as $transport) {
            config(['mail.environment_host' => 'smtp.example.com', 'mail.environment_mailer' => $transport]);

            $this->assertFalse(
                $this->settings()->environmentControlsMail(),
                "the [{$transport}] transport must not count as the environment owning mail",
            );
        }
    }

    public function test_the_effective_mailer_is_not_what_this_reads(): void
    {
        // applyRuntimeConfiguration() sets mail.default to smtp when the
        // panel supplies settings. If this check read that key it would
        // latch true afterwards and lock the panel out of its own form.
        config(['mail.environment_host' => null, 'mail.environment_mailer' => null, 'mail.default' => 'smtp']);

        $this->assertFalse($this->settings()->environmentControlsMail());
    }

    public function test_the_check_reads_no_environment_variable_directly(): void
    {
        // The regression itself: with the config set and the environment
        // variables deliberately absent, the answer must still be yes. A
        // version reading env() would fail here exactly as it would under a
        // cached config in production.
        putenv('MAIL_HOST');
        putenv('MAIL_MAILER');
        unset($_ENV['MAIL_HOST'], $_ENV['MAIL_MAILER'], $_SERVER['MAIL_HOST'], $_SERVER['MAIL_MAILER']);

        config(['mail.environment_host' => 'smtp.example.com', 'mail.environment_mailer' => 'smtp']);

        $this->assertTrue($this->settings()->environmentControlsMail());
    }
}
