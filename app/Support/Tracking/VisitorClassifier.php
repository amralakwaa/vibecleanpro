<?php

namespace App\Support\Tracking;

class VisitorClassifier
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|facebookexternalhit|curl|wget|python|go-http/i';

    private const SEARCH_HOSTS = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'yandex.', 'ecosia.'];

    private const SOCIAL_HOSTS = ['instagram.', 'facebook.', 'fb.', 't.co', 'twitter.', 'x.com', 'tiktok.', 'snapchat.', 'linkedin.', 'youtube.', 'wa.me', 'whatsapp.'];

    private const PAID_MEDIUMS = ['cpc', 'ppc', 'paid', 'paid_social', 'paidsocial', 'display', 'ads'];

    public function isBot(?string $userAgent): bool
    {
        return blank($userAgent) || (bool) preg_match(self::BOT_PATTERN, $userAgent);
    }

    public function deviceType(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;

        return match (true) {
            (bool) preg_match('/ipad|tablet|android(?!.*mobile)/i', $userAgent) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android/i', $userAgent) => 'mobile',
            default => 'desktop',
        };
    }

    /**
     * First-touch traffic source from the attribution the session already
     * holds (see CaptureLeadAttribution). UTM tags win over the referrer
     * because they were set deliberately.
     */
    public function source(?string $utmSource, ?string $utmMedium, ?string $utmCampaign, ?string $referrerHost): string
    {
        $utmSource = mb_strtolower((string) $utmSource);
        $utmMedium = mb_strtolower((string) $utmMedium);
        $utmCampaign = mb_strtolower((string) $utmCampaign);
        $referrerHost = mb_strtolower((string) $referrerHost);

        return match (true) {
            $utmCampaign === 'gbp' || str_contains($utmSource, 'gbp') => 'gbp',
            in_array($utmMedium, self::PAID_MEDIUMS, true) => 'paid',
            $utmSource !== '' && $this->hostMatches($utmSource, self::SOCIAL_HOSTS, bare: true) => 'social',
            $utmSource !== '' => 'campaign',
            $referrerHost === '' => 'direct',
            $this->hostMatches($referrerHost, self::SEARCH_HOSTS) => 'organic_search',
            $this->hostMatches($referrerHost, self::SOCIAL_HOSTS) => 'social',
            default => 'referral',
        };
    }

    /**
     * @param  list<string>  $needles
     */
    private function hostMatches(string $haystack, array $needles, bool $bare = false): bool
    {
        foreach ($needles as $needle) {
            $needle = $bare ? rtrim($needle, '.') : $needle;

            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
