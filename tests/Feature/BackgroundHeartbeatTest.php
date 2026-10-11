<?php

namespace Tests\Feature;

use App\Support\Scheduling\BackgroundHeartbeat;
use App\Support\Settings\IntegrationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The heartbeat replaces cron, so what matters is that it fires at most
 * once a minute, stays off until switched on, and can never take a page
 * down with it - a visitor must not pay for the backup.
 */
class BackgroundHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    private function heartbeat(): BackgroundHeartbeat
    {
        return app(BackgroundHeartbeat::class);
    }

    private function enable(): void
    {
        app(IntegrationSettings::class)->set(IntegrationSettings::BACKGROUND_WORK_ENABLED, true, 'bool');
    }

    public function test_it_stays_off_until_it_is_switched_on(): void
    {
        $this->assertFalse($this->heartbeat()->enabled());
        $this->assertFalse($this->heartbeat()->tick());
    }

    public function test_it_reports_enabled_once_switched_on(): void
    {
        $this->enable();

        $this->assertTrue($this->heartbeat()->enabled());
    }

    public function test_it_fires_at_most_once_per_interval(): void
    {
        $this->enable();

        $this->assertTrue($this->heartbeat()->tick(), 'the first tick should take the lock');
        $this->assertFalse($this->heartbeat()->tick(), 'a second tick inside the interval must do nothing');
        $this->assertFalse($this->heartbeat()->tick());
    }

    public function test_a_page_still_renders_when_the_heartbeat_is_on(): void
    {
        $this->enable();

        $this->get('/')->assertOk();
        $this->get('/')->assertOk();
    }
}
