<?php

use App\Support\Backup\BackupSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('leads:prune-personal-data')->dailyAt('03:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Time and on/off come from the panel (System & Integrations); the
// database is not available at boot on a fresh install, so fall back.
$backupTime = rescue(fn () => app(BackupSettings::class)->dailyTime(), '02:30', false);
$backupEnabled = rescue(fn () => app(BackupSettings::class)->localEnabled(), false, false);

Schedule::command('backup:run')->dailyAt($backupTime)->withoutOverlapping()->when(fn () => $backupEnabled);
