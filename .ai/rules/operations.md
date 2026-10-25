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

## Production runs with cached config, routes, views and events
`config:cache`, `route:cache`, `view:cache` and `event:cache` are all active
on the live server. Two consequences, both of which have bitten before:

**A `.env` edit does nothing until the config is rebuilt.** Changing a value
and reloading will show the old one. Always follow an `.env` change with
`php artisan config:cache` on the server (`/opt/alt/php84/usr/bin/php`).
Likewise a deployed route or Blade change needs `optimize:clear` or the
matching `*:cache` — a view edit that appears to have no effect is almost
always this.

**`env()` outside `config/` returns null.** Never call it from `app/`,
`routes/` or a Blade view. `MailSettings::environmentControlsMail()` did,
which is what blocked caching for months: it would have answered `false` on
every production request, silently, and only in production. Values needed at
runtime get their own config key. Two of them exist for this reason —
`mail.environment_host` and `mail.environment_mailer` hold MAIL_HOST and
MAIL_MAILER raw, because `mail.mailers.smtp.host` carries a 127.0.0.1
fallback and `mail.default` is the *effective* mailer that
`applyRuntimeConfiguration()` itself overwrites.

## public_html is the web root; `public_path()` points at it
`bootstrap/app.php` calls `usePublicPath()` on `public_html` when that
directory exists, so the framework and the web server agree on one
directory. Before that they did not: a duplicate `public/` existed with its
own `build/manifest.json`, the browser fetched from `public_html` while
`public_path()` resolved to `public`, and a stylesheet deployed to the served
directory was invisible to Vite — the admin panel answered 500 until the
entry was added to both. Deploy built assets to `public_html/build` only.
The guard means local development and CI, which have no `public_html`, are
unaffected.
