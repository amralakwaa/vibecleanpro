<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'type'])]
class SiteSetting extends Model
{
    /**
     * Media id of the homepage hero illustration, picked in site settings.
     */
    public const HOME_HERO_MEDIA_ID = 'home_hero_media_id';

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'bool' => (bool) $setting->value,
            'int' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }
}
