<?php

/*
 * Local backups written by `php artisan backup:run`. These files sit on the
 * same server as the site: they protect against mistakes and bad deploys,
 * not against losing the server. Copy them off-site (see
 * BACKUP_AND_RESTORE_RUNBOOK.md) - that step is not automated here.
 */
return [
    // Storage disk and folder the backup sets are written to (private).
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'backups'),

    // How many backup sets to keep; older sets are deleted after each run.
    'keep' => (int) env('BACKUP_KEEP', 14),

    // mysqldump binary (e.g. C:/xampp/mysql/bin/mysqldump.exe locally).
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    // Uploaded media on the public disk. Responsive WebP sizes are skipped:
    // `php artisan media:generate-variants` rebuilds them from originals.
    'media_disk' => 'public',
    'media_directory' => 'media',
    'media_exclude_segment' => 'responsive',
];
