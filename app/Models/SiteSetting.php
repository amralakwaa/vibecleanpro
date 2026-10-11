<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'type'])]
class SiteSetting extends Model
{
    /**
     * Media id of the homepage hero illustration, picked in site settings.
     */
    public const HOME_HERO_MEDIA_ID = 'home_hero_media_id';

    /**
     * The whole table, cached as one map.
     *
     * This is not a micro-optimisation. Several settings are read on EVERY
     * request before anything else runs - the mail transport override in
     * AppServiceProvider, and the page cache's own on/off switch - so a
     * query per key meant the full-page cache could never actually remove
     * the database from a request. Worse, it failed in the wrong
     * direction: under load the settings read was itself refused with
     * "SQLSTATE[HY000] [2002] Operation not permitted", the on/off switch
     * read false, and the cache turned ITSELF OFF exactly when it was
     * needed. One cached map, invalidated on write, removes that.
     */
    private const CACHE_KEY = 'site_settings:map:v1';

    protected static function booted(): void
    {
        static::saved(static fn () => static::forgetMap());
        static::deleted(static fn () => static::forgetMap());
    }

    public static function forgetMap(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{value: mixed, type: string|null}>
     */
    public static function map(): array
    {
        // rescue(): a request must not fail because the table is not
        // migrated yet (fresh install, CI, migrate:fresh), and must not
        // fail because the cache store is briefly unavailable either.
        return rescue(
            fn (): array => Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (self $setting): array => [$setting->key => ['value' => $setting->value, 'type' => $setting->type]])
                ->all()),
            [],
            false,
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::map()[$key] ?? null;

        if (! $setting) {
            return $default;
        }

        return match ($setting['type']) {
            'bool' => (bool) $setting['value'],
            'int' => (int) $setting['value'],
            'json' => json_decode((string) $setting['value'], true),
            default => $setting['value'],
        };
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, static::map());
    }
}
