<?php

namespace App\Support\Scheduling;

use App\Support\Settings\IntegrationSettings;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs Laravel's scheduler and queue worker without a cron daemon, driven
 * by ordinary visitor traffic.
 *
 * This exists because the hosting account has no crontab: `crontab` is not
 * installed and cron is configured only through the hosting panel's web
 * UI. The consequence was not theoretical - the daily backup had never run
 * once since launch, and 516 image-variant jobs had been sitting in the
 * queue since September, which means every image uploaded after that was
 * being served at full size.
 *
 * A real cron entry in the panel is still better and this is written to be
 * harmless beside one: `schedule:run` only runs tasks that are due, the
 * backup command is `withoutOverlapping()`, and the lock below means two
 * triggers a second apart cannot both fire. Adding cron later needs no
 * change here beyond turning the switch off.
 *
 * The work is handed to a DETACHED CLI PROCESS rather than run inside the
 * request. A visitor must never wait for a backup, and a queue worker held
 * open inside a web request would occupy one of the account's limited PHP
 * workers - the exact resource the page cache was added to protect.
 */
class BackgroundHeartbeat
{
    /**
     * One trigger per minute at most, which matches what a cron entry
     * would do. The lock is held for slightly less than the interval so a
     * crashed process cannot wedge it shut.
     */
    private const INTERVAL_SECONDS = 60;

    private const LOCK_KEY = 'heartbeat:background-work';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly IntegrationSettings $settings,
    ) {}

    public function enabled(): bool
    {
        return rescue(fn (): bool => $this->settings->bool(IntegrationSettings::BACKGROUND_WORK_ENABLED), false, false);
    }

    /**
     * @return bool whether this call actually triggered the work
     */
    public function tick(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $store = $this->cache->getStore();

        if (! $store instanceof LockProvider) {
            return false;
        }

        // A non-blocking lock: a request that loses the race does nothing
        // and returns immediately, rather than queueing behind the winner.
        $lock = $store->lock(self::LOCK_KEY, self::INTERVAL_SECONDS - 5);

        if (! $lock->get()) {
            return false;
        }

        try {
            $this->spawn(['schedule:run']);
            $this->spawn(['queue:work', '--stop-when-empty', '--max-time=50', '--tries=3']);
        } catch (Throwable $exception) {
            // Never let background work break a page. The lock is left to
            // expire so the next minute tries again.
            Log::warning('Background heartbeat could not start: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Fire-and-forget, deliberately through the shell rather than
     * Symfony's Process: a child started by Process stays tied to the
     * request that opened it and is killed when the web server tears that
     * request down. Measured here - the lock was taken and the queued job
     * was still sitting there afterwards. `nohup ... &` hands the child to
     * init instead, so a backup that outlives the page view survives.
     *
     * POSIX only. Local Windows development has no heartbeat and does not
     * need one: run `php artisan schedule:work` in a terminal there.
     *
     * @param  list<string>  $arguments
     */
    private function spawn(array $arguments): void
    {
        // The test suite exercises the switch and the once-a-minute lock,
        // which is the logic worth pinning; actually launching workers
        // from a test would be slow and would leave processes behind.
        if (app()->runningUnitTests() || PHP_OS_FAMILY === 'Windows' || ! function_exists('exec')) {
            return;
        }

        $command = sprintf(
            'nohup %s %s %s > /dev/null 2>&1 &',
            escapeshellarg($this->phpBinary()),
            escapeshellarg(base_path('artisan')),
            implode(' ', array_map('escapeshellarg', $arguments)),
        );

        exec($command);
    }

    /**
     * PHP_BINARY under a web SAPI can point at the web interpreter rather
     * than a CLI one, so the path is configurable and set explicitly in
     * the environment on servers where the two differ.
     */
    private function phpBinary(): string
    {
        return (string) config('app.php_binary', PHP_BINARY);
    }
}
