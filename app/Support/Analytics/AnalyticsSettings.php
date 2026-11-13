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

    /** One or more tokens separated by spaces, commas or new lines. */
    public const TOKEN_LIST_PATTERN = '/^[A-Za-z0-9_\-]{10,100}([\s,]+[A-Za-z0-9_\-]{10,100})*$/';

    public const GA4_PATTERN = '/^G-[A-Z0-9]{4,15}$/';

    /**
     * Google Ads conversion tracking: the account's tag id plus one
     * conversion label per click event the site reports (see tracking.js).
     */
    public const ADS_ID_KEY = 'google_ads_id';

    public const ADS_ENABLED_KEY = 'google_ads_enabled';

    public const ADS_PHONE_LABEL_KEY = 'google_ads_phone_label';

    public const ADS_WHATSAPP_LABEL_KEY = 'google_ads_whatsapp_label';

    public const ADS_ID_PATTERN = '/^AW-\d{6,12}$/';

    public const ADS_LABEL_PATTERN = '/^[A-Za-z0-9_\-]{8,40}$/';

    /**
     * The first token to print, or null. See searchConsoleTokens().
     */
    public function searchConsoleToken(): ?string
    {
        return $this->searchConsoleTokens()[0] ?? null;
    }

    /**
     * Every token to print, only when switched on. The setting may hold
     * several tokens (one per Search Console owner or property, separated
     * by spaces, commas or new lines) so adding an owner never removes
     * the tag that keeps an existing owner verified.
     *
     * @return list<string>
     */
    public function searchConsoleTokens(): array
    {
        return SiteSetting::get(IntegrationSettings::SEARCH_CONSOLE_ENABLED, false) ? $this->storedSearchConsoleTokens() : [];
    }

    /**
     * Whether a usable token is stored, regardless of the switch.
     */
    public function hasSearchConsoleToken(): bool
    {
        return $this->storedSearchConsoleTokens() !== [];
    }

    /**
     * Stored tokens that are valid and not a seeded placeholder - so a demo
     * token is never printed for visitors.
     *
     * @return list<string>
     */
    private function storedSearchConsoleTokens(): array
    {
        $tokens = preg_split('/[\s,]+/', trim((string) SiteSetting::get(self::SEARCH_CONSOLE_KEY)), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(
            $tokens,
            fn (string $token): bool => preg_match(self::TOKEN_PATTERN, $token) === 1 && ! DemoValue::isDemo($token),
        )));
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

    public function adsId(): ?string
    {
        $id = strtoupper(trim((string) SiteSetting::get(self::ADS_ID_KEY)));

        if (! preg_match(self::ADS_ID_PATTERN, $id) || DemoValue::isDemo($id)) {
            return null;
        }

        return $id;
    }

    /**
     * Ads tracking follows the same gate as GA4: a valid id, the explicit
     * switch, and a published privacy policy.
     */
    public function isAdsActive(): bool
    {
        return $this->adsId() !== null
            && (bool) SiteSetting::get(self::ADS_ENABLED_KEY, false)
            && $this->privacyPolicyPublished();
    }

    /**
     * The "AW-id/label" a click event reports as a conversion, keyed by the
     * event type tracking.js sends. Only events with a valid label appear.
     *
     * @return array<string, string>
     */
    public function adsConversionTargets(): array
    {
        $adsId = $this->adsId();

        if ($adsId === null) {
            return [];
        }

        $targets = [];

        foreach (['phone_click' => self::ADS_PHONE_LABEL_KEY, 'whatsapp_click' => self::ADS_WHATSAPP_LABEL_KEY] as $event => $key) {
            $label = trim((string) SiteSetting::get($key));

            if (preg_match(self::ADS_LABEL_PATTERN, $label)) {
                $targets[$event] = $adsId.'/'.$label;
            }
        }

        return $targets;
    }

    public function privacyPolicyPublished(): bool
    {
        return Page::query()->where('type', PageType::Legal)->where('slug', 'privacy')->where('status', PageStatus::Published)->exists();
    }
}
