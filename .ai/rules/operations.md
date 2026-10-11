---
paths:
  - 'app/Support/Scheduling/**,app/Http/Middleware/TriggerBackgroundWork.php,app/Support/Cache/**,app/Http/Middleware/CachePublicPages.php,app/Models/SiteSetting.php,routes/console.php'
---

# Production operations

## There is no cron on this host
`crontab` is not installed on the Hostinger account; cron can only be added
through the hosting panel's web UI. Nothing had ever run as a result — the
daily backup had never executed since launch, and 516 image-variant jobs sat
queued from September, so every image uploaded after that was served full
size.

`BackgroundHeartbeat` replaces it: `TriggerBackgroundWork` calls it from
`terminate()`, it takes a once-a-minute lock, and it spawns `schedule:run`
and `queue:work --stop-when-empty` as detached processes.

- **Spawn through the shell (`nohup … &`), not Symfony Process.** A child
  started by `Process::start()` is killed when the web server tears the
  request down. This was measured: the lock was taken and the queued job was
  still there afterwards.
- `SCHEDULER_PHP_BINARY` in `.env` points at the CLI binary
  (`/opt/alt/php84/usr/bin/php`); `PHP_BINARY` under a web SAPI is not it.
- It is switched from the panel (System & Integrations → الأداء) and is safe
  beside a real cron entry — `schedule:run` only runs what is due and
  `backup:run` is `withoutOverlapping()`. Turn the switch off if cron is
  ever added in the panel.
- POSIX only; local Windows development runs `php artisan schedule:work`.

## Nothing read on every request may query the database
The account caps concurrent MySQL connections, and that cap is what breaks
first under load. `SiteSetting` therefore caches the whole table as one map,
invalidated on write.

This is load-bearing, not tidiness: the page cache's own on/off switch is a
setting. With a query per key the cache could never remove the database from
a request, and under load the settings read was itself refused — so the
switch read `false` and **the cache turned itself off exactly when it was
needed**. Do not add a per-request setting read that bypasses the map.

## Full-page cache invariants
See `CachePublicPages`. Four things keep it correct, and each has a test:
a body containing a CSRF token is never stored; only body and content type
are stored (never headers or cookies); the key keeps an **allowlist** of
significant query parameters, so an unknown parameter cannot be used to
bypass the cache and put the database back on the critical path; and a
signed-in editor always gets the live page.
