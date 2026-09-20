<?php

namespace App\Support\Settings;

use App\Models\SiteSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Typed access to the operational integration settings kept in the
 * existing site_settings key/value table.
 *
 * Secrets (the SMTP password, external backup keys) are encrypted at rest
 * with the app key and are never handed back to a form, an export, a log
 * line or an audit entry - only `hasSecret()` is exposed, so the panel can
 * say "saved" without showing it. Configuration in the environment always
 * wins over the database, so a server-managed secret is never overridden
 * by something typed into the panel.
 */
class IntegrationSettings
{
    public const SMTP_ENABLED = 'smtp_enabled';

    public const SMTP_HOST = 'smtp_host';

    public const SMTP_PORT = 'smtp_port';

    public const SMTP_ENCRYPTION = 'smtp_encryption';

    public const SMTP_USERNAME = 'smtp_username';

    public const SMTP_PASSWORD = 'smtp_password';

    public const SMTP_FROM_ADDRESS = 'smtp_from_address';

    public const SMTP_FROM_NAME = 'smtp_from_name';

    public const BACKUP_LOCAL_ENABLED = 'backup_local_enabled';

    public const BACKUP_TIME = 'backup_time';

    public const BACKUP_KEEP = 'backup_keep';

    public const BACKUP_INCLUDE_DATABASE = 'backup_include_database';

    public const BACKUP_INCLUDE_MEDIA = 'backup_include_media';

    public const BACKUP_EXTERNAL_ENABLED = 'backup_external_enabled';

    public const BACKUP_EXTERNAL_PROVIDER = 'backup_external_provider';

    public const BACKUP_EXTERNAL_DESTINATION = 'backup_external_destination';

    public const BACKUP_EXTERNAL_KEY = 'backup_external_key';

    public const BACKUP_EXTERNAL_SECRET = 'backup_external_secret';

    public const BACKUP_LAST_RESTORE_TEST = 'backup_last_restore_test_at';

    public const SEARCH_CONSOLE_ENABLED = 'google_site_verification_enabled';

    /**
     * Every secret key. These never leave the server in readable form.
     */
    public const SECRET_KEYS = [self::SMTP_PASSWORD, self::BACKUP_EXTERNAL_KEY, self::BACKUP_EXTERNAL_SECRET];

    public function get(string $key, mixed $default = null): mixed
    {
        if (in_array($key, self::SECRET_KEYS, true)) {
            return $default;
        }

        return SiteSetting::get($key, $default);
    }

    public function string(string $key): ?string
    {
        $value = $this->get($key);

        return filled($value) ? trim((string) $value) : null;
    }

    public function bool(string $key): bool
    {
        return (bool) SiteSetting::get($key, false);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = SiteSetting::get($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }

    public function has(string $key): bool
    {
        return SiteSetting::query()->where('key', $key)->exists();
    }

    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
    }

    /**
     * Stores a secret encrypted. An empty value leaves the stored secret
     * untouched, so saving the form without retyping it keeps it.
     */
    public function putSecret(string $key, ?string $value): void
    {
        if (blank($value)) {
            return;
        }

        $this->set($key, Crypt::encryptString($value));
    }

    public function forgetSecret(string $key): void
    {
        SiteSetting::query()->where('key', $key)->delete();
    }

    public function hasSecret(string $key): bool
    {
        return filled(SiteSetting::get($key));
    }

    /**
     * Only the mailer/backup runtime reads this - never a form or a log.
     */
    public function revealSecret(string $key): ?string
    {
        $stored = SiteSetting::get($key);

        if (blank($stored)) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            // A secret encrypted with a previous APP_KEY: treat it as
            // absent rather than crashing the request that needs it.
            return null;
        }
    }
}
