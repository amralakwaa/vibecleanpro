<?php

namespace App\Support\Backup;

/**
 * Contract an off-site backup destination must satisfy.
 *
 * No implementation ships today: copying a backup set to S3, Google Drive
 * or anywhere else needs a package, and packages need the owner's
 * approval. The panel therefore reports off-site backups as NOT
 * CONFIGURED, and never claims a copy exists.
 */
interface ExternalBackupDestination
{
    public function key(): string;

    public function label(): string;

    /**
     * Whether this destination can actually run with the given settings.
     */
    public function isOperational(BackupSettings $settings): bool;

    /**
     * Uploads one finished backup set (absolute local path).
     */
    public function upload(string $absolutePath, string $remoteName): void;
}
