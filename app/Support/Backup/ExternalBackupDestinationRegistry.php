<?php

namespace App\Support\Backup;

/**
 * The destinations the panel offers. Extend by registering an
 * implementation of ExternalBackupDestination here.
 *
 * "local_only" is the honest default: backups are written next to the
 * site. Anything else is listed so the owner can state the intent, but it
 * reports itself as not operational until an implementation exists.
 */
class ExternalBackupDestinationRegistry
{
    public const LOCAL_ONLY = 'local_only';

    /**
     * Providers offered in the panel. The value is the label; a provider
     * becomes usable only when an implementation is registered below.
     *
     * @var array<string, string>
     */
    public const PROVIDERS = [
        self::LOCAL_ONLY => 'محلي فقط (على الخادم نفسه)',
        's3' => 'تخزين متوافق مع S3 — يحتاج حزمة وموافقة المالك',
        'google_drive' => 'Google Drive — يحتاج حزمة وموافقة المالك',
        'other' => 'وجهة أخرى (يدويًا)',
    ];

    /**
     * @var array<string, ExternalBackupDestination>
     */
    private array $destinations = [];

    public function register(ExternalBackupDestination $destination): void
    {
        $this->destinations[$destination->key()] = $destination;
    }

    public function get(string $key): ?ExternalBackupDestination
    {
        return $this->destinations[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->destinations[$key]);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return self::PROVIDERS;
    }
}
