<?php

namespace App\Support\Analytics;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\SiteSetting;

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

    public function searchConsoleToken(): ?string
    {
        $token = trim((string) SiteSetting::get(self::SEARCH_CONSOLE_KEY));

        return preg_match(self::TOKEN_PATTERN, $token) ? $token : null;
    }

    public function ga4MeasurementId(): ?string
    {
        $id = strtoupper(trim((string) SiteSetting::get(self::GA4_ID_KEY)));

        return preg_match(self::GA4_PATTERN, $id) ? $id : null;
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
