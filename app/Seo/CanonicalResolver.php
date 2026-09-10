<?php

namespace App\Seo;

use App\Models\Page;

/**
 * Canonical is a *signal* to search engines, not a guarantee - we still
 * pick one carefully and consistently:
 *
 * - Default: self-referencing canonical, built from the project's single
 *   URL policy (UrlResolver), always absolute, always the configured host,
 *   never carrying query parameters (UTM or otherwise never produce a
 *   distinct canonical - see UrlResolver/UTM tests).
 *
 *   This blanket "always strip query params" rule is correct today because
 *   no public page has pagination or filters that change the actual
 *   content shown (see CLAUDE.md / Phase 4 report). If a future phase adds
 *   paginated or filterable listing pages, revisit this class then: some
 *   query-parameter combinations may need their own canonical instead of
 *   always collapsing to the unfiltered page - don't assume this rule
 *   still holds for every URL type without re-checking it against
 *   whichever listing feature is being added.
 * - Custom canonical: only when an editor explicitly sets one on
 *   SeoMetadata, and only if it passes validation. An invalid custom value
 *   (empty after trim, javascript: pseudo-protocol, unparsable, no host)
 *   is rejected and we fall back to the safe self-referencing default
 *   instead of ever emitting a malformed <link rel="canonical">.
 */
class CanonicalResolver
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function resolve(Page $page): string
    {
        $custom = $page->seoMetadata?->canonical_url;

        if ($custom && $this->isValid($custom)) {
            return rtrim($custom, '/') ?: $custom;
        }

        return $this->urlResolver->urlForPage($page);
    }

    public function isValid(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        if (str_starts_with(strtolower($url), 'javascript:')) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        return in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }
}
