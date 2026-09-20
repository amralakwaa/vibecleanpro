<?php

namespace App\Support\Analytics;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\Settings\DemoValue;
use App\Support\Settings\IntegrationSettings;

/**
 * Search Console verification and GA4, read from site settings.
 *
 * GA4 never starts just because an ID was typed in: it needs a valid ID,
 * the explicit "enable" switch, and a published privacy policy. Until then
 * the state is "configured but disabled" and no script is printed.
 */
class AnalyticsSettings
{
    public const SEARCH_CONSOLE_KEY = 'google_site_verification';

    /**
     * The GA4 measurement id reuses the existing setting key.
     */
    public const GA4_ID_KEY = 'google_analytics_id';

    public const GA4_ENABLED_KEY = 'ga4_enabled';

    public const TOKEN_PATTERN = '/^[A-Za-z0-9_\-]{10,100}$/';

    public const GA4_PATTERN = '/^G-[A-Z0-9]{4,15}$/';

    /**
     * The token only when it is valid, switched on, and not a seeded
     * placeholder - so a demo token is never printed for visitors.
     */
    public function searchConsoleToken(): ?string
    {
        $token = trim((string) SiteSetting::get(self::SEARCH_CONSOLE_KEY));

        if (! preg_match(self::TOKEN_PATTERN, $token) || DemoValue::isDemo($token)) {
            return null;
        }

        return SiteSetting::get(IntegrationSettings::SEARCH_CONSOLE_ENABLED, false) ? $token : null;
    }

    /**
     * Whether a usable token is stored, regardless of the switch.
     */
    public function hasSearchConsoleToken(): bool
    {
        $token = trim((string) SiteSetting::get(self::SEARCH_CONSOLE_KEY));

        return preg_match(self::TOKEN_PATTERN, $token) && ! DemoValue::isDemo($token);
    }

    public function ga4MeasurementId(): ?string
    {
        $id = strtoupper(trim((string) SiteSetting::get(self::GA4_ID_KEY)));

        if (! preg_match(self::GA4_PATTERN, $id) || DemoValue::isDemo($id)) {
            return null;
        }

        return $id;
    }

    public function ga4State(): string
    {
        if ($this->ga4MeasurementId() === null) {
            return 'not_configured';
        }

        return SiteSetting::get(self::GA4_ENABLED_KEY, false) && $this->privacyPolicyPublished()
            ? 'active'
            : 'configured_disabled';
    }

    public function isGa4Active(): bool
    {
        return $this->ga4State() === 'active';
    }

    public function privacyPolicyPublished(): bool
    {
        return Page::query()->where('type', PageType::Legal)->where('slug', 'privacy')->where('status', PageStatus::Published)->exists();
    }
}
